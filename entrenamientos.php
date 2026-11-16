<?php
/**
 * GYMRAT — Módulo de Entrenamiento, Rutinas, Ejercicios e Historial
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();

// 1. Obtener Rutinas del usuario y plantillas
$rutinasStmt = $pdo->prepare("
    SELECT r.*, COUNT(DISTINCT rd.id) as total_dias 
    FROM rutinas r
    LEFT JOIN rutina_dias rd ON r.id = rd.rutina_id
    WHERE r.usuario_id = ? OR r.es_plantilla = 1
    GROUP BY r.id
    ORDER BY r.es_plantilla DESC, r.id DESC
");
$rutinasStmt->execute([$user['id']]);
$rutinas = $rutinasStmt->fetchAll();

// 2. Obtener Historial de Entrenamientos completados
$historialStmt = $pdo->prepare("
    SELECT e.*, r.nombre as rutina_nombre 
    FROM entrenamientos e
    LEFT JOIN rutinas r ON e.rutina_id = r.id
    WHERE e.usuario_id = ? AND e.estado = 'completado'
    ORDER BY e.fecha DESC, e.id DESC 
    LIMIT 20
");
$historialStmt->execute([$user['id']]);
$historial = $historialStmt->fetchAll();

// 3. Obtener Ejercicios con filtros
$categoriaFiltro = $_GET['categoria'] ?? '';
$sqlEx = "SELECT * FROM ejercicios";
$paramsEx = [];
if (!empty($categoriaFiltro)) {
    $sqlEx .= " WHERE categoria = ?";
    $paramsEx[] = $categoriaFiltro;
}
$sqlEx .= " ORDER BY musculo_principal ASC, nombre ASC";
$ejerciciosStmt = $pdo->prepare($sqlEx);
$ejerciciosStmt->execute($paramsEx);
$ejercicios = $ejerciciosStmt->fetchAll();

$pageTitle = 'Entrenamiento & Rutinas — GYMRAT';
$pageSubtitle = 'Sobrecarga progresiva, biblioteca técnica de ejercicios y registro de sesiones';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-body">
        <!-- Encabezado con Botón de Iniciar Sesión Libre -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
            <div>
                <h2>Centro de Entrenamiento</h2>
                <p>Elegí una rutina, creá una nueva división o iniciá un entrenamiento libre inmediato.</p>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="openModal('modal_create_routine')">
                    + Nueva Rutina
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="startFreeWorkout()">
                    ⚡ Iniciar Entrenamiento Libre
                </button>
            </div>
        </div>

        <!-- Pestañas de Navegación del Módulo -->
        <div class="tab-group">
            <div class="tab-item active" onclick="switchTrainingTab('tab_rutinas', this)">
                Mis Rutinas & Plantillas
            </div>
            <div class="tab-item" onclick="switchTrainingTab('tab_ejercicios', this)">
                Biblioteca de Ejercicios (<?= count($ejercicios) ?>)
            </div>
            <div class="tab-item" onclick="switchTrainingTab('tab_historial', this)">
                Historial de Sesiones (<?= count($historial) ?>)
            </div>
        </div>

        <!-- TAB 1: RUTINAS -->
        <div id="tab_rutinas" class="training-tab-content">
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:1.5rem;">
                <?php foreach ($rutinas as $r): ?>
                    <div class="card card-glow" style="display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.75rem;">
                                <div>
                                    <h3 style="font-size:1.2rem; margin-bottom:0.25rem;"><?= htmlspecialchars($r['nombre']) ?></h3>
                                    <?php if ($r['es_plantilla']): ?>
                                        <span class="badge badge-green" style="font-size:0.65rem;">Plantilla Oficial</span>
                                    <?php else: ?>
                                        <span class="badge badge-blue" style="font-size:0.65rem;">Personalizada</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p style="font-size:0.85rem; margin-bottom:1rem;"><?= htmlspecialchars($r['descripcion'] ?: 'Rutina balanceada de hipertrofia y fuerza.') ?></p>
                            <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1.25rem;">
                                📅 <?= htmlspecialchars($r['dias_semana'] ?: 'Flexible') ?> • <?= (int)$r['total_dias'] ?> Días configurados
                            </div>
                        </div>

                        <div style="display:flex; gap:0.5rem; border-top:1px solid var(--border-subtle); padding-top:1rem;">
                            <a href="<?= BASE_URL ?>/workout_en_vivo.php?rutina=<?= $r['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;">
                                ▶ Entrenar
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm" title="Duplicar rutina" onclick="duplicateRoutine(<?= $r['id'] ?>)">
                                📋 Duplicar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB 2: BIBLIOTECA DE EJERCICIOS -->
        <div id="tab_ejercicios" class="training-tab-content" style="display:none;">
            <!-- Filtros por Grupo Muscular -->
            <div style="display:flex; gap:0.5rem; overflow-x:auto; padding-bottom:1rem; margin-bottom:1.5rem;">
                <?php
                $categorias = [
                    '' => 'Todos',
                    'pecho' => 'Pecho',
                    'espalda' => 'Espalda',
                    'hombros' => 'Hombros',
                    'cuadriceps' => 'Cuádriceps',
                    'femorales' => 'Femorales',
                    'gluteos' => 'Glúteos',
                    'biceps' => 'Bíceps',
                    'triceps' => 'Tríceps',
                    'abdominales' => 'Core/Abdomen',
                    'gemelos' => 'Gemelos',
                    'cardio' => 'Cardio'
                ];
                foreach ($categorias as $catKey => $catLabel):
                    $activeCat = ($categoriaFiltro === $catKey) ? 'btn-primary' : 'btn-secondary';
                ?>
                    <a href="?categoria=<?= urlencode($catKey) ?>#tab_ejercicios" class="btn <?= $activeCat ?> btn-sm" style="white-space:nowrap;">
                        <?= $catLabel ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1.25rem;">
                <?php foreach ($ejercicios as $ex): ?>
                    <div class="card" style="cursor:pointer;" onclick="openExerciseDetail(<?= htmlspecialchars(json_encode($ex), ENT_QUOTES, 'UTF-8') ?>)">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem;">
                            <strong style="font-size:1.05rem; color:var(--text-primary);"><?= htmlspecialchars($ex['nombre']) ?></strong>
                            <span class="badge badge-green" style="font-size:0.65rem; text-transform:capitalize;"><?= $ex['equipamiento'] ?></span>
                        </div>
                        <div style="font-size:0.85rem; color:var(--accent-green); font-weight:600; margin-bottom:0.4rem;">
                            🎯 <?= htmlspecialchars($ex['musculo_principal']) ?>
                        </div>
                        <?php if ($ex['musculos_secundarios']): ?>
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:0.75rem;">
                                Secundarios: <?= htmlspecialchars($ex['musculos_secundarios']) ?>
                            </div>
                        <?php endif; ?>
                        <div style="font-size:0.8rem; color:var(--text-secondary); line-height:1.4;">
                            <?= htmlspecialchars(mb_substr($ex['instrucciones'] ?? $ex['descripcion'] ?? '', 0, 90)) ?>...
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB 3: HISTORIAL DE ENTRENAMIENTOS -->
        <div id="tab_historial" class="training-tab-content" style="display:none;">
            <?php if (empty($historial)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg viewBox="0 0 24 24"><path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29z"/></svg>
                    </div>
                    <div class="empty-state-title">Todavía no tenés entrenamientos registrados</div>
                    <div class="empty-state-desc">Comenzá tu primera sesión para llevar el registro de tus pesos, series y récords personales.</div>
                    <button type="button" class="btn btn-primary" onclick="startFreeWorkout()">+ Iniciar Mi Primer Entrenamiento</button>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:1rem;">
                    <?php foreach ($historial as $h): ?>
                        <div class="card" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                            <div>
                                <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.25rem;">
                                    <h4 style="margin:0; font-size:1.1rem;"><?= htmlspecialchars($h['nombre']) ?></h4>
                                    <?php if ($h['prs_conseguidos'] > 0): ?>
                                        <span class="badge badge-amber">🔥 <?= (int)$h['prs_conseguidos'] ?> PRs</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:0.85rem; color:var(--text-muted);">
                                    📅 <?= date('d/m/Y', strtotime($h['fecha'])) ?> 
                                    • ⏱️ <?= gmdate("H\h i\m", (int)$h['duracion_segundos']) ?>
                                    • <?= (int)$h['series_totales'] ?> series
                                    • <strong style="color:var(--accent-green);"><?= number_format((float)$h['volumen_total'], 0, ',', '.') ?> kg</strong> volumen
                                </div>
                                <?php if ($h['notas']): ?>
                                    <div style="font-size:0.8rem; color:var(--text-secondary); margin-top:0.4rem; font-style:italic;">
                                        "<?= htmlspecialchars($h['notas']) ?>"
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button type="button" class="btn btn-secondary btn-sm" onclick="repeatWorkout(<?= $h['id'] ?>)">
                                🔄 Repetir Entrenamiento
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- MODAL: DETALLE TÉCNICO DE EJERCICIO -->
    <div class="modal-backdrop" id="modal_exercise_detail">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="detail_ex_name">Ejercicio</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal_exercise_detail')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div style="display:flex; gap:0.5rem; margin-bottom:1rem;">
                    <span id="detail_ex_main_muscle" class="badge badge-green">Músculo</span>
                    <span id="detail_ex_equipment" class="badge badge-blue">Equipo</span>
                    <span id="detail_ex_difficulty" class="badge badge-amber">Dificultad</span>
                </div>

                <h4 style="font-size:0.95rem; margin-bottom:0.35rem; color:var(--text-primary);">Técnica de Ejecución:</h4>
                <div id="detail_ex_instructions" style="font-size:0.85rem; color:var(--text-secondary); white-space:pre-line; line-height:1.5; margin-bottom:1.25rem;"></div>

                <h4 style="font-size:0.95rem; margin-bottom:0.35rem; color:var(--accent-green);">Consejos Clave:</h4>
                <p id="detail_ex_cues" style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1.25rem;"></p>

                <h4 style="font-size:0.95rem; margin-bottom:0.35rem; color:var(--accent-rose);">Errores Frecuentes a Evitar:</h4>
                <p id="detail_ex_mistakes" style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:1rem;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modal_exercise_detail')">Entendido</button>
            </div>
        </div>
    </div>

    <!-- MODAL: CREAR RUTINA PERSONALIZADA -->
    <div class="modal-backdrop" id="modal_create_routine">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Crear Rutina Personalizada</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal_create_routine')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="create_routine_form" onsubmit="submitNewRoutine(event)">
                    <div class="form-group">
                        <label class="form-label">Nombre de la Rutina:</label>
                        <input type="text" id="routine_name" class="form-input" placeholder="Ej: Torso / Pierna Power" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descripción:</label>
                        <input type="text" id="routine_desc" class="form-input" placeholder="Ej: Enfoque en hipertrofia y progresión doble">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Días sugeridos:</label>
                        <input type="text" id="routine_days" class="form-input" placeholder="Ej: Lunes, Martes, Jueves, Viernes">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;">
                        Guardar Rutina
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script>
    function switchTrainingTab(tabId, el) {
        document.querySelectorAll('.training-tab-content').forEach(c => c.style.display = 'none');
        document.querySelectorAll('.tab-group .tab-item').forEach(t => t.classList.remove('active'));
        document.getElementById(tabId).style.display = 'block';
        el.classList.add('active');
    }

    // Si viene con hash #tab_ejercicios
    if (window.location.hash === '#tab_ejercicios') {
        const item = document.querySelectorAll('.tab-group .tab-item')[1];
        if (item) switchTrainingTab('tab_ejercicios', item);
    }

    function openExerciseDetail(ex) {
        document.getElementById('detail_ex_name').innerText = ex.nombre;
        document.getElementById('detail_ex_main_muscle').innerText = ex.musculo_principal;
        document.getElementById('detail_ex_equipment').innerText = ex.equipamiento;
        document.getElementById('detail_ex_difficulty').innerText = ex.dificultad;
        document.getElementById('detail_ex_instructions').innerText = ex.instrucciones || ex.descripcion || 'Sin instrucciones adicionales.';
        document.getElementById('detail_ex_cues').innerText = ex.consejos || 'Mantener siempre la técnica limpia y controlada.';
        document.getElementById('detail_ex_mistakes').innerText = ex.errores_frecuentes || 'Evitar balanceos y peso excesivo sin control.';
        openModal('modal_exercise_detail');
    }

    async function startFreeWorkout() {
        const res = await fetchAPI('api/entrenamientos/iniciar.php', 'POST', { nombre: 'Entrenamiento Libre' });
        if (res.success && res.data) {
            window.location.href = `workout_en_vivo.php?id=${res.data.entrenamiento_id}`;
        } else {
            showToast('Error', res.message || 'No se pudo iniciar el entrenamiento.', 'error');
        }
    }

    async function duplicateRoutine(routineId) {
        const res = await fetchAPI('api/rutinas/duplicar.php', 'POST', { rutina_id: routineId });
        if (res.success) {
            showToast('✓ Rutina duplicada', 'Se creó una copia editable en tus rutinas.');
            setTimeout(() => location.reload(), 400);
        } else {
            showToast('Error', res.message || 'No se pudo duplicar.', 'error');
        }
    }

    async function repeatWorkout(workoutId) {
        const res = await fetchAPI('api/entrenamientos/repetir.php', 'POST', { entrenamiento_id: workoutId });
        if (res.success && res.data) {
            showToast('✓ Sesión iniciada', 'Cargando entrenamiento basado en tu sesión previa...');
            setTimeout(() => {
                window.location.href = `workout_en_vivo.php?id=${res.data.nuevo_entrenamiento_id}`;
            }, 300);
        } else {
            showToast('Error', res.message || 'No se pudo repetir el entrenamiento.', 'error');
        }
    }

    async function submitNewRoutine(e) {
        e.preventDefault();
        const payload = {
            nombre: document.getElementById('routine_name').value,
            descripcion: document.getElementById('routine_desc').value,
            dias_semana: document.getElementById('routine_days').value
        };
        const res = await fetchAPI('api/rutinas/crear.php', 'POST', payload);
        if (res.success) {
            showToast('✓ Rutina Creada', `${payload.nombre} agregada con éxito.`);
            closeModal('modal_create_routine');
            setTimeout(() => location.reload(), 400);
        } else {
            showToast('Error', res.message || 'No se pudo guardar la rutina.', 'error');
        }
    }
    </script>
</div>
