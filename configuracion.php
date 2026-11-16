<?php
/**
 * GYMRAT — Panel de Configuración General, Privacidad y Unidades
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();

$msg = '';
$error = '';

// Procesar cambios en configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'guardar_cuenta') {
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $biografia = trim($_POST['biografia'] ?? '');

        if (!empty($nombre)) {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, biografia = ? WHERE id = ?");
            $stmt->execute([$nombre, $apellido, $biografia, $user['id']]);
            $msg = 'Información de cuenta actualizada correctamente.';
            $user = getLoggedUser(); // Refrescar
        }
    } elseif ($action === 'guardar_nutricion') {
        $calorias = (int)($_POST['calorias_objetivo'] ?? 2400);
        $proteinas = (int)($_POST['proteinas_objetivo'] ?? 190);
        $carbos = (int)($_POST['carbohidratos_objetivo'] ?? 240);
        $grasas = (int)($_POST['grasas_objetivo'] ?? 70);
        $agua = (int)($_POST['agua_objetivo'] ?? 2500);
        $pasos = (int)($_POST['pasos_objetivo'] ?? 10000);

        $stmt = $pdo->prepare("
            UPDATE usuarios SET 
                calorias_objetivo = ?,
                proteinas_objetivo = ?,
                carbohidratos_objetivo = ?,
                grasas_objetivo = ?,
                agua_objetivo = ?,
                pasos_objetivo = ?
            WHERE id = ?
        ");
        $stmt->execute([$calorias, $proteinas, $carbos, $grasas, $agua, $pasos, $user['id']]);
        $msg = 'Objetivos nutricionales personalizados guardados.';
        $user = getLoggedUser();
    } elseif ($action === 'guardar_privacidad') {
        $perfilPub = isset($_POST['perfil_publico']) ? 1 : 0;
        $entrenosPub = isset($_POST['entrenamientos_publicos']) ? 1 : 0;
        $prsPub = isset($_POST['prs_publicos']) ? 1 : 0;
        $fotosPub = isset($_POST['fotos_publicas']) ? 1 : 0;

        $stmt = $pdo->prepare("
            UPDATE usuarios SET 
                perfil_publico = ?,
                entrenamientos_publicos = ?,
                prs_publicos = ?,
                fotos_publicas = ?
            WHERE id = ?
        ");
        $stmt->execute([$perfilPub, $entrenosPub, $prsPub, $fotosPub, $user['id']]);
        $msg = 'Preferencias de privacidad actualizadas.';
        $user = getLoggedUser();
    } elseif ($action === 'guardar_unidades') {
        $uPeso = $_POST['unidad_peso'] ?? 'kg';
        $uAltura = $_POST['unidad_altura'] ?? 'cm';
        $uAgua = $_POST['unidad_agua'] ?? 'ml';

        $stmt = $pdo->prepare("
            UPDATE usuarios SET 
                unidad_peso = ?,
                unidad_altura = ?,
                unidad_agua = ?
            WHERE id = ?
        ");
        $stmt->execute([$uPeso, $uAltura, $uAgua, $user['id']]);
        $msg = 'Unidades de medida actualizadas.';
        $user = getLoggedUser();
    }
}

// Obtener recordatorios del usuario
$recStmt = $pdo->prepare("SELECT * FROM recordatorios WHERE usuario_id = ? ORDER BY hora ASC");
$recStmt->execute([$user['id']]);
$recordatorios = $recStmt->fetchAll();

$pageTitle = 'Configuración — GYMRAT';
$pageSubtitle = 'Ajustes de cuenta, objetivos calóricos, privacidad y recordatorios';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-body">
        <div style="margin-bottom:1.75rem;">
            <h2>Configuración del Sistema</h2>
            <p>Personalizá tus metas, ajustá tu privacidad y configurá tus preferencias de la plataforma.</p>
        </div>

        <?php if ($msg): ?>
            <div style="background:rgba(0, 245, 155, 0.1); border:1px solid var(--accent-green); color:var(--accent-green); padding:0.85rem 1.25rem; border-radius:var(--radius-md); font-size:0.9rem; margin-bottom:1.5rem;">
                ✓ <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <!-- Pestañas de Configuración -->
        <div class="tab-group">
            <div class="tab-item active" onclick="switchConfigTab('tab_cuenta', this)">Cuenta & Perfil</div>
            <div class="tab-item" onclick="switchConfigTab('tab_nutricion', this)">Objetivos Nutricionales</div>
            <div class="tab-item" onclick="switchConfigTab('tab_privacidad', this)">Privacidad</div>
            <div class="tab-item" onclick="switchConfigTab('tab_unidades', this)">Unidades de Medida</div>
            <div class="tab-item" onclick="switchConfigTab('tab_recordatorios', this)">Recordatorios</div>
        </div>

        <!-- 1. TAB CUENTA Y PERFIL -->
        <div id="tab_cuenta" class="config-tab-content">
            <div class="card card-glow" style="max-width:650px;">
                <form method="POST">
                    <input type="hidden" name="action" value="guardar_cuenta">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Nombre:</label>
                            <input type="text" name="nombre" class="form-input" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Apellido:</label>
                            <input type="text" name="apellido" class="form-input" value="<?= htmlspecialchars($user['apellido']) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico (no modificable):</label>
                        <input type="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:0.6;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre de Usuario (@username):</label>
                        <input type="text" class="form-input" value="<?= htmlspecialchars($user['username']) ?>" disabled style="opacity:0.6;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Biografía Atlética:</label>
                        <textarea name="biografia" class="form-textarea" rows="3"><?= htmlspecialchars($user['biografia'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:0.5rem;">
                        Guardar Información de Cuenta
                    </button>
                </form>
            </div>
        </div>

        <!-- 2. TAB OBJETIVOS NUTRICIONALES -->
        <div id="tab_nutricion" class="config-tab-content" style="display:none;">
            <div class="card card-glow" style="max-width:650px;">
                <h3 style="font-size:1.15rem; margin-bottom:0.5rem;">Personalización de Macros</h3>
                <p style="font-size:0.85rem; margin-bottom:1.5rem;">Podés sobreescribir los cálculos automáticos de Mifflin-St Jeor con tus propios objetivos:</p>

                <form method="POST">
                    <input type="hidden" name="action" value="guardar_nutricion">
                    <div class="form-group">
                        <label class="form-label">Calorías Diarias (kcal):</label>
                        <input type="number" name="calorias_objetivo" class="form-input" value="<?= (int)$user['calorias_objetivo'] ?>" required>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Proteínas (g):</label>
                            <input type="number" name="proteinas_objetivo" class="form-input" value="<?= (int)$user['proteinas_objetivo'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Carbohidratos (g):</label>
                            <input type="number" name="carbohidratos_objetivo" class="form-input" value="<?= (int)$user['carbohidratos_objetivo'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Grasas (g):</label>
                            <input type="number" name="grasas_objetivo" class="form-input" value="<?= (int)$user['grasas_objetivo'] ?>" required>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">Meta de Agua (ml):</label>
                            <input type="number" name="agua_objetivo" class="form-input" value="<?= (int)$user['agua_objetivo'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Meta de Pasos:</label>
                            <input type="number" name="pasos_objetivo" class="form-input" value="<?= (int)$user['pasos_objetivo'] ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:0.5rem;">
                        Guardar Objetivos Nutricionales
                    </button>
                </form>
            </div>
        </div>

        <!-- 3. TAB PRIVACIDAD -->
        <div id="tab_privacidad" class="config-tab-content" style="display:none;">
            <div class="card" style="max-width:650px;">
                <h3 style="font-size:1.15rem; margin-bottom:1rem;">Control de Visibilidad y Datos</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="guardar_privacidad">
                    <div style="display:flex; flex-direction:column; gap:1.25rem;">
                        <label style="display:flex; align-items:center; gap:0.75rem; cursor:pointer;">
                            <input type="checkbox" name="perfil_publico" value="1" <?= $user['perfil_publico'] ? 'checked' : '' ?> style="width:18px; height:18px;">
                            <div>
                                <strong>Perfil Público</strong>
                                <p style="font-size:0.8rem; margin:0;">Permite a otros atletas encontrarte y seguirte en la comunidad.</p>
                            </div>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.75rem; cursor:pointer;">
                            <input type="checkbox" name="entrenamientos_publicos" value="1" <?= $user['entrenamientos_publicos'] ? 'checked' : '' ?> style="width:18px; height:18px;">
                            <div>
                                <strong>Entrenamientos Públicos en el Feed</strong>
                                <p style="font-size:0.8rem; margin:0;">Muestra tus sesiones completadas en el feed social.</p>
                            </div>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.75rem; cursor:pointer;">
                            <input type="checkbox" name="prs_publicos" value="1" <?= $user['prs_publicos'] ? 'checked' : '' ?> style="width:18px; height:18px;">
                            <div>
                                <strong>Récords Personales (PRs) Públicos</strong>
                                <p style="font-size:0.8rem; margin:0;">Comparte tus marcas históricas en tu perfil.</p>
                            </div>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.75rem; cursor:pointer;">
                            <input type="checkbox" name="fotos_publicas" value="1" <?= $user['fotos_publicas'] ? 'checked' : '' ?> style="width:18px; height:18px;">
                            <div>
                                <strong>Fotografías de Progreso Públicas</strong>
                                <p style="font-size:0.8rem; margin:0;">Por defecto tus fotografías son 100% privadas y personales.</p>
                            </div>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top:1.5rem;">
                        Guardar Privacidad
                    </button>
                </form>
            </div>
        </div>

        <!-- 4. TAB UNIDADES DE MEDIDA (Requerimiento 56) -->
        <div id="tab_unidades" class="config-tab-content" style="display:none;">
            <div class="card" style="max-width:650px;">
                <h3 style="font-size:1.15rem; margin-bottom:1rem;">Unidades de Medida</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="guardar_unidades">
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:1rem; margin-bottom:1.5rem;">
                        <div class="form-group">
                            <label class="form-label">Peso:</label>
                            <select name="unidad_peso" class="form-select">
                                <option value="kg" <?= ($user['unidad_peso'] === 'kg') ? 'selected' : '' ?>>Kilogramos (kg)</option>
                                <option value="lb" <?= ($user['unidad_peso'] === 'lb') ? 'selected' : '' ?>>Libras (lb)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Altura:</label>
                            <select name="unidad_altura" class="form-select">
                                <option value="cm" <?= ($user['unidad_altura'] === 'cm') ? 'selected' : '' ?>>Centímetros (cm)</option>
                                <option value="ft" <?= ($user['unidad_altura'] === 'ft') ? 'selected' : '' ?>>Pies / Pulgadas (ft)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Agua / Líquidos:</label>
                            <select name="unidad_agua" class="form-select">
                                <option value="ml" <?= ($user['unidad_agua'] === 'ml') ? 'selected' : '' ?>>Mililitros (ml)</option>
                                <option value="oz" <?= ($user['unidad_agua'] === 'oz') ? 'selected' : '' ?>>Onzas (oz)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Guardar Unidades
                    </button>
                </form>
            </div>
        </div>

        <!-- 5. TAB RECORDATORIOS (Requerimiento 52) -->
        <div id="tab_recordatorios" class="config-tab-content" style="display:none;">
            <div class="card" style="max-width:650px;">
                <div class="card-header">
                    <div class="card-title">Recordatorios de Hábitos</div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="openModal('modal_new_reminder')">+ Nuevo Aviso</button>
                </div>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <?php if (empty($recordatorios)): ?>
                        <p style="color:var(--text-muted); font-size:0.85rem;">No tenés recordatorios programados.</p>
                    <?php else: ?>
                        <?php foreach ($recordatorios as $rec): ?>
                            <div style="background:var(--bg-secondary); border-radius:var(--radius-sm); padding:0.75rem 1rem; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <strong style="text-transform:capitalize; color:var(--text-primary); font-size:0.95rem;"><?= htmlspecialchars($rec['tipo']) ?></strong>
                                    <div style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($rec['mensaje']) ?></div>
                                </div>
                                <span class="badge badge-green"><?= date('H:i', strtotime($rec['hora'])) ?> hs</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL NUEVO RECORDATORIO -->
    <div class="modal-backdrop" id="modal_new_reminder">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Nuevo Recordatorio</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal_new_reminder')">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="reminder_form" onsubmit="submitReminder(event)">
                    <div class="form-group">
                        <label class="form-label">Tipo de Recordatorio:</label>
                        <select id="rem_type" class="form-select">
                            <option value="agua">Tomar Agua</option>
                            <option value="entrenar">Hora de Entrenar</option>
                            <option value="comer">Registrar Comida</option>
                            <option value="pesar">Pesarme en Ayunas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora:</label>
                        <input type="time" id="rem_time" class="form-input" value="10:00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mensaje personalizado:</label>
                        <input type="text" id="rem_msg" class="form-input" placeholder="Ej: Tomá al menos 500ml de agua">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;">
                        Guardar Recordatorio
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script>
    function switchConfigTab(tabId, el) {
        document.querySelectorAll('.config-tab-content').forEach(c => c.style.display = 'none');
        document.querySelectorAll('.tab-group .tab-item').forEach(t => t.classList.remove('active'));
        document.getElementById(tabId).style.display = 'block';
        el.classList.add('active');
    }

    async function submitReminder(e) {
        e.preventDefault();
        const payload = {
            tipo: document.getElementById('rem_type').value,
            hora: document.getElementById('rem_time').value + ':00',
            mensaje: document.getElementById('rem_msg').value
        };
        const res = await fetchAPI('api/recordatorios/guardar.php', 'POST', payload);
        if (res.success) {
            showToast('✓ Recordatorio configurado', 'Aviso guardado en tu sistema.');
            closeModal('modal_new_reminder');
            setTimeout(() => location.reload(), 400);
        } else {
            showToast('Error', res.message || 'No se pudo guardar.', 'error');
        }
    }
    </script>
</div>
