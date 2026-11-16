<?php
/**
 * GYMRAT — Módulo Completo de Nutrición & Diario de Comidas
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();

// Fecha seleccionada (por defecto hoy)
$selectedDate = $_GET['fecha'] ?? date('Y-m-d');
$prevDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($selectedDate . ' +1 day'));

// Obtener registros de comidas para la fecha
$stmt = $pdo->prepare("
    SELECT rc.*, a.nombre as alimento_nombre, a.marca as alimento_marca 
    FROM registro_comidas rc
    LEFT JOIN alimentos a ON rc.alimento_id = a.id
    WHERE rc.usuario_id = ? AND rc.fecha = ?
    ORDER BY rc.id ASC
");
$stmt->execute([$user['id'], $selectedDate]);
$comidasRows = $stmt->fetchAll();

// Agrupar por tipo de comida
$comidasAgrupadas = [
    'desayuno'     => [],
    'media_manana' => [],
    'almuerzo'     => [],
    'merienda'     => [],
    'cena'         => [],
    'otros'        => []
];

$diaCal = 0;
$diaProt = 0;
$diaCarb = 0;
$diaGras = 0;

foreach ($comidasRows as $c) {
    $tipo = $c['tipo_comida'];
    if (!isset($comidasAgrupadas[$tipo])) {
        $comidasAgrupadas[$tipo] = [];
    }
    $comidasAgrupadas[$tipo][] = $c;

    $diaCal += (float)$c['calorias'];
    $diaProt += (float)$c['proteinas'];
    $diaCarb += (float)$c['carbohidratos'];
    $diaGras += (float)$c['grasas'];
}

$calMeta = (int)($user['calorias_objetivo'] ?? 2400);
$protMeta = (int)($user['proteinas_objetivo'] ?? 190);
$carbMeta = (int)($user['carbohidratos_objetivo'] ?? 240);
$grasMeta = (int)($user['grasas_objetivo'] ?? 70);

$calPorcentaje = ($calMeta > 0) ? min(100, (int)round(($diaCal / $calMeta) * 100)) : 0;
$protPorcentaje = ($protMeta > 0) ? min(100, (int)round(($diaProt / $protMeta) * 100)) : 0;
$carbPorcentaje = ($carbMeta > 0) ? min(100, (int)round(($diaCarb / $carbMeta) * 100)) : 0;
$grasPorcentaje = ($grasMeta > 0) ? min(100, (int)round(($diaGras / $grasMeta) * 100)) : 0;

$pageTitle = 'Nutrición & Macros — GYMRAT';
$pageSubtitle = 'Seguimiento exacto de calorías, macronutrientes y diario de comidas';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <input type="hidden" id="current_diary_date" value="<?= htmlspecialchars($selectedDate) ?>">

    <main class="page-body">
        <!-- Barra de Control de Fecha y Acciones Rápidas -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.75rem;">
            <!-- Selector de Fecha -->
            <div style="display:flex; align-items:center; gap:0.5rem; background:var(--bg-card); padding:0.4rem 0.75rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                <a href="?fecha=<?= $prevDate ?>" class="btn-icon" style="color:var(--text-muted);">&larr;</a>
                <span style="font-weight:700; font-size:1rem; padding:0 0.5rem;">
                    <?= ($selectedDate === date('Y-m-d')) ? 'Hoy, ' : '' ?><?= date('d M Y', strtotime($selectedDate)) ?>
                </span>
                <a href="?fecha=<?= $nextDate ?>" class="btn-icon" style="color:var(--text-muted);">&rarr;</a>
            </div>

            <!-- Botones de Acción de Nutrición -->
            <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="copyYesterdayMeals()">
                    ⚡ Copiar de ayer
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="openBarcodeScanner()">
                    🏷️ Escanear Código
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="openAIFoodAnalysis()">
                    📷 Analizar con IA
                </button>
            </div>
        </div>

        <!-- Resumen Calórico y de Macros del Día -->
        <div class="card card-glow" style="margin-bottom:2rem;">
            <div style="display:grid; grid-template-columns: 1.2fr 2fr; gap:2rem; align-items:center;">
                <!-- Total Calorías -->
                <div style="text-align:center; border-right:1px solid var(--border-subtle); padding-right:1.5rem;">
                    <div style="font-size:0.8rem; text-transform:uppercase; font-weight:700; color:var(--text-muted);">Calorías Totales</div>
                    <div style="font-family:var(--font-heading); font-size:2.8rem; font-weight:900; line-height:1.1; color:var(--text-primary);">
                        <?= number_format($diaCal, 0, ',', '.') ?>
                    </div>
                    <div style="font-size:0.9rem; color:var(--text-secondary); margin-bottom:0.75rem;">
                        Objetivo: <?= number_format($calMeta, 0, ',', '.') ?> kcal
                    </div>
                    <div class="progress-bar-container" style="max-width:260px; margin:0 auto;">
                        <div class="progress-bar-fill green" style="width: <?= min(100, round(($diaCal / max(1, $calMeta)) * 100)) ?>%;"></div>
                    </div>
                </div>

                <!-- Desglose de Macronutrientes -->
                <div class="macro-grid">
                    <div class="macro-card">
                        <div class="macro-name"><span class="dot protein"></span> Proteínas</div>
                        <div class="macro-values"><?= round($diaProt, 1) ?>g <span>/ <?= $protMeta ?>g</span></div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill green" style="width: <?= min(100, round(($diaProt / max(1, $protMeta)) * 100)) ?>%;"></div>
                        </div>
                    </div>
                    <div class="macro-card">
                        <div class="macro-name"><span class="dot carbs"></span> Carbohidratos</div>
                        <div class="macro-values"><?= round($diaCarb, 1) ?>g <span>/ <?= $carbMeta ?>g</span></div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill blue" style="width: <?= min(100, round(($diaCarb / max(1, $carbMeta)) * 100)) ?>%;"></div>
                        </div>
                    </div>
                    <div class="macro-card">
                        <div class="macro-name"><span class="dot fats"></span> Grasas</div>
                        <div class="macro-values"><?= round($diaGras, 1) ?>g <span>/ <?= $grasMeta ?>g</span></div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill amber" style="width: <?= min(100, round(($diaGras / max(1, $grasMeta)) * 100)) ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secciones de Comidas del Día -->
        <?php
        $categoriasInfo = [
            'desayuno'     => ['nombre' => 'Desayuno', 'icono' => '🍳'],
            'media_manana' => ['nombre' => 'Media Mañana', 'icono' => '🍏'],
            'almuerzo'     => ['nombre' => 'Almuerzo', 'icono' => '🍗'],
            'merienda'     => ['nombre' => 'Merienda', 'icono' => '🥪'],
            'cena'         => ['nombre' => 'Cena', 'icono' => '🥗'],
            'otros'        => ['nombre' => 'Otros / Snacks', 'icono' => '🥜']
        ];
        ?>

        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <?php foreach ($categoriasInfo as $key => $info): ?>
                <?php
                $items = $comidasAgrupadas[$key] ?? [];
                $subCal = 0; $subProt = 0; $subCarb = 0; $subGras = 0;
                foreach ($items as $it) {
                    $subCal += (float)$it['calorias'];
                    $subProt += (float)$it['proteinas'];
                    $subCarb += (float)$it['carbohidratos'];
                    $subGras += (float)$it['grasas'];
                }
                ?>
                <div class="card" id="meal_section_<?= $key ?>">
                    <div class="card-header" style="margin-bottom:0.75rem;">
                        <div class="card-title">
                            <span style="font-size:1.3rem;"><?= $info['icono'] ?></span>
                            <?= $info['nombre'] ?>
                            <span style="font-size:0.85rem; font-weight:500; color:var(--text-muted); margin-left:0.5rem;">
                                <?= number_format($subCal, 0, ',', '.') ?> kcal
                            </span>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="openAddFoodModal('<?= $key ?>')">
                            + Agregar Alimento
                        </button>
                    </div>

                    <?php if (empty($items)): ?>
                        <div style="padding:1.25rem 0.5rem; color:var(--text-muted); font-size:0.85rem; text-align:center; border:1px dashed var(--border-subtle); border-radius:var(--radius-md);">
                            No hay alimentos registrados en este momento.
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Alimento</th>
                                        <th>Porción</th>
                                        <th>Calorías</th>
                                        <th>Proteínas</th>
                                        <th>Carbos</th>
                                        <th>Grasas</th>
                                        <th style="width:40px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr id="meal_item_<?= $item['id'] ?>">
                                            <td>
                                                <div style="color:#ffffff; font-weight:700; font-size:0.95rem; line-height:1.3;"><?= htmlspecialchars($item['nombre_personalizado'] ?: ($item['alimento_nombre'] ?? 'Alimento')) ?></div>
                                                <?php if (!empty($item['alimento_marca'])): ?>
                                                    <span style="display:block; font-size:0.8rem; color:#94a3b8; margin-top:2px; font-weight:500;"><?= htmlspecialchars($item['alimento_marca']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="color:#e2e8f0; font-weight:500; font-size:0.9rem;"><?= (float)$item['cantidad'] ?> <?= htmlspecialchars($item['unidad'] ?? 'g') ?></td>
                                            <td><strong style="color:#00f59b; font-size:0.95rem; font-weight:700;"><?= round((float)$item['calorias']) ?> kcal</strong></td>
                                            <td style="color:#00f59b; font-weight:700; font-size:0.9rem;"><?= (float)$item['proteinas'] ?> g</td>
                                            <td style="color:#38bdf8; font-weight:700; font-size:0.9rem;"><?= (float)$item['carbohidratos'] ?> g</td>
                                            <td style="color:#f59e0b; font-weight:700; font-size:0.9rem;"><?= (float)$item['grasas'] ?> g</td>
                                            <td style="text-align:right;">
                                                <button type="button" class="btn-icon" style="background:none; border:none; color:var(--text-muted); cursor:pointer;" title="Eliminar alimento" onclick="deleteMealItem(<?= $item['id'] ?>)">
                                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- MODAL 1: BUSCADOR DE ALIMENTOS Y REGISTRO -->
    <div class="modal-backdrop" id="modal_add_food">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Agregar a <span id="modal_meal_name" style="color:var(--accent-green);">Comida</span></h3>
                <button type="button" class="modal-close" onclick="closeModal('modal_add_food')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="input-icon-wrapper">
                        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                        <input type="text" id="food_search_input" class="form-input" placeholder="Buscar alimento (ej. Pollo, Arroz, Avena, Banana...)" oninput="onFoodSearchInput(this.value)">
                    </div>
                </div>

                <!-- Lista de Resultados Dinámicos -->
                <div id="food_search_results" style="max-height:240px; overflow-y:auto; border:1px solid var(--border-color); border-radius:var(--radius-md); margin-bottom:1.25rem; background:var(--bg-secondary);">
                    <!-- Se llena por JavaScript vía API -->
                </div>

                <!-- Detalles y Selector de Porción del Alimento Seleccionado -->
                <div id="food_selected_details" style="display:none; background:var(--bg-card); border-radius:var(--radius-md); padding:1.25rem; border:1px solid var(--border-color); box-shadow:var(--shadow-sm);">
                    <h4 id="selected_food_name" style="margin-bottom:0.25rem; font-size:1.1rem; color:#ffffff; font-weight:700;">Alimento</h4>
                    <p id="selected_food_portion" style="font-size:0.85rem; color:#94a3b8; margin-bottom:1rem; font-weight:500;">Porción</p>

                    <div class="form-group" style="margin-bottom:1rem;">
                        <label class="form-label" style="font-size:0.9rem; color:#e2e8f0; font-weight:600;">Cantidad a registrar (gramos / ml):</label>
                        <input type="number" id="selected_food_qty" class="form-input" value="100" min="1" step="1" oninput="calculateSelectedFoodMacros()">
                    </div>

                    <!-- Vista previa de macros calculados -->
                    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:0.5rem; text-align:center; margin-bottom:1.25rem;">
                        <div style="background:var(--bg-input); padding:0.6rem 0.4rem; border-radius:var(--radius-sm); border:1px solid var(--border-subtle);">
                            <div style="font-size:0.7rem; font-weight:700; letter-spacing:0.04em; color:#94a3b8; margin-bottom:0.2rem;">CALORÍAS</div>
                            <strong id="calc_food_cal" style="color:#ffffff; font-size:0.95rem;">0 kcal</strong>
                        </div>
                        <div style="background:var(--bg-input); padding:0.6rem 0.4rem; border-radius:var(--radius-sm); border:1px solid var(--border-subtle);">
                            <div style="font-size:0.7rem; font-weight:700; letter-spacing:0.04em; color:var(--accent-green); margin-bottom:0.2rem;">PROTEÍNAS</div>
                            <strong id="calc_food_p" style="color:var(--accent-green); font-size:0.95rem;">0 g</strong>
                        </div>
                        <div style="background:var(--bg-input); padding:0.6rem 0.4rem; border-radius:var(--radius-sm); border:1px solid var(--border-subtle);">
                            <div style="font-size:0.7rem; font-weight:700; letter-spacing:0.04em; color:var(--accent-blue); margin-bottom:0.2rem;">CARBOS</div>
                            <strong id="calc_food_c" style="color:var(--accent-blue); font-size:0.95rem;">0 g</strong>
                        </div>
                        <div style="background:var(--bg-input); padding:0.6rem 0.4rem; border-radius:var(--radius-sm); border:1px solid var(--border-subtle);">
                            <div style="font-size:0.7rem; font-weight:700; letter-spacing:0.04em; color:var(--accent-amber); margin-bottom:0.2rem;">GRASAS</div>
                            <strong id="calc_food_f" style="color:var(--accent-amber); font-size:0.95rem;">0 g</strong>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" style="width:100%;" onclick="submitAddFoodToMeal()">
                        + Guardar en Mi Diario
                    </button>
                </div>
            </div>
            <div class="modal-footer" style="justify-content:space-between;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modal_add_food'); openModal('modal_create_food');">
                    + Crear Alimento Propio
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modal_add_food')">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: SIMULACIÓN DE ESCÁNER DE CÓDIGO DE BARRAS -->
    <div class="modal-backdrop" id="modal_barcode_scanner">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Escanear Alimento</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal_barcode_scanner')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <!-- Visor Simulado de Cámara de Escáner -->
                <div style="height:180px; background:#080b11; border:2px dashed var(--accent-green); border-radius:var(--radius-md); display:flex; flex-direction:column; align-items:center; justify-content:center; position:relative; overflow:hidden; margin-bottom:1.25rem;">
                    <div style="position:absolute; width:100%; height:2px; background:var(--accent-green); box-shadow:0 0 10px var(--accent-green); animation:scanLine 2s infinite ease-in-out;"></div>
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="var(--text-muted)" style="margin-bottom:0.5rem;"><path d="M2 4h2v16H2zm4 0h1v16H6zm3 0h2v16H9zm4 0h1v16h-1zm3 0h2v16h-2zm4 0h2v16h-2z"/></svg>
                    <span id="barcode_scanner_status" style="font-size:0.85rem; color:var(--text-secondary); text-align:center; padding:0 1rem;">Apuntá la cámara o ingresá el código de barras manualmente</span>
                </div>

                <style>
                    @keyframes scanLine {
                        0% { top: 10%; }
                        50% { top: 90%; }
                        100% { top: 10%; }
                    }
                </style>

                <div class="form-group">
                    <label class="form-label">Código de Barras:</label>
                    <div style="display:flex; gap:0.5rem;">
                        <input type="text" id="barcode_input" class="form-input" placeholder="Ej: 779001000001">
                        <button type="button" class="btn btn-primary btn-sm" onclick="simulateBarcodeScan()">Buscar</button>
                    </div>
                </div>

                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1rem;">
                    Probar con códigos de demostración de la base de datos:
                    <div style="display:flex; gap:0.4rem; margin-top:0.4rem; flex-wrap:wrap;">
                        <button type="button" class="btn btn-secondary btn-sm" style="font-size:0.75rem;" onclick="simulateBarcodeScan('779001000001')">Pechuga Pollo (779001000001)</button>
                        <button type="button" class="btn btn-secondary btn-sm" style="font-size:0.75rem;" onclick="simulateBarcodeScan('779001000005')">Avena Quaker (779001000005)</button>
                        <button type="button" class="btn btn-secondary btn-sm" style="font-size:0.75rem;" onclick="simulateBarcodeScan('779001000015')">Whey Protein (779001000015)</button>
                    </div>
                </div>

                <!-- Tarjeta de Resultado de Escaneo -->
                <div id="barcode_result_card" style="display:none; background:var(--bg-secondary); border-radius:var(--radius-md); padding:1rem; border:1px solid var(--accent-green);">
                    <h4 id="barcode_food_name" style="color:var(--accent-green); margin-bottom:0.2rem;">Alimento</h4>
                    <p id="barcode_food_details" style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.4rem;">Detalles</p>
                    <p id="barcode_food_macros" style="font-size:0.85rem; font-weight:700; margin-bottom:0.75rem;">Macros</p>
                    <button type="button" class="btn btn-primary btn-sm" style="width:100%;" onclick="closeModal('modal_barcode_scanner'); openAddFoodModal(currentMealType); selectFood(selectedFood);">
                        Continuar y Agregar Porción &rarr;
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modal_barcode_scanner')">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- MODAL 3: SIMULACIÓN DE ANÁLISIS FOTOGRÁFICO DE COMIDA CON IA -->
    <div class="modal-backdrop" id="modal_ai_analysis">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Analizar Plato con IA 🧠</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal_ai_analysis')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;">
                    Subí una foto de tu plato para segmentar ingredientes, estimar porciones y calcular automáticamente calorías y macronutrientes.
                </p>

                <div class="form-group">
                    <input type="file" id="ai_file_input" accept="image/*" class="form-input" onchange="handleAIImageUpload(this)">
                </div>

                <div id="ai_image_preview" style="display:none; text-align:center; margin-bottom:1rem;">
                    <img id="ai_preview_img" src="" alt="Vista previa" style="max-height:180px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                </div>

                <div id="ai_analysis_loading" style="display:none; text-align:center; padding:1.5rem 0;">
                    <span class="spinner" style="width:32px; height:32px;"></span>
                    <p style="font-size:0.9rem; margin-top:0.75rem; color:var(--accent-green);">Segmentando alimentos y analizando volumen con visión artificial...</p>
                </div>

                <!-- Resultados Editables -->
                <div id="ai_detected_results" style="display:none;">
                    <div style="background:var(--bg-secondary); border-radius:var(--radius-md); padding:1rem; border:1px solid var(--border-color); margin-bottom:1rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                            <strong style="color:var(--accent-green);">Análisis Completado</strong>
                            <span id="ai_total_cal" style="font-weight:800; font-size:1.1rem;">720 kcal</span>
                        </div>
                        <div style="display:flex; gap:1rem; font-size:0.85rem; color:var(--text-muted); margin-bottom:0.75rem;">
                            <span>P: <strong id="ai_total_p" style="color:var(--text-primary);">52g</strong></span>
                            <span>C: <strong id="ai_total_c" style="color:var(--text-primary);">68g</strong></span>
                            <span>G: <strong id="ai_total_f" style="color:var(--text-primary);">22g</strong></span>
                        </div>
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:0.5rem;">Podés editar los gramos detectados libremente:</div>
                        <div id="ai_items_list">
                            <!-- Filas de alimentos generadas por nutrition.js -->
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" style="width:100%;" onclick="saveAIDetectedMeal()">
                        ✓ Guardar en Mi Diario
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modal_ai_analysis')">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: CREAR ALIMENTO PERSONALIZADO -->
    <div class="modal-backdrop" id="modal_create_food">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Crear Alimento Propio</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal_create_food')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="create_food_form" onsubmit="submitCustomFood(event)">
                    <div class="form-group">
                        <label class="form-label">Nombre del Alimento:</label>
                        <input type="text" id="custom_food_name" class="form-input" placeholder="Ej: Barra de proteína casera" required>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Marca (opcional):</label>
                            <input type="text" id="custom_food_brand" class="form-input" placeholder="Ej: Casero">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Porción base (g / ml):</label>
                            <input type="number" id="custom_food_portion" class="form-input" value="100" required>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:0.5rem;">
                        <div class="form-group">
                            <label class="form-label">Calorías</label>
                            <input type="number" step="0.1" id="custom_food_cal" class="form-input" placeholder="kcal" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Proteínas (g)</label>
                            <input type="number" step="0.1" id="custom_food_p" class="form-input" placeholder="g" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Carbos (g)</label>
                            <input type="number" step="0.1" id="custom_food_c" class="form-input" placeholder="g" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Grasas (g)</label>
                            <input type="number" step="0.1" id="custom_food_f" class="form-input" placeholder="g" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;">
                        Guardar Alimento en Mi Base
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modal_create_food')">Cancelar</button>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script src="<?= BASE_URL ?>/assets/js/nutrition.js"></script>
    <script>
    async function submitCustomFood(e) {
        e.preventDefault();
        const payload = {
            nombre: document.getElementById('custom_food_name').value,
            marca: document.getElementById('custom_food_brand').value,
            tamano_porcion: parseFloat(document.getElementById('custom_food_portion').value),
            calorias: parseFloat(document.getElementById('custom_food_cal').value),
            proteinas: parseFloat(document.getElementById('custom_food_p').value),
            carbohidratos: parseFloat(document.getElementById('custom_food_c').value),
            grasas: parseFloat(document.getElementById('custom_food_f').value)
        };
        const res = await fetchAPI('api/alimentos/crear.php', 'POST', payload);
        if (res.success) {
            showToast('✓ Alimento creado', `${payload.nombre} guardado en tus alimentos.`);
            closeModal('modal_create_food');
            openAddFoodModal(currentMealType);
        } else {
            showToast('Error', res.message || 'No se pudo crear el alimento.', 'error');
        }
    }
    </script>
</div>
