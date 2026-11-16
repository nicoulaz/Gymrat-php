<?php
/**
 * GYMRAT — Módulo de Progreso Corporal, Fotos y Slider Antes/Después
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();

// Obtener historial de medidas
$medidasStmt = $pdo->prepare("SELECT * FROM medidas_corporales WHERE usuario_id = ? ORDER BY fecha DESC");
$medidasStmt->execute([$user['id']]);
$medidas = $medidasStmt->fetchAll();

// Obtener fotos de progreso
$fotosStmt = $pdo->prepare("SELECT * FROM fotos_progreso WHERE usuario_id = ? ORDER BY fecha DESC");
$fotosStmt->execute([$user['id']]);
$fotos = $fotosStmt->fetchAll();

// Cálculo de progreso hacia el objetivo de peso
$pesoInicial = (float)($user['peso_inicial'] ?? 85.0);
$pesoActual = (float)($user['peso_actual'] ?? 82.4);
$pesoObjetivo = (float)($user['peso_objetivo'] ?? 78.0);

$recorridoTotal = abs($pesoInicial - $pesoObjetivo);
$avanceActual = abs($pesoInicial - $pesoActual);
$porcentajeMeta = ($recorridoTotal > 0) ? min(100, (int)round(($avanceActual / $recorridoTotal) * 100)) : 100;

$pageTitle = 'Progreso Corporal & Fotos — GYMRAT';
$pageSubtitle = 'Medidas corporales, evolución de peso y comparación fotográfica';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-body">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.75rem;">
            <div>
                <h2>Seguimiento de Progreso Corporal</h2>
                <p>Monitoreá tu evolución física a través de datos antropométricos y fotografías privadas.</p>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="openModal('modal_add_measurement')">
                    📏 Registrar Medidas
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="openModal('modal_add_photo')">
                    📷 Subir Fotografía
                </button>
            </div>
        </div>

        <!-- Tarjeta de Progreso hacia el Peso Objetivo -->
        <div class="card card-glow" style="margin-bottom:2rem;">
            <div class="card-header">
                <div class="card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="var(--accent-green)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"/></svg>
                    Meta de Peso Corporal
                </div>
                <span class="badge badge-green"><?= $porcentajeMeta ?>% Completado</span>
            </div>

            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:1.5rem; text-align:center; margin-bottom:1.25rem;">
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Peso Inicial</div>
                    <div style="font-size:1.6rem; font-weight:800; color:var(--text-primary);"><?= $pesoInicial ?> kg</div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; color:var(--accent-green); font-weight:700;">Peso Actual</div>
                    <div style="font-size:2.2rem; font-weight:900; color:var(--accent-green); line-height:1;"><?= $pesoActual ?> kg</div>
                    <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.2rem;">
                        <?= ($pesoActual < $pesoInicial) ? '-' . round($pesoInicial - $pesoActual, 1) . ' kg perdidos' : '+' . round($pesoActual - $pesoInicial, 1) . ' kg ganados' ?>
                    </div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; color:var(--accent-blue); font-weight:700;">Peso Objetivo</div>
                    <div style="font-size:1.6rem; font-weight:800; color:var(--accent-blue);"><?= $pesoObjetivo ?> kg</div>
                </div>
            </div>

            <div class="progress-bar-container" style="height:10px;">
                <div class="progress-bar-fill green" style="width: <?= $porcentajeMeta ?>%;"></div>
            </div>
        </div>

        <!-- Herramienta de Comparación de Fotos (Slider Antes / Después) -->
        <div class="card" style="margin-bottom:2rem;">
            <div class="card-header">
                <div class="card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="var(--accent-green)"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                    Comparador Interactivo: ANTES &larr;&rarr; DESPUÉS
                </div>
                <span style="font-size:0.85rem; color:var(--text-muted);">Arrastrá el control al centro para comparar</span>
            </div>

            <div class="comparison-slider-container">
                <!-- Imagen Antes -->
                <img src="<?= BASE_URL ?>/assets/img/demo-before.svg" alt="Antes" class="comparison-image comparison-before">

                <!-- Imagen Después (revelada por el contenedor cortado) -->
                <div class="comparison-after-wrapper">
                    <img src="<?= BASE_URL ?>/assets/img/demo-after.svg" alt="Después" class="comparison-image">
                </div>

                <!-- Tirador Deslizante -->
                <div class="comparison-handle">&harr;</div>
            </div>
        </div>

        <!-- Tabla Histórica de Medidas Corporales -->
        <div class="card" style="margin-bottom:2rem;">
            <div class="card-header">
                <div class="card-title">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="var(--accent-green)"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
                    Historial de Medidas Corporales
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="openModal('modal_add_measurement')">+ Registrar Hoy</button>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Peso (kg)</th>
                            <th>Grasa (%)</th>
                            <th>Cintura (cm)</th>
                            <th>Pecho (cm)</th>
                            <th>Brazo (cm)</th>
                            <th>Muslo (cm)</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($medidas)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:2rem; color:var(--text-muted);">
                                    Todavía no registraste medidas corporales. Empezá hoy para ver tu progreso en números.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($medidas as $m): ?>
                                <tr>
                                    <td><strong><?= date('d/m/Y', strtotime($m['fecha'])) ?></strong></td>
                                    <td><strong style="color:var(--accent-green);"><?= (float)$m['peso'] ?> kg</strong></td>
                                    <td><?= $m['grasa_corporal'] ? (float)$m['grasa_corporal'] . '%' : '-' ?></td>
                                    <td><?= $m['cintura'] ? (float)$m['cintura'] . ' cm' : '-' ?></td>
                                    <td><?= $m['pecho'] ? (float)$m['pecho'] . ' cm' : '-' ?></td>
                                    <td><?= $m['brazo_der'] ? (float)$m['brazo_der'] . ' cm' : '-' ?></td>
                                    <td><?= $m['muslo_der'] ? (float)$m['muslo_der'] . ' cm' : '-' ?></td>
                                    <td style="font-size:0.8rem; font-style:italic;"><?= htmlspecialchars($m['notas'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- MODAL: REGISTRAR NUEVA MEDIDA -->
    <div class="modal-backdrop" id="modal_add_measurement">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Registrar Medidas Corporales</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal_add_measurement')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="measurement_form" onsubmit="event.preventDefault(); submitBodyMeasurement();">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Fecha:</label>
                            <input type="date" id="measure_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Peso Corporal (kg):</label>
                            <input type="number" step="0.1" id="measure_weight" class="form-input" value="<?= (float)$user['peso_actual'] ?>" required>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Grasa Corporal (% opcional):</label>
                            <input type="number" step="0.1" id="measure_fat" class="form-input" placeholder="Ej: 16.5">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cintura (cm):</label>
                            <input type="number" step="0.5" id="measure_waist" class="form-input" placeholder="Ej: 83.5">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Pecho (cm):</label>
                            <input type="number" step="0.5" id="measure_chest" class="form-input" placeholder="Ej: 106">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Brazo (cm):</label>
                            <input type="number" step="0.5" id="measure_arm" class="form-input" placeholder="Ej: 38.5">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Muslo (cm):</label>
                            <input type="number" step="0.5" id="measure_thigh" class="form-input" placeholder="Ej: 58">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;">
                        Guardar Medidas
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: SUBIR FOTOGRAFÍA DE PROGRESO -->
    <div class="modal-backdrop" id="modal_add_photo">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Subir Fotografía de Progreso</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal_add_photo')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="progress_photo_form" onsubmit="uploadProgressPhotoForm(event)">
                    <div class="form-group">
                        <label class="form-label">Seleccioná la imagen:</label>
                        <input type="file" id="photo_file" name="foto" class="form-input" accept="image/*" required>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Categoría:</label>
                            <select name="categoria" class="form-select">
                                <option value="frente">Frente</option>
                                <option value="espalda">Espalda</option>
                                <option value="perfil_izquierdo">Perfil Izquierdo</option>
                                <option value="perfil_derecho">Perfil Derecho</option>
                                <option value="personalizada">Personalizada</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha:</label>
                            <input type="date" name="fecha" class="form-input" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notas o sensaciones:</label>
                        <input type="text" name="notas" class="form-input" placeholder="Ej: Semana 6 de definición">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;">
                        Guardar Fotografía
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script src="<?= BASE_URL ?>/assets/js/progress.js"></script>
</div>
