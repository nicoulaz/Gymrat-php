<?php
/**
 * GYMRAT — Informe Mensual del Atleta (Printable / Exportable)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
$nombresMeses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$nombreMes = $nombresMeses[$mes];

// Métricas de Entrenamientos en el Mes
$wStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_entrenos,
        COALESCE(SUM(duracion_segundos), 0) as total_segundos,
        COALESCE(SUM(volumen_total), 0) as total_volumen,
        COALESCE(SUM(prs_conseguidos), 0) as total_prs
    FROM entrenamientos 
    WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? AND estado = 'completado'
");
$wStmt->execute([$user['id'], $mes, $anio]);
$statsWorkout = $wStmt->fetch();

// Promedio de Nutrición en el Mes
$nStmt = $pdo->prepare("
    SELECT 
        COALESCE(AVG(total_cal), 0) as avg_cal,
        COALESCE(AVG(total_p), 0) as avg_p 
    FROM (
        SELECT fecha, SUM(calorias) as total_cal, SUM(proteinas) as total_p 
        FROM registro_comidas 
        WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? 
        GROUP BY fecha
    ) as sub
");
$nStmt->execute([$user['id'], $mes, $anio]);
$statsNutri = $nStmt->fetch();

// Promedio de Pasos
$pStmt = $pdo->prepare("SELECT COALESCE(AVG(pasos), 0) as avg_pasos FROM registro_pasos WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ?");
$pStmt->execute([$user['id'], $mes, $anio]);
$avgPasos = (int)round($pStmt->fetchColumn() ?: 0);

$pageTitle = 'Informe Mensual — ' . $nombreMes . ' ' . $anio . ' | GYMRAT';
$pageSubtitle = 'Reporte consolidado de rendimiento deportivo y hábitos metabólicos';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-body">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:2rem;">
            <div>
                <h2>Informe Mensual de Rendimiento</h2>
                <p>Resumen analítico oficial para evaluación de progresos y sobrecarga progresiva.</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                🖨️ Imprimir / Guardar como PDF
            </button>
        </div>

        <!-- Tarjeta Oficial del Informe (Estilo Reporte Comercial) -->
        <div class="card card-glow" id="printable_report" style="max-width:850px; margin:0 auto; padding:2.5rem;">
            <!-- Cabecera del Reporte -->
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid var(--border-subtle); padding-bottom:1.5rem; margin-bottom:2rem;">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div class="brand-logo-icon">
                        <svg viewBox="0 0 24 24"><path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29z"/></svg>
                    </div>
                    <div>
                        <div class="brand-name">GYM<span>RAT</span></div>
                        <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.06em;">Informe Oficial del Atleta</div>
                    </div>
                </div>

                <div style="text-align:right;">
                    <h3 style="font-size:1.4rem; color:var(--accent-green); margin:0; text-transform:uppercase;"><?= $nombreMes ?> <?= $anio ?></h3>
                    <span style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?> (@<?= htmlspecialchars($user['username']) ?>)</span>
                </div>
            </div>

            <!-- Grilla de Métricas Oficiales (Requerimiento 57) -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                <!-- Bloque Entrenamiento -->
                <div style="background:var(--bg-secondary); border-radius:var(--radius-md); padding:1.25rem;">
                    <h4 style="font-size:1rem; color:var(--accent-green); margin-bottom:1rem; text-transform:uppercase; letter-spacing:0.04em;">Entrenamiento de Fuerza</h4>
                    <div style="display:flex; flex-direction:column; gap:0.75rem; font-size:0.95rem;">
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Sesiones Completadas:</span>
                            <strong style="color:var(--text-primary);"><?= (int)$statsWorkout['total_entrenos'] ?> entrenamientos</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Tiempo Entrenando:</span>
                            <strong style="color:var(--text-primary);"><?= gmdate("H\h i\m", (int)$statsWorkout['total_segundos']) ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Volumen Total Acumulado:</span>
                            <strong style="color:var(--accent-green);"><?= number_format((float)$statsWorkout['total_volumen'], 0, ',', '.') ?> kg</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Récords Personales (PRs):</span>
                            <strong style="color:var(--accent-amber);"><?= (int)$statsWorkout['total_prs'] ?> superados</strong>
                        </div>
                    </div>
                </div>

                <!-- Bloque Nutrición y Hábitos -->
                <div style="background:var(--bg-secondary); border-radius:var(--radius-md); padding:1.25rem;">
                    <h4 style="font-size:1rem; color:var(--accent-blue); margin-bottom:1rem; text-transform:uppercase; letter-spacing:0.04em;">Nutrición & Actividad</h4>
                    <div style="display:flex; flex-direction:column; gap:0.75rem; font-size:0.95rem;">
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Calorías Promedio Diario:</span>
                            <strong style="color:var(--text-primary);"><?= number_format((float)$statsNutri['avg_cal'], 0, ',', '.') ?> kcal</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Proteína Promedio Diaria:</span>
                            <strong style="color:var(--accent-green);"><?= round((float)$statsNutri['avg_p'], 1) ?> g</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Promedio de Pasos:</span>
                            <strong style="color:var(--accent-amber);"><?= number_format($avgPasos, 0, ',', '.') ?> / día</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--text-muted);">Evolución de Peso:</span>
                            <strong style="color:var(--text-primary);"><?= (float)$user['peso_inicial'] ?> kg &rarr; <?= (float)$user['peso_actual'] ?> kg</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer del Informe -->
            <div style="border-top:1px solid var(--border-subtle); padding-top:1rem; display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:var(--text-muted);">
                <span>Generado por la plataforma GYMRAT</span>
                <span>Constancia verificada: <?= (int)$user['streak_dias'] ?> días consecutivos</span>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>
