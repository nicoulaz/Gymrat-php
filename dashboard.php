<?php
/**
 * GYMRAT — Dashboard Principal (Centro de Control Fitness)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();
$hoy = date('Y-m-d');

// 1. Obtener Nutrición de Hoy
$nutriStmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(calorias), 0) as total_cal,
        COALESCE(SUM(proteinas), 0) as total_p,
        COALESCE(SUM(carbohidratos), 0) as total_c,
        COALESCE(SUM(grasas), 0) as total_f
    FROM registro_comidas 
    WHERE usuario_id = ? AND fecha = ?
");
$nutriStmt->execute([$user['id'], $hoy]);
$totalesNutri = $nutriStmt->fetch();

$calActual = (int)round($totalesNutri['total_cal']);
$calMeta = (int)($user['calorias_objetivo'] ?? 2400);
$calPorcentaje = ($calMeta > 0) ? min(100, (int)round(($calActual / $calMeta) * 100)) : 0;
$calRestantes = $calMeta - $calActual;

$protActual = (float)round($totalesNutri['total_p'], 1);
$protMeta = (int)($user['proteinas_objetivo'] ?? 190);
$protPorcentaje = ($protMeta > 0) ? min(100, (int)round(($protActual / $protMeta) * 100)) : 0;

$carbActual = (float)round($totalesNutri['total_c'], 1);
$carbMeta = (int)($user['carbohidratos_objetivo'] ?? 240);
$carbPorcentaje = ($carbMeta > 0) ? min(100, (int)round(($carbActual / $carbMeta) * 100)) : 0;

$grasActual = (float)round($totalesNutri['total_f'], 1);
$grasMeta = (int)($user['grasas_objetivo'] ?? 70);
$grasPorcentaje = ($grasMeta > 0) ? min(100, (int)round(($grasActual / $grasMeta) * 100)) : 0;

// 2. Obtener Agua de Hoy
$aguaStmt = $pdo->prepare("SELECT cantidad_ml FROM registro_agua WHERE usuario_id = ? AND fecha = ?");
$aguaStmt->execute([$user['id'], $hoy]);
$aguaActual = (int)($aguaStmt->fetchColumn() ?: 0);
$aguaMeta = (int)($user['agua_objetivo'] ?? 2500);
$aguaPorcentaje = ($aguaMeta > 0) ? min(100, (int)round(($aguaActual / $aguaMeta) * 100)) : 0;

// 3. Obtener Pasos de Hoy
$pasosStmt = $pdo->prepare("SELECT * FROM registro_pasos WHERE usuario_id = ? AND fecha = ?");
$pasosStmt->execute([$user['id'], $hoy]);
$pasosRow = $pasosStmt->fetch();
$pasosActual = (int)($pasosRow['pasos'] ?? 0);
$distanciaKm = (float)($pasosRow['distancia_km'] ?? 0.0);
$caloriasPasos = (int)($pasosRow['calorias_quemadas'] ?? 0);
$pasosMeta = (int)($user['pasos_objetivo'] ?? 10000);
$pasosPorcentaje = ($pasosMeta > 0) ? min(100, (int)round(($pasosActual / $pasosMeta) * 100)) : 0;

// 4. Último Entrenamiento y Rutina Sugerida
$workoutStmt = $pdo->prepare("SELECT * FROM entrenamientos WHERE usuario_id = ? AND estado = 'completado' ORDER BY fecha DESC, id DESC LIMIT 1");
$workoutStmt->execute([$user['id']]);
$ultimoEntreno = $workoutStmt->fetch();

// 5. Total de Entrenamientos y PRs
$totalEntrenosStmt = $pdo->prepare("SELECT COUNT(*) FROM entrenamientos WHERE usuario_id = ? AND estado = 'completado'");
$totalEntrenosStmt->execute([$user['id']]);
$totalEntrenos = (int)$totalEntrenosStmt->fetchColumn();

$totalPrsStmt = $pdo->prepare("SELECT COUNT(*) FROM records_personales WHERE usuario_id = ?");
$totalPrsStmt->execute([$user['id']]);
$totalPrs = (int)$totalPrsStmt->fetchColumn();

// Saludo dinámico según la hora
$hora = (int)date('H');
if ($hora < 12) $saludo = "Buenos días";
elseif ($hora < 19) $saludo = "Buenas tardes";
else $saludo = "Buenas noches";

$pageTitle = 'Dashboard — GYMRAT';
$pageSubtitle = "Hoy es tu día {$user['streak_dias']} de constancia fitness. ¡A romper marcas!";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-body">
        <!-- Saludo Principal y Resumen Rápido -->
        <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1rem; margin-bottom:1.75rem;">
            <div>
                <h2 style="font-size:1.85rem; margin-bottom:0.25rem;">
                    <?= $saludo ?>, <?= htmlspecialchars($user['nombre']) ?> 👋
                </h2>
                <p style="font-size:0.95rem;">
                    Objetivo activo: <strong style="color:var(--text-primary); text-transform:capitalize;"><?= str_replace('_', ' ', $user['objetivo']) ?></strong> 
                    • Peso: <strong><?= (float)$user['peso_actual'] ?> kg</strong> &rarr; Meta: <strong><?= (float)$user['peso_objetivo'] ?> kg</strong>
                </p>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <a href="<?= BASE_URL ?>/nutricion.php" class="btn btn-secondary btn-sm">
                    + Registrar Comida
                </a>
                <a href="<?= BASE_URL ?>/entrenamientos.php" class="btn btn-primary btn-sm">
                    💪 Iniciar Entrenamiento
                </a>
            </div>
        </div>

        <!-- Alerta contextual de calorías -->
        <?php if ($calRestantes > 0): ?>
            <div style="background:rgba(0, 245, 155, 0.08); border:1px solid rgba(0, 245, 155, 0.2); border-radius:var(--radius-md); padding:0.85rem 1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:0.75rem; font-size:0.9rem;">
                    <span style="font-size:1.2rem;">🥗</span>
                    <span>Te quedan <strong><?= number_format($calRestantes, 0, ',', '.') ?> kcal</strong> para completar tu objetivo calórico de hoy.</span>
                </div>
                <a href="<?= BASE_URL ?>/nutricion.php" style="font-size:0.85rem; font-weight:700;">Ver diario &rarr;</a>
            </div>
        <?php else: ?>
            <div style="background:rgba(245, 158, 11, 0.08); border:1px solid rgba(245, 158, 11, 0.2); border-radius:var(--radius-md); padding:0.85rem 1.25rem; margin-bottom:1.5rem; display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:0.75rem; font-size:0.9rem;">
                    <span style="font-size:1.2rem;">⚡</span>
                    <span>Has superado tu meta diaria por <strong><?= number_format(abs($calRestantes), 0, ',', '.') ?> kcal</strong>. Todo el combustible para tus entrenamientos.</span>
                </div>
                <a href="<?= BASE_URL ?>/nutricion.php" style="font-size:0.85rem; font-weight:700;">Ver diario &rarr;</a>
            </div>
        <?php endif; ?>

        <!-- Tarjetas Superiores de Métricas Clave -->
        <div class="grid-stats-4">
            <!-- Tarjeta Calorías -->
            <div class="stat-widget">
                <div class="stat-top">
                    <span class="stat-label">Calorías Diarias</span>
                    <div class="stat-icon-wrapper green">
                        <svg viewBox="0 0 24 24"><path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z"/></svg>
                    </div>
                </div>
                <div class="stat-value-group">
                    <div class="stat-value"><?= number_format($calActual, 0, ',', '.') ?></div>
                    <div class="stat-unit">/ <?= number_format($calMeta, 0, ',', '.') ?> kcal</div>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill green" style="width: <?= $calPorcentaje ?>%;"></div>
                </div>
                <div style="font-size:0.75rem; color:var(--text-muted); display:flex; justify-content:space-between;">
                    <span><?= $calPorcentaje ?>% consumido</span>
                    <span><?= ($calRestantes >= 0) ? $calRestantes . ' kcal restan' : '+' . abs($calRestantes) . ' excedente' ?></span>
                </div>
            </div>

            <!-- Tarjeta Proteínas -->
            <div class="stat-widget">
                <div class="stat-top">
                    <span class="stat-label">Proteína (2.0g/kg)</span>
                    <div class="stat-icon-wrapper blue">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                    </div>
                </div>
                <div class="stat-value-group">
                    <div class="stat-value"><?= $protActual ?></div>
                    <div class="stat-unit">/ <?= $protMeta ?> g</div>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill blue" style="width: <?= $protPorcentaje ?>%;"></div>
                </div>
                <div style="font-size:0.75rem; color:var(--text-muted); display:flex; justify-content:space-between;">
                    <span><?= $protPorcentaje ?>% completado</span>
                    <span><?= max(0, round($protMeta - $protActual, 1)) ?> g restantes</span>
                </div>
            </div>

            <!-- Tarjeta Agua (Interactivo en vivo sin recargar) -->
            <div class="stat-widget" id="water_widget">
                <div class="stat-top">
                    <span class="stat-label">Agua Diaria</span>
                    <div class="stat-icon-wrapper blue">
                        <svg viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                    </div>
                </div>
                <div class="stat-value-group">
                    <div class="stat-value" id="water_current_ml"><?= number_format($aguaActual, 0, ',', '.') ?></div>
                    <div class="stat-unit">/ <span id="water_target_ml"><?= number_format($aguaMeta, 0, ',', '.') ?></span> ml</div>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill blue" id="water_progress_fill" style="width: <?= $aguaPorcentaje ?>%;"></div>
                </div>
                <div style="display:flex; gap:0.4rem; margin-top:0.35rem;">
                    <button type="button" class="btn btn-secondary btn-sm" style="flex:1; padding:0.3rem;" onclick="addWater(250)">+250ml</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="flex:1; padding:0.3rem;" onclick="addWater(500)">+500ml</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="flex:1; padding:0.3rem;" onclick="addWater(750)">+750ml</button>
                </div>
            </div>

            <!-- Tarjeta Pasos (Interactivo) -->
            <div class="stat-widget">
                <div class="stat-top">
                    <span class="stat-label">Pasos Diarios</span>
                    <div class="stat-icon-wrapper amber">
                        <svg viewBox="0 0 24 24"><path d="M13.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM9.8 8.9L7 23h2.1l1.8-8 2.1 2v6h2v-7.5l-2.1-2 .6-3C14.8 12 16.8 13 19 13v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1L6 8.3V13h2V9.6l1.8-.7"/></svg>
                    </div>
                </div>
                <div class="stat-value-group">
                    <div class="stat-value" id="steps_current_val"><?= number_format($pasosActual, 0, ',', '.') ?></div>
                    <div class="stat-unit">/ <?= number_format($pasosMeta, 0, ',', '.') ?></div>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill amber" id="steps_progress_fill" style="width: <?= $pasosPorcentaje ?>%;"></div>
                </div>
                <div style="font-size:0.75rem; color:var(--text-muted); display:flex; justify-content:space-between; align-items:center;">
                    <span id="steps_current_km"><?= $distanciaKm ?> km • <?= $caloriasPasos ?> kcal</span>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding:0.15rem 0.5rem; font-size:0.7rem;" onclick="updateStepsPrompt()">
                        Editar
                    </button>
                </div>
            </div>
        </div>

        <!-- Grilla Central del Dashboard: Nutrición & Entrenamiento -->
        <div class="grid-dashboard-main">
            <!-- Columna Izquierda: Macronutrientes y Rutina de Hoy -->
            <div style="display:flex; flex-direction:column; gap:1.5rem;">
                <!-- Desglose de Macronutrientes -->
                <div class="card card-glow">
                    <div class="card-header">
                        <div class="card-title">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="var(--accent-green)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"/></svg>
                            Distribución de Macros de Hoy
                        </div>
                        <a href="<?= BASE_URL ?>/nutricion.php" class="btn btn-outline btn-sm">Abrir Diario Nutricional &rarr;</a>
                    </div>

                    <div class="macro-grid">
                        <div class="macro-card">
                            <div class="macro-name">
                                <span class="dot protein"></span> Proteínas
                            </div>
                            <div class="macro-values">
                                <?= $protActual ?>g <span>/ <?= $protMeta ?>g</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill green" style="width: <?= $protPorcentaje ?>%;"></div>
                            </div>
                        </div>

                        <div class="macro-card">
                            <div class="macro-name">
                                <span class="dot carbs"></span> Carbohidratos
                            </div>
                            <div class="macro-values">
                                <?= $carbActual ?>g <span>/ <?= $carbMeta ?>g</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill blue" style="width: <?= $carbPorcentaje ?>%;"></div>
                            </div>
                        </div>

                        <div class="macro-card">
                            <div class="macro-name">
                                <span class="dot fats"></span> Grasas
                            </div>
                            <div class="macro-values">
                                <?= $grasActual ?>g <span>/ <?= $grasMeta ?>g</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill amber" style="width: <?= $grasPorcentaje ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sesión de Entrenamiento Sugerida / Iniciar -->
                <div class="card card-glow">
                    <div class="card-header">
                        <div class="card-title">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="var(--accent-green)"><path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29z"/></svg>
                            Tu Próximo Entrenamiento
                        </div>
                        <span class="badge badge-green">Frecuencia Semanal</span>
                    </div>

                    <div style="background:var(--bg-secondary); border-radius:var(--radius-md); padding:1.25rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                        <div>
                            <h4 style="margin-bottom:0.25rem;">PPL x Hypertrophy — Día 1: Push</h4>
                            <p style="font-size:0.85rem; margin:0;">Pecho plano, Inclinado mancuernas, Press militar, Fondos y Tríceps</p>
                        </div>
                        <div style="display:flex; gap:0.5rem;">
                            <a href="<?= BASE_URL ?>/workout_en_vivo.php?rutina=1" class="btn btn-primary btn-sm">
                                ▶ Iniciar Ahora
                            </a>
                            <a href="<?= BASE_URL ?>/entrenamientos.php" class="btn btn-secondary btn-sm">
                                Ver Rutinas
                            </a>
                        </div>
                    </div>

                    <?php if ($ultimoEntreno): ?>
                    <div style="margin-top:1rem; font-size:0.85rem; color:var(--text-muted); display:flex; justify-content:space-between; align-items:center;">
                        <span>Última sesión: <strong><?= htmlspecialchars($ultimoEntreno['nombre']) ?></strong> (<?= date('d/m/Y', strtotime($ultimoEntreno['fecha'])) ?>)</span>
                        <span style="color:var(--accent-green); font-weight:700;">Volumen: <?= number_format((float)$ultimoEntreno['volumen_total'], 0, ',', '.') ?> kg</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna Derecha: Récords Personales, Racha y Resumen -->
            <div style="display:flex; flex-direction:column; gap:1.5rem;">
                <!-- Widget de Racha y Gamificación -->
                <div class="card" style="text-align:center; padding:1.75rem 1.5rem;">
                    <div style="width:68px; height:68px; margin:0 auto 1rem; border-radius:50%; background:rgba(245, 158, 11, 0.12); display:flex; align-items:center; justify-content:center; font-size:2rem;">
                        🔥
                    </div>
                    <div style="font-family:var(--font-heading); font-size:2.2rem; font-weight:900; line-height:1; color:var(--text-primary); margin-bottom:0.25rem;">
                        <?= (int)$user['streak_dias'] ?> DÍAS
                    </div>
                    <p style="font-size:0.85rem; margin-bottom:1.25rem;">Racha de disciplina ininterrumpida en Gymrat</p>
                    <div style="display:flex; justify-content:space-around; border-top:1px solid var(--border-subtle); padding-top:1rem; font-size:0.85rem;">
                        <div>
                            <div style="font-weight:800; font-size:1.15rem; color:var(--text-primary);"><?= $totalEntrenos ?></div>
                            <div style="color:var(--text-muted); font-size:0.75rem;">Entrenamientos</div>
                        </div>
                        <div>
                            <div style="font-weight:800; font-size:1.15rem; color:var(--accent-amber);"><?= $totalPrs ?></div>
                            <div style="color:var(--text-muted); font-size:0.75rem;">Récords (PRs)</div>
                        </div>
                        <div>
                            <div style="font-weight:800; font-size:1.15rem; color:var(--accent-green);">Nv. <?= (int)$user['nivel'] ?></div>
                            <div style="color:var(--text-muted); font-size:0.75rem;">Nivel Atleta</div>
                        </div>
                    </div>
                </div>

                <!-- Récords Personales Recientes -->
                <div class="card">
                    <div class="card-header" style="margin-bottom:0.75rem;">
                        <div class="card-title" style="font-size:1rem;">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="var(--accent-amber)"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0011 15.9V19H7v2h10v-2h-4v-3.1c1.92-.37 3.42-1.84 3.61-3.96C19.08 11.63 21 9.55 21 7V5c0-1.1-.9-2-2-2zm-14 3V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>
                            Tus Mejores Marcas (PRs)
                        </div>
                        <a href="<?= BASE_URL ?>/progreso.php" style="font-size:0.8rem;">Ver todos &rarr;</a>
                    </div>
                    <?php
                    $prsStmt = $pdo->prepare("
                        SELECT pr.*, e.nombre as ejercicio_nombre 
                        FROM records_personales pr 
                        JOIN ejercicios e ON pr.ejercicio_id = e.id 
                        WHERE pr.usuario_id = ? 
                        ORDER BY pr.fecha DESC LIMIT 3
                    ");
                    $prsStmt->execute([$user['id']]);
                    $recentPRs = $prsStmt->fetchAll();
                    ?>
                    <div style="display:flex; flex-direction:column; gap:0.6rem;">
                        <?php if (empty($recentPRs)): ?>
                            <p style="font-size:0.85rem; color:var(--text-muted);">Comenzá a registrar tus series pesadas para desbloquear PRs.</p>
                        <?php else: ?>
                            <?php foreach ($recentPRs as $pr): ?>
                                <div style="background:var(--bg-secondary); border-radius:var(--radius-sm); padding:0.65rem 0.85rem; display:flex; justify-content:space-between; align-items:center;">
                                    <div>
                                        <div style="font-size:0.85rem; font-weight:700;"><?= htmlspecialchars($pr['ejercicio_nombre']) ?></div>
                                        <div style="font-size:0.75rem; color:var(--text-muted);"><?= date('d/m/Y', strtotime($pr['fecha'])) ?></div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-weight:800; color:var(--accent-amber); font-size:0.95rem;"><?= (float)$pr['peso'] ?> kg × <?= (int)$pr['repeticiones'] ?></div>
                                        <div style="font-size:0.7rem; color:var(--text-muted);">1RM: <?= (float)$pr['one_rm_estimado'] ?> kg</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>

    <?php if (isset($_GET['welcome'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showToast('⚡ ¡Bienvenido a GYMRAT!', 'Tus objetivos fueron calculados con la fórmula Mifflin-St Jeor.', 'pr', 6000);
        });
    </script>
    <?php endif; ?>
</div>
