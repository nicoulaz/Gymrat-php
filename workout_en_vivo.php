<?php
/**
 * GYMRAT — Modo Entrenamiento en Vivo (Live Workout Tracker)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();

$workoutId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rutinaId = isset($_GET['rutina']) ? (int)$_GET['rutina'] : null;

// Si no se pasó ID pero sí rutina, o viceversa, iniciar o buscar entrenamiento
if ($workoutId <= 0) {
    // Buscar si ya hay un entrenamiento en progreso
    $stmtActive = $pdo->prepare("SELECT id FROM entrenamientos WHERE usuario_id = ? AND estado = 'en_progreso' ORDER BY id DESC LIMIT 1");
    $stmtActive->execute([$user['id']]);
    $active = $stmtActive->fetch();

    if ($active) {
        $workoutId = (int)$active['id'];
    } else {
        // Crear nuevo entrenamiento
        $nombre = 'Entrenamiento Libre';
        if ($rutinaId) {
            $rStmt = $pdo->prepare("SELECT nombre FROM rutinas WHERE id = ?");
            $rStmt->execute([$rutinaId]);
            $nombre = $rStmt->fetchColumn() ?: 'Entrenamiento';
        }
        $ins = $pdo->prepare("INSERT INTO entrenamientos (usuario_id, rutina_id, nombre, fecha, hora_inicio, estado) VALUES (?, ?, ?, CURRENT_DATE, CURRENT_TIME, 'en_progreso')");
        $ins->execute([$user['id'], $rutinaId, $nombre]);
        $workoutId = (int)$pdo->lastInsertId();
    }
}

// Obtener datos del entrenamiento
$stmtW = $pdo->prepare("SELECT * FROM entrenamientos WHERE id = ? AND usuario_id = ?");
$stmtW->execute([$workoutId, $user['id']]);
$workout = $stmtW->fetch();

if (!$workout) {
    header("Location: " . BASE_URL . "/entrenamientos.php");
    exit;
}

// Calcular segundos transcurridos desde hora_inicio
$segundosInicio = 0;
if (!empty($workout['hora_inicio'])) {
    $timeStart = strtotime($workout['fecha'] . ' ' . $workout['hora_inicio']);
    $segundosInicio = max(0, time() - $timeStart);
}

// Obtener series ya registradas en esta sesión
$seriesStmt = $pdo->prepare("
    SELECT s.*, e.nombre as ejercicio_nombre, e.musculo_principal 
    FROM entrenamiento_series s
    JOIN ejercicios e ON s.ejercicio_id = e.id
    WHERE s.entrenamiento_id = ?
    ORDER BY s.ejercicio_id ASC, s.numero_serie ASC
");
$seriesStmt->execute([$workoutId]);
$seriesRegistradas = $seriesStmt->fetchAll();

// Si no hay series y el entrenamiento proviene de una rutina, precargar los ejercicios del Día 1
$ejerciciosIdsSesion = [];
foreach ($seriesRegistradas as $s) {
    if (!in_array($s['ejercicio_id'], $ejerciciosIdsSesion)) {
        $ejerciciosIdsSesion[] = $s['ejercicio_id'];
    }
}

if (empty($ejerciciosIdsSesion)) {
    if (!empty($workout['rutina_id'])) {
        // Cargar ejercicios de la rutina
        $rExStmt = $pdo->prepare("
            SELECT rde.ejercicio_id 
            FROM rutina_dia_ejercicios rde 
            JOIN rutina_dias rd ON rde.rutina_dia_id = rd.id 
            WHERE rd.rutina_id = ? 
            ORDER BY rd.orden ASC, rde.orden ASC 
            LIMIT 5
        ");
        $rExStmt->execute([$workout['rutina_id']]);
        $ejerciciosIdsSesion = $rExStmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Fallback: al menos los 3 ejercicios principales de Push
    if (empty($ejerciciosIdsSesion)) {
        $ejerciciosIdsSesion = [1, 2, 10]; // Banca plana, Inclinado mancuernas, Laterales
    }
}

// Cargar información completa de los ejercicios de la sesión
$inClause = implode(',', array_map('intval', $ejerciciosIdsSesion));
$exList = $pdo->query("SELECT * FROM ejercicios WHERE id IN ($inClause) ORDER BY FIELD(id, $inClause)")->fetchAll();

// Catálogo de todos los ejercicios para el selector "+ Agregar Ejercicio a la sesión"
$todosEjercicios = $pdo->query("SELECT id, nombre, musculo_principal FROM ejercicios ORDER BY nombre ASC")->fetchAll();

$pageTitle = 'Entrenamiento en Vivo — GYMRAT';
$pageSubtitle = htmlspecialchars($workout['nombre']) . ' • Registrá tus series con máxima velocidad';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <input type="hidden" id="current_workout_id" value="<?= $workoutId ?>">

    <main class="page-body">
        <!-- Barra de Control del Entrenamiento en Vivo -->
        <div class="card card-glow" style="margin-bottom:1.75rem; background:linear-gradient(135deg, var(--bg-card) 0%, rgba(0, 245, 155, 0.04) 100%);">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.25rem;">
                <div>
                    <span class="badge badge-green" style="margin-bottom:0.4rem;">● SESIÓN EN CURSO</span>
                    <h2 style="font-size:1.6rem; margin-bottom:0.25rem;"><?= htmlspecialchars($workout['nombre']) ?></h2>
                    <p style="font-size:0.85rem; margin:0;">Iniciado a las <?= htmlspecialchars($workout['hora_inicio'] ?? date('H:i')) ?> • Guardado automático continuo</p>
                </div>

                <!-- Cronómetro Gigante -->
                <div style="text-align:center;">
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--text-muted); letter-spacing:0.05em;">Tiempo Transcurrido</div>
                    <div id="workout_timer_display" style="font-family:var(--font-heading); font-size:2.5rem; font-weight:900; color:var(--accent-green); line-height:1;">
                        00:00:00
                    </div>
                </div>

                <!-- Métricas acumuladas en vivo y Botón Finalizar -->
                <div style="display:flex; align-items:center; gap:1.5rem;">
                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted);">VOLUMEN</div>
                        <strong id="live_total_volume" data-raw-volume="<?= (float)$workout['volumen_total'] ?>" style="font-size:1.25rem; color:var(--text-primary);">
                            <?= number_format((float)$workout['volumen_total'], 0, ',', '.') ?> kg
                        </strong>
                    </div>
                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted);">SERIES</div>
                        <strong id="live_total_sets" style="font-size:1.25rem; color:var(--accent-blue);">
                            <?= (int)$workout['series_totales'] ?>
                        </strong>
                    </div>
                    <button type="button" class="btn btn-primary" style="padding:0.75rem 1.4rem;" onclick="finishWorkout()">
                        ✓ Finalizar Sesión
                    </button>
                </div>
            </div>
        </div>

        <!-- Banner Flotante de Temporizador de Descanso (Countdown) -->
        <div id="rest_timer_widget" style="display:none; position:sticky; top:calc(var(--header-height) + 10px); z-index:45; background:rgba(18, 24, 38, 0.95); backdrop-filter:blur(12px); border:1px solid var(--accent-green); border-radius:var(--radius-md); padding:0.75rem 1.5rem; margin-bottom:1.5rem; justify-content:space-between; align-items:center; box-shadow:0 8px 30px rgba(0, 245, 155, 0.25);">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <span style="font-size:1.3rem;">⏳</span>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--text-muted);">Descanso entre series</div>
                    <div id="rest_timer_clock" style="font-family:var(--font-heading); font-size:1.75rem; font-weight:900; color:var(--accent-green); line-height:1;">
                        01:30
                    </div>
                </div>
            </div>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="addRestTime(30)">+30s</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addRestTime(60)">+60s</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="cancelRestTimer()">Saltar</button>
            </div>
        </div>

        <!-- Ejercicios del Entrenamiento -->
        <div id="workout_exercises_container" style="display:flex; flex-direction:column; gap:1.75rem;">
            <?php foreach ($exList as $ex): ?>
                <?php
                // Obtener series registradas para este ejercicio
                $seriesDeEx = array_filter($seriesRegistradas, fn($s) => $s['ejercicio_id'] == $ex['id']);

                // Obtener rendimiento de la última sesión para este ejercicio (Requerimiento 75 y 76)
                $lastSessStmt = $pdo->prepare("
                    SELECT s.peso, s.repeticiones, s.numero_serie 
                    FROM entrenamiento_series s 
                    JOIN entrenamientos e ON s.entrenamiento_id = e.id 
                    WHERE s.ejercicio_id = ? AND s.entrenamiento_id != ? AND e.usuario_id = ? 
                    ORDER BY e.fecha DESC, s.numero_serie ASC 
                    LIMIT 4
                ");
                $lastSessStmt->execute([$ex['id'], $workoutId, $user['id']]);
                $lastSessionSets = $lastSessStmt->fetchAll();
                ?>
                <div class="card" id="exercise_card_<?= $ex['id'] ?>">
                    <div class="card-header" style="margin-bottom:0.75rem;">
                        <div>
                            <h3 id="exercise_title_<?= $ex['id'] ?>" style="font-size:1.25rem; margin-bottom:0.25rem;">
                                <?= htmlspecialchars($ex['nombre']) ?>
                            </h3>
                            <span style="font-size:0.8rem; color:var(--accent-green); font-weight:600;">
                                <?= htmlspecialchars($ex['musculo_principal']) ?> • <?= htmlspecialchars($ex['equipamiento']) ?>
                            </span>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="startRestTimer(90)">
                            ⏱️ Descanso (90s)
                        </button>
                    </div>

                    <!-- Sugerencia y Memoria del Último Entrenamiento -->
                    <?php if (!empty($lastSessionSets)): ?>
                        <div style="background:var(--bg-secondary); border-radius:var(--radius-sm); padding:0.5rem 0.85rem; margin-bottom:1rem; font-size:0.8rem; color:var(--text-muted); display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                📌 <strong>Última sesión:</strong> 
                                <?php foreach ($lastSessionSets as $ls): ?>
                                    <span style="color:var(--text-primary); font-weight:600; margin-right:0.4rem;"><?= (float)$ls['peso'] ?>kg×<?= $ls['repeticiones'] ?></span>
                                <?php endforeach; ?>
                            </div>
                            <span style="color:var(--accent-amber); font-weight:700;">💡 Sugerencia: Intentá +1 rep o +1 kg hoy</span>
                        </div>
                    <?php endif; ?>

                    <!-- Tabla de Series del Ejercicio -->
                    <div class="table-container" style="margin-bottom:1rem;">
                        <table class="table" id="exercise_table_<?= $ex['id'] ?>">
                            <thead>
                                <tr>
                                    <th style="width:45px; text-align:center;">Serie</th>
                                    <th style="width:140px;">Tipo</th>
                                    <th>Peso (kg)</th>
                                    <th>Reps</th>
                                    <th>RIR</th>
                                    <th style="width:60px; text-align:center;">✓</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($seriesDeEx)): ?>
                                    <?php foreach ($seriesDeEx as $s): ?>
                                        <tr id="set_row_<?= $ex['id'] ?>_<?= $s['numero_serie'] ?>" style="opacity:0.8;">
                                            <td style="font-weight:700; color:var(--text-muted); text-align:center;"><?= $s['numero_serie'] ?></td>
                                            <td>
                                                <select class="form-select select-set-type" style="padding:0.4rem; font-size:0.85rem;">
                                                    <option value="normal" <?= ($s['tipo_serie'] === 'normal') ? 'selected' : '' ?>>Normal</option>
                                                    <option value="calentamiento" <?= ($s['tipo_serie'] === 'calentamiento') ? 'selected' : '' ?>>Calentamiento</option>
                                                    <option value="fallo" <?= ($s['tipo_serie'] === 'fallo') ? 'selected' : '' ?>>Fallo</option>
                                                    <option value="dropset" <?= ($s['tipo_serie'] === 'dropset') ? 'selected' : '' ?>>Drop Set</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.5" class="form-input input-weight" value="<?= (float)$s['peso'] ?>" style="width:75px; text-align:center; padding:0.4rem;">
                                            </td>
                                            <td>
                                                <input type="number" class="form-input input-reps" value="<?= (int)$s['repeticiones'] ?>" style="width:65px; text-align:center; padding:0.4rem;">
                                            </td>
                                            <td>
                                                <select class="form-select select-rir" style="padding:0.4rem; font-size:0.85rem;">
                                                    <option value="2" <?= ($s['rir'] == 2) ? 'selected' : '' ?>>RIR 2</option>
                                                    <option value="1" <?= ($s['rir'] == 1) ? 'selected' : '' ?>>RIR 1</option>
                                                    <option value="0" <?= ($s['rir'] === 0) ? 'selected' : '' ?>>RIR 0</option>
                                                    <option value="3" <?= ($s['rir'] == 3) ? 'selected' : '' ?>>RIR 3</option>
                                                </select>
                                            </td>
                                            <td style="text-align:center;">
                                                <button type="button" class="btn btn-primary btn-sm btn-check-set" onclick="toggleCompleteSet(<?= $ex['id'] ?>, <?= $s['numero_serie'] ?>, this)">
                                                    ✓
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- 3 Series predeterminadas para completar rápido -->
                                    <?php for ($i = 1; $i <= 3; $i++): ?>
                                        <tr id="set_row_<?= $ex['id'] ?>_<?= $i ?>">
                                            <td style="font-weight:700; color:var(--text-muted); text-align:center;"><?= $i ?></td>
                                            <td>
                                                <select class="form-select select-set-type" style="padding:0.4rem; font-size:0.85rem;">
                                                    <option value="normal">Normal</option>
                                                    <option value="calentamiento">Calentamiento</option>
                                                    <option value="fallo">Fallo</option>
                                                    <option value="dropset">Drop Set</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.5" class="form-input input-weight" value="60" style="width:75px; text-align:center; padding:0.4rem;">
                                            </td>
                                            <td>
                                                <input type="number" class="form-input input-reps" value="10" style="width:65px; text-align:center; padding:0.4rem;">
                                            </td>
                                            <td>
                                                <select class="form-select select-rir" style="padding:0.4rem; font-size:0.85rem;">
                                                    <option value="2">RIR 2</option>
                                                    <option value="1">RIR 1</option>
                                                    <option value="0">RIR 0</option>
                                                    <option value="3">RIR 3</option>
                                                </select>
                                            </td>
                                            <td style="text-align:center;">
                                                <button type="button" class="btn btn-secondary btn-sm btn-check-set" onclick="toggleCompleteSet(<?= $ex['id'] ?>, <?= $i ?>, this)">
                                                    ✓
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endfor; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Botón rápido + Serie -->
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addSetRow(<?= $ex['id'] ?>)">
                        + Agregar Serie
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Botón para incorporar otro ejercicio al entrenamiento sobre la marcha -->
        <div style="margin-top:2rem; text-align:center;">
            <button type="button" class="btn btn-secondary" onclick="openModal('modal_add_exercise_to_workout')">
                + Agregar Otro Ejercicio a Esta Sesión
            </button>
        </div>

        <!-- Notas finales de la sesión -->
        <div class="card" style="margin-top:2rem;">
            <div class="card-title" style="margin-bottom:0.75rem; font-size:1rem;">Notas de la sesión (sensaciones, congestión, fatiga):</div>
            <textarea id="workout_notes_input" class="form-textarea" rows="2" placeholder="Ej: Excelente conexión muscular en press inclinado, muy buenas sensaciones de fuerza."><?= htmlspecialchars($workout['notas'] ?? '') ?></textarea>
        </div>
    </main>

    <!-- MODAL: AGREGAR EJERCICIO A LA SESIÓN EN VIVO -->
    <div class="modal-backdrop" id="modal_add_exercise_to_workout">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Agregar Ejercicio</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal_add_exercise_to_workout')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Seleccioná un ejercicio del catálogo:</label>
                    <select id="select_new_exercise" class="form-select">
                        <?php foreach ($todosEjercicios as $itemEx): ?>
                            <option value="<?= $itemEx['id'] ?>"><?= htmlspecialchars($itemEx['nombre']) ?> (<?= $itemEx['musculo_principal'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-primary" style="width:100%;" onclick="appendExerciseCardToWorkout()">
                    + Agregar a la Sesión
                </button>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script src="<?= BASE_URL ?>/assets/js/workout.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Iniciar cronómetro con los segundos transcurridos
        initWorkoutTimer(<?= $segundosInicio ?>);
    });

    function appendExerciseCardToWorkout() {
        const select = document.getElementById('select_new_exercise');
        const exId = select.value;
        const exName = select.options[select.selectedIndex].text;

        const container = document.getElementById('workout_exercises_container');
        const card = document.createElement('div');
        card.className = 'card';
        card.id = `exercise_card_${exId}`;
        card.innerHTML = `
            <div class="card-header" style="margin-bottom:0.75rem;">
                <div>
                    <h3 id="exercise_title_${exId}" style="font-size:1.25rem; margin-bottom:0.25rem;">${escapeHTML(exName)}</h3>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="startRestTimer(90)">⏱️ Descanso</button>
            </div>
            <div class="table-container" style="margin-bottom:1rem;">
                <table class="table" id="exercise_table_${exId}">
                    <thead>
                        <tr>
                            <th style="width:45px; text-align:center;">Serie</th>
                            <th style="width:140px;">Tipo</th>
                            <th>Peso (kg)</th>
                            <th>Reps</th>
                            <th>RIR</th>
                            <th style="width:60px; text-align:center;">✓</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="set_row_${exId}_1">
                            <td style="font-weight:700; color:var(--text-muted); text-align:center;">1</td>
                            <td>
                                <select class="form-select select-set-type" style="padding:0.4rem; font-size:0.85rem;">
                                    <option value="normal">Normal</option>
                                    <option value="calentamiento">Calentamiento</option>
                                    <option value="fallo">Fallo</option>
                                </select>
                            </td>
                            <td><input type="number" step="0.5" class="form-input input-weight" value="50" style="width:75px; text-align:center; padding:0.4rem;"></td>
                            <td><input type="number" class="form-input input-reps" value="10" style="width:65px; text-align:center; padding:0.4rem;"></td>
                            <td>
                                <select class="form-select select-rir" style="padding:0.4rem; font-size:0.85rem;">
                                    <option value="2">RIR 2</option>
                                    <option value="1">RIR 1</option>
                                    <option value="0">RIR 0</option>
                                </select>
                            </td>
                            <td style="text-align:center;">
                                <button type="button" class="btn btn-secondary btn-sm btn-check-set" onclick="toggleCompleteSet(${exId}, 1, this)">✓</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addSetRow(${exId})">+ Agregar Serie</button>
        `;
        container.appendChild(card);
        closeModal('modal_add_exercise_to_workout');
        card.scrollIntoView({ behavior: 'smooth' });
    }
    </script>
</div>
