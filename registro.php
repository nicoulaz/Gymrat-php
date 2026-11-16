<?php
/**
 * GYMRAT — Registro de Nuevos Usuarios
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

$error = '';
$nombre = '';
$apellido = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validaciones estrictas
    if (empty($nombre) || empty($apellido) || empty($username) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor ingresá un formato de correo electrónico válido.';
    } elseif (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'El nombre de usuario debe tener al menos 3 caracteres y solo letras, números y guiones bajos.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe contener al menos 6 caracteres.';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas ingresadas no coinciden.';
    } else {
        try {
            $pdo = getDBConnection();

            // Verificar si el email o username ya existen
            $checkStmt = $pdo->prepare("SELECT id, email, username FROM usuarios WHERE email = ? OR username = ? LIMIT 1");
            $checkStmt->execute([$email, $username]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                if ($existing['email'] === $email) {
                    $error = 'Ese correo electrónico ya está registrado.';
                } else {
                    $error = 'Ese nombre de usuario ya está en uso.';
                }
            } else {
                // Hashear contraseña de forma segura
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                $insertStmt = $pdo->prepare("
                    INSERT INTO usuarios 
                    (nombre, apellido, username, email, password, onboarding_completado, streak_dias, ultimo_streak_fecha, nivel, puntos_xp) 
                    VALUES (?, ?, ?, ?, ?, 0, 1, CURRENT_DATE, 1, 50)
                ");
                $insertStmt->execute([$nombre, $apellido, $username, $email, $passwordHash]);
                $newUserId = (int)$pdo->lastInsertId();

                // Autenticar sesión
                $_SESSION['gymrat_user_id'] = $newUserId;
                $_SESSION['gymrat_username'] = $username;

                // Redirigir al Onboarding
                header("Location: " . BASE_URL . "/onboarding.php");
                exit;
            }
        } catch (Exception $e) {
            error_log("Error en registro: " . $e->getMessage());
            $error = 'Ocurrió un error al crear la cuenta. Intentalo de nuevo.';
        }
    }
}

$pageTitle = 'Crear Cuenta — GYMRAT';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css?v=<?= APP_VERSION ?>">
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
        }
        .auth-card {
            width: 100%;
            max-width: 500px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow-lg);
        }
    </style>
</head>
<body style="background-color:var(--bg-primary);">

<div class="auth-wrapper">
    <div class="auth-card card-glow">
        <div style="text-align:center; margin-bottom:2rem;">
            <a href="<?= BASE_URL ?>/index.php" style="display:inline-flex; align-items:center; gap:0.75rem; text-decoration:none;">
                <div class="brand-logo-icon">
                    <svg viewBox="0 0 24 24"><path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29z"/></svg>
                </div>
                <div class="brand-name">GYM<span>RAT</span></div>
            </a>
            <h2 style="margin-top:1.5rem; font-size:1.5rem;">Unite a Gymrat</h2>
            <p style="font-size:0.875rem;">Comenzá tu transformación con ciencia, disciplina y datos reales</p>
        </div>

        <?php if ($error): ?>
            <div style="background:rgba(244, 63, 94, 0.15); border:1px solid rgba(244, 63, 94, 0.3); color:#fb7185; padding:0.75rem 1rem; border-radius:var(--radius-md); font-size:0.85rem; margin-bottom:1.5rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="registro.php">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" class="form-input" placeholder="Nicolás" value="<?= htmlspecialchars($nombre) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="apellido">Apellido</label>
                    <input type="text" id="apellido" name="apellido" class="form-input" placeholder="Gómez" value="<?= htmlspecialchars($apellido) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Nombre de Usuario</label>
                <div class="input-icon-wrapper">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    <input type="text" id="username" name="username" class="form-input" placeholder="nicolas_fit" value="<?= htmlspecialchars($username) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Correo Electrónico</label>
                <div class="input-icon-wrapper">
                    <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    <input type="email" id="email" name="email" class="form-input" placeholder="tu@email.com" value="<?= htmlspecialchars($email) ?>" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirm">Repetir Contraseña</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-input" placeholder="Repetí tu clave" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem; padding:0.85rem;">
                Crear Mi Cuenta & Continuar &rarr;
            </button>
        </form>

        <div style="text-align:center; margin-top:1.5rem; font-size:0.875rem;">
            ¿Ya tenés una cuenta? <a href="<?= BASE_URL ?>/login.php" style="font-weight:700;">Iniciar Sesión</a>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/auth.js"></script>
</body>
</html>
