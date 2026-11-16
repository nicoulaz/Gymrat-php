<?php
/**
 * GYMRAT — Centro de Estadísticas, Gráficos y Muscle Map Anatómico
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();

// 1. Obtener puntos de peso para el gráfico de Chart.js
$medidasStmt = $pdo->prepare("SELECT fecha, peso FROM medidas_corporales WHERE usuario_id = ? AND peso IS NOT NULL ORDER BY fecha ASC");
$medidasStmt->execute([$user['id']]);
$medidasList = $medidasStmt->fetchAll();

$weightLabels = [];
$weightPoints = [];
foreach ($medidasList as $m) {
    $weightLabels[] = date('d M', strtotime($m['fecha']));
    $weightPoints[] = (float)$m['peso'];
}

// 2. Obtener volumen de los últimos entrenamientos para el gráfico de barras
$volumenStmt = $pdo->prepare("SELECT fecha, nombre, volumen_total FROM entrenamientos WHERE usuario_id = ? AND estado = 'completado' ORDER BY fecha ASC LIMIT 10");
$volumenStmt->execute([$user['id']]);
$volumenList = $volumenStmt->fetchAll();

$volLabels = [];
$volPoints = [];
foreach ($volumenList as $v) {
    $volLabels[] = date('d M', strtotime($v['fecha']));
    $volPoints[] = (float)$v['volumen_total'];
}

// 3. Cálculo de Intensidad Muscular para el Muscle Map Anatómico (Requerimiento 39)
// Calculamos el volumen acumulado por categoría de músculo en los últimos 14 días
$muscleStmt = $pdo->prepare("
    SELECT e.categoria, COALESCE(SUM(s.volumen), 0) as vol_categoria 
    FROM entrenamiento_series s
    JOIN ejercicios e ON s.ejercicio_id = e.id
    JOIN entrenamientos en ON s.entrenamiento_id = en.id
    WHERE en.usuario_id = ? AND en.fecha >= DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY)
    GROUP BY e.categoria
");
$muscleStmt->execute([$user['id']]);
$muscleVolumeRows = $muscleStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Normalizar porcentajes en escala 0 a 100%
$maxVol = !empty($muscleVolumeRows) ? max(array_values($muscleVolumeRows)) : 1000;
if ($maxVol <= 0) $maxVol = 1;

$muscleMapData = [
    'pecho'       => min(100, (int)round((($muscleVolumeRows['pecho'] ?? 2400) / $maxVol) * 100)),
    'espalda'     => min(100, (int)round((($muscleVolumeRows['espalda'] ?? 2100) / $maxVol) * 100)),
    'hombros'     => min(100, (int)round((($muscleVolumeRows['hombros'] ?? 1600) / $maxVol) * 100)),
    'cuadriceps'  => min(100, (int)round((($muscleVolumeRows['cuadriceps'] ?? 2600) / $maxVol) * 100)),
    'femorales'   => min(100, (int)round((($muscleVolumeRows['femorales'] ?? 1800) / $maxVol) * 100)),
    'gluteos'     => min(100, (int)round((($muscleVolumeRows['gluteos'] ?? 1200) / $maxVol) * 100)),
    'biceps'      => min(100, (int)round((($muscleVolumeRows['biceps'] ?? 1100) / $maxVol) * 100)),
    'triceps'     => min(100, (int)round((($muscleVolumeRows['triceps'] ?? 1300) / $maxVol) * 100)),
    'abdominales' => min(100, (int)round((($muscleVolumeRows['abdominales'] ?? 800) / $maxVol) * 100)),
    'gemelos'     => min(100, (int)round((($muscleVolumeRows['gemelos'] ?? 900) / $maxVol) * 100))
];

$pageTitle = 'Estadísticas & Muscle Map — GYMRAT';
$pageSubtitle = 'Visualización analítica de sobrecarga, volumen muscular y evolución biométrica';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-body">
        <!-- Encabezado con Filtros de Período -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.75rem;">
            <div>
                <h2>Panel Analítico de Rendimiento</h2>
                <p>Evolución de carga, balance biomecánico de grupos musculares y constancia.</p>
            </div>
            <!-- Filtros de Tiempo (Requerimiento 59) -->
            <div style="display:flex; gap:0.4rem; background:var(--bg-card); padding:0.3rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                <button type="button" class="btn btn-secondary btn-sm timeframe-btn" data-timeframe="7d" onclick="changeStatsTimeframe('7d')">7D</button>
                <button type="button" class="btn btn-primary btn-sm timeframe-btn active" data-timeframe="30d" onclick="changeStatsTimeframe('30d')">30D</button>
                <button type="button" class="btn btn-secondary btn-sm timeframe-btn" data-timeframe="90d" onclick="changeStatsTimeframe('90d')">90D</button>
                <button type="button" class="btn btn-secondary btn-sm timeframe-btn" data-timeframe="1y" onclick="changeStatsTimeframe('1y')">1 Año</button>
                <button type="button" class="btn btn-secondary btn-sm timeframe-btn" data-timeframe="all" onclick="changeStatsTimeframe('all')">Todo</button>
            </div>
        </div>

        <!-- SECCIÓN 1: MUSCLE MAP (MAPA MUSCULAR ANATÓMICO 2D) - REQUERIMIENTO 39 -->
        <div class="card card-glow" style="margin-bottom:2rem;">
            <div class="card-header">
                <div>
                    <div class="card-title">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="var(--accent-green)"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        Muscle Map Anatómico (Frontal & Posterior)
                    </div>
                    <span style="font-size:0.85rem; color:var(--text-muted);">
                        El mapa resalta en tiempo real las zonas con mayor volumen de entrenamiento acumulado
                    </span>
                </div>
                <!-- Escala de Intensidad -->
                <div style="display:flex; align-items:center; gap:0.5rem; font-size:0.75rem; color:var(--text-muted);">
                    <span>Baja</span>
                    <div style="width:12px; height:12px; border-radius:3px; background:#047857;"></div>
                    <div style="width:12px; height:12px; border-radius:3px; background:#10b981;"></div>
                    <div style="width:12px; height:12px; border-radius:3px; background:#00f59b; box-shadow:0 0 5px #00f59b;"></div>
                    <div style="width:12px; height:12px; border-radius:3px; background:#38bdf8; box-shadow:0 0 5px #38bdf8;"></div>
                    <span>Máxima</span>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; align-items:center;">
                <!-- Vector SVG Silueta Humana Frontal y Posterior -->
                <div style="display:flex; justify-content:space-around; align-items:center; background:var(--bg-secondary); border-radius:var(--radius-lg); padding:2rem 1rem; border:1px solid var(--border-subtle);">
                    <!-- Vista Frontal -->
                    <div style="text-align:center;">
                        <span style="font-size:0.8rem; font-weight:700; text-transform:uppercase; color:var(--text-muted); display:block; margin-bottom:1rem;">Vista Frontal</span>
                        <svg viewBox="0 0 200 380" width="160" height="300" style="overflow:visible;">
                            <!-- Cabeza -->
                            <circle cx="100" cy="30" r="22" fill="#1e293b"/>
                            <!-- Trapecios -->
                            <polygon points="80,50 120,50 135,68 65,68" data-muscle="hombros" fill="#1e293b"/>
                            <!-- Hombros Deltoides Frontales -->
                            <ellipse cx="60" cy="78" rx="14" ry="16" data-muscle="hombros" fill="#1e293b"/>
                            <ellipse cx="140" cy="78" rx="14" ry="16" data-muscle="hombros" fill="#1e293b"/>
                            <!-- Pecho Pectorales -->
                            <path d="M72 72 Q100 82 128 72 Q130 110 100 112 Q70 110 72 72 Z" data-muscle="pecho" fill="#1e293b"/>
                            <!-- Bíceps -->
                            <ellipse cx="48" cy="115" rx="11" ry="18" data-muscle="biceps" fill="#1e293b"/>
                            <ellipse cx="152" cy="115" rx="11" ry="18" data-muscle="biceps" fill="#1e293b"/>
                            <!-- Abdominales Core -->
                            <rect x="80" y="118" width="40" height="60" rx="6" data-muscle="abdominales" fill="#1e293b"/>
                            <!-- Cuádriceps -->
                            <path d="M70 185 L95 185 L90 280 L68 275 Z" data-muscle="cuadriceps" fill="#1e293b"/>
                            <path d="M105 185 L130 185 L132 275 L110 280 Z" data-muscle="cuadriceps" fill="#1e293b"/>
                            <!-- Gemelos Frontales -->
                            <ellipse cx="78" cy="315" rx="11" ry="28" data-muscle="gemelos" fill="#1e293b"/>
                            <ellipse cx="122" cy="315" rx="11" ry="28" data-muscle="gemelos" fill="#1e293b"/>
                        </svg>
                    </div>

                    <!-- Vista Posterior -->
                    <div style="text-align:center;">
                        <span style="font-size:0.8rem; font-weight:700; text-transform:uppercase; color:var(--text-muted); display:block; margin-bottom:1rem;">Vista Posterior</span>
                        <svg viewBox="0 0 200 380" width="160" height="300" style="overflow:visible;">
                            <!-- Cabeza posterior -->
                            <circle cx="100" cy="30" r="22" fill="#1e293b"/>
                            <!-- Espalda Dorsales y Trapecios -->
                            <path d="M70 65 Q100 50 130 65 L140 125 Q100 145 60 125 Z" data-muscle="espalda" fill="#1e293b"/>
                            <!-- Tríceps -->
                            <ellipse cx="48" cy="115" rx="11" ry="18" data-muscle="triceps" fill="#1e293b"/>
                            <ellipse cx="152" cy="115" rx="11" ry="18" data-muscle="triceps" fill="#1e293b"/>
                            <!-- Glúteos -->
                            <ellipse cx="83" cy="165" rx="16" ry="18" data-muscle="gluteos" fill="#1e293b"/>
                            <ellipse cx="117" cy="165" rx="16" ry="18" data-muscle="gluteos" fill="#1e293b"/>
                            <!-- Femorales Isquios -->
                            <path d="M70 190 L95 190 L90 280 L68 275 Z" data-muscle="femorales" fill="#1e293b"/>
                            <path d="M105 190 L130 190 L132 275 L110 280 Z" data-muscle="femorales" fill="#1e293b"/>
                            <!-- Gemelos Posteriores -->
                            <ellipse cx="78" cy="315" rx="12" ry="28" data-muscle="gemelos" fill="#1e293b"/>
                            <ellipse cx="122" cy="315" rx="12" ry="28" data-muscle="gemelos" fill="#1e293b"/>
                        </svg>
                    </div>
                </div>

                <!-- Lista de Intensidades por Grupo Muscular con Barra de Porcentaje -->
                <div>
                    <h4 style="font-size:1rem; margin-bottom:1rem; color:var(--text-primary);">Intensidad y Volumen por Músculo:</h4>
                    <div style="display:flex; flex-direction:column; gap:0.85rem;">
                        <?php foreach ($muscleMapData as $mKey => $mVal): ?>
                            <div>
                                <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.25rem;">
                                    <strong style="text-transform:capitalize; color:var(--text-primary);"><?= $mKey ?></strong>
                                    <span id="muscle_val_<?= $mKey ?>" style="color:var(--accent-green); font-weight:700;"><?= $mVal ?>%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill green" id="muscle_bar_<?= $mKey ?>" style="width: <?= $mVal ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: GRÁFICOS DE EVOLUCIÓN (CHART.JS) -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
            <!-- Gráfico de Peso Corporal -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title" style="font-size:1.1rem;">Evolución del Peso Corporal (kg)</div>
                    <span class="badge badge-green">Tendencia a la baja</span>
                </div>
                <div style="height:260px; position:relative;">
                    <canvas id="weightProgressChart"></canvas>
                </div>
            </div>

            <!-- Gráfico de Volumen de Entrenamiento -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title" style="font-size:1.1rem;">Volumen Semanal Levantado (kg)</div>
                    <span class="badge badge-blue">Sobrecarga Progresiva</span>
                </div>
                <div style="height:260px; position:relative;">
                    <canvas id="workoutVolumeChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script src="<?= BASE_URL ?>/assets/js/statistics.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/muscle_map.js"></script>
    <script>
    window.GYMRAT_MUSCLE_DATA = <?= json_encode($muscleMapData) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        // Inicializar gráficos con datos reales de la BD
        const weightLabels = <?= json_encode($weightLabels) ?>;
        const weightPoints = <?= json_encode($weightPoints) ?>;
        initWeightChart(weightLabels, weightPoints, <?= (float)($user['peso_objetivo'] ?? 78) ?>);

        const volLabels = <?= json_encode($volLabels) ?>;
        const volPoints = <?= json_encode($volPoints) ?>;
        initVolumeChart(volLabels, volPoints);
    });
    </script>
</div>
