<?php
/**
 * GYMRAT — Calendario Unificado de Fitness, Nutrición y Hábitos
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

// Días del mes seleccionado
$primerDiaDelMes = strtotime("$anio-$mes-01");
$totalDiasMes = (int)date('t', $primerDiaDelMes);
$diaSemanaInicio = (int)date('N', $primerDiaDelMes); // 1 (Lunes) a 7 (Domingo)

$mesAnterior = ($mes == 1) ? 12 : $mes - 1;
$anioAnterior = ($mes == 1) ? $anio - 1 : $anio;
$mesSiguiente = ($mes == 12) ? 1 : $mes + 1;
$anioSiguiente = ($mes == 12) ? $anio + 1 : $anio;

$nombresMeses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$nombreMesActual = $nombresMeses[$mes];

// 1. Obtener todos los entrenamientos del mes
$wStmt = $pdo->prepare("SELECT id, fecha, nombre, volumen_total, prs_conseguidos FROM entrenamientos WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? AND estado = 'completado'");
$wStmt->execute([$user['id'], $mes, $anio]);
$entrenosPorFecha = [];
foreach ($wStmt->fetchAll() as $w) {
    $entrenosPorFecha[$w['fecha']][] = $w;
}

// 2. Obtener calorías del mes agrupadas por fecha
$nStmt = $pdo->prepare("SELECT fecha, SUM(calorias) as total_cal FROM registro_comidas WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? GROUP BY fecha");
$nStmt->execute([$user['id'], $mes, $anio]);
$nutriPorFecha = $nStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Obtener agua del mes
$aStmt = $pdo->prepare("SELECT fecha, cantidad_ml FROM registro_agua WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ?");
$aStmt->execute([$user['id'], $mes, $anio]);
$aguaPorFecha = $aStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 4. Obtener pasos del mes
$pStmt = $pdo->prepare("SELECT fecha, pasos FROM registro_pasos WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ?");
$pStmt->execute([$user['id'], $mes, $anio]);
$pasosPorFecha = $pStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Calendario Central — GYMRAT';
$pageSubtitle = "Historial unificado de {$nombreMesActual} {$anio}";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-body">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.75rem;">
            <div>
                <h2>Calendario Integral de Hábitos</h2>
                <p>Visualizá tu constancia en entrenamientos, nutrición, hidratación y pasos día por día.</p>
            </div>
            <!-- Navegación entre meses -->
            <div style="display:flex; align-items:center; gap:0.5rem; background:var(--bg-card); padding:0.4rem 0.85rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                <a href="?mes=<?= $mesAnterior ?>&anio=<?= $anioAnterior ?>" class="btn-icon" style="color:var(--text-muted);">&larr;</a>
                <span style="font-weight:800; font-size:1.1rem; padding:0 0.75rem; color:var(--text-primary);">
                    <?= $nombreMesActual ?> <?= $anio ?>
                </span>
                <a href="?mes=<?= $mesSiguiente ?>&anio=<?= $anioSiguiente ?>" class="btn-icon" style="color:var(--text-muted);">&rarr;</a>
            </div>
        </div>

        <!-- Matriz del Calendario -->
        <div class="card" style="padding:1rem;">
            <!-- Días de la semana -->
            <div style="display:grid; grid-template-columns: repeat(7, 1fr); text-align:center; font-weight:700; font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); padding-bottom:0.75rem; border-bottom:1px solid var(--border-subtle); margin-bottom:0.5rem;">
                <div>Lun</div>
                <div>Mar</div>
                <div>Mié</div>
                <div>Jue</div>
                <div>Vie</div>
                <div>Sáb</div>
                <div>Dom</div>
            </div>

            <!-- Celdas de los días -->
            <div style="display:grid; grid-template-columns: repeat(7, 1fr); gap:0.5rem;">
                <!-- Días vacíos previos -->
                <?php for ($v = 1; $v < $diaSemanaInicio; $v++): ?>
                    <div style="min-height:95px; background:rgba(255,255,255,0.01); border-radius:var(--radius-sm); opacity:0.3;"></div>
                <?php endfor; ?>

                <!-- Días del mes -->
                <?php for ($dia = 1; $dia <= $totalDiasMes; $dia++): ?>
                    <?php
                    $fechaStr = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
                    $esHoy = ($fechaStr === date('Y-m-d'));
                    
                    $entrenosDia = $entrenosPorFecha[$fechaStr] ?? [];
                    $calDia = (int)($nutriPorFecha[$fechaStr] ?? 0);
                    $aguaDia = (int)($aguaPorFecha[$fechaStr] ?? 0);
                    $pasosDia = (int)($pasosPorFecha[$fechaStr] ?? 0);
                    ?>
                    <div style="min-height:95px; background:<?= $esHoy ? 'rgba(0, 245, 155, 0.05)' : 'var(--bg-secondary)' ?>; border:1px solid <?= $esHoy ? 'var(--accent-green)' : 'var(--border-subtle)' ?>; border-radius:var(--radius-sm); padding:0.4rem; display:flex; flex-direction:column; justify-content:space-between; cursor:pointer;" onclick="openDayDetail('<?= $fechaStr ?>')">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong style="font-size:0.85rem; color:<?= $esHoy ? 'var(--accent-green)' : 'var(--text-primary)' ?>;"><?= $dia ?></strong>
                            <?php if ($esHoy): ?>
                                <span class="badge badge-green" style="font-size:0.6rem; padding:0.1rem 0.35rem;">Hoy</span>
                            <?php endif; ?>
                        </div>

                        <!-- Indicadores visuales -->
                        <div style="display:flex; flex-direction:column; gap:0.2rem; font-size:0.7rem; margin-top:0.25rem;">
                            <?php if (!empty($entrenosDia)): ?>
                                <span style="background:rgba(0, 245, 155, 0.15); color:var(--accent-green); padding:0.1rem 0.3rem; border-radius:3px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    🏋️ <?= htmlspecialchars($entrenosDia[0]['nombre']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($calDia > 0): ?>
                                <span style="color:var(--text-secondary);">
                                    🥗 <?= number_format($calDia, 0, ',', '.') ?> kcal
                                </span>
                            <?php endif; ?>

                            <?php if ($aguaDia > 0): ?>
                                <span style="color:var(--accent-blue);">
                                    💧 <?= $aguaDia ?> ml
                                </span>
                            <?php endif; ?>

                            <?php if ($pasosDia > 0): ?>
                                <span style="color:var(--accent-amber);">
                                    👟 <?= number_format($pasosDia, 0, ',', '.') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </main>

    <!-- MODAL DETALLE DE UN DÍA DEL CALENDARIO -->
    <div class="modal-backdrop" id="modal_day_detail">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="day_modal_title">Detalle del Día</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal_day_detail')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body" id="day_modal_body">
                <p>Cargando información del día...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modal_day_detail')">Cerrar</button>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script>
    function openDayDetail(fechaStr) {
        document.getElementById('day_modal_title').innerText = `Día ${fechaStr}`;
        document.getElementById('day_modal_body').innerHTML = `
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <div style="background:var(--bg-secondary); padding:1rem; border-radius:var(--radius-md);">
                    <h4 style="margin-bottom:0.25rem;">Nutrición y Comidas</h4>
                    <p style="font-size:0.85rem; margin-bottom:0.5rem;">Podés abrir el registro detallado de este día en el módulo de nutrición.</p>
                    <a href="nutricion.php?fecha=${fechaStr}" class="btn btn-primary btn-sm">Abrir Diario Nutricional de este Día &rarr;</a>
                </div>
            </div>
        `;
        openModal('modal_day_detail');
    }
    </script>
</div>
