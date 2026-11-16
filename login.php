<?php
/**
 * GYMRAT — Iniciar Sesión
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

$error = '';
$prefillEmail = '';

// Si viene con parámetro demo=1, prellenar credenciales demo
if (isset($_GET['demo']) && $_GET['demo'] == '1') {
    $prefillEmail = 'demo@gymrat.com';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'Por favor completá todos los campos.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? OR username = ? LIMIT 1");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Iniciar sesión
                $_SESSION['gymrat_user_id'] = $user['id'];
                $_SESSION['gymrat_username'] = $user['username'];

                // Actualizar último acceso y racha
                updateUserStreak($user['id']);

                // Resolver redirección segura evitando duplicación de BASE_URL
                if (!empty($_GET['redirect'])) {
                    $target = $_GET['redirect'];
                    if (strpos($target, '://') !== false) {
                        $target = parse_url($target, PHP_URL_PATH) ?? '';
                    }
                    $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?? '/gymrat';
                    if (!empty($basePath) && strpos($target, $basePath) === 0) {
                        $target = substr($target, strlen($basePath));
                    }
                    $target = '/' . ltrim($target, '/');
                    $redirectUrl = BASE_URL . $target;
                } else {
                    $destination = empty($user['onboarding_completado']) ? 'onboarding.php' : 'dashboard.php';
                    $redirectUrl = BASE_URL . '/' . $destination;
                }

                header("Location: " . $redirectUrl);
                exit;
            } else {
                $error = 'Credenciales incorrectas. Verificá tu correo/usuario y contraseña.';
            }
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = 'Error al conectar con la base de datos. Asegurate de que MySQL esté iniciado en XAMPP.';
        }
    }
}

$pageTitle = 'Iniciar Sesión — GYMRAT';
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
            padding: 2rem 1.5rem;
            position: relative;
        }
        .auth-card {
            width: 100%;
            max-width: 440px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
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
            <h2 style="margin-top:1.5rem; font-size:1.5rem;">Iniciar Sesión</h2>
            <p style="font-size:0.875rem;">Bienvenido de nuevo a tu plataforma de entrenamiento</p>
        </div>

        <?php if ($error): ?>
            <div style="background:rgba(244, 63, 94, 0.15); border:1px solid rgba(244, 63, 94, 0.3); color:#fb7185; padding:0.75rem 1rem; border-radius:var(--radius-md); font-size:0.85rem; margin-bottom:1.5rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">
            <div class="form-group">
                <label class="form-label" for="email">Correo Electrónico o Usuario</label>
                <div class="input-icon-wrapper">
                    <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    <input type="text" id="email" name="email" class="form-input" placeholder="tu@email.com o nicolasfit" value="<?= htmlspecialchars($prefillEmail) ?>" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <div class="input-icon-wrapper" style="position:relative;">
                    <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" value="<?= ($prefillEmail === 'demo@gymrat.com') ? 'password123' : '' ?>" required>
                    <button type="button" class="btn-icon" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--text-muted); cursor:pointer;" onclick="togglePassword('password', this)">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:0.75rem; padding:0.85rem;">
                Entrar a Gymrat &rarr;
            </button>
        </form>

        <!-- Acceso Rápido Demo -->
        <div style="margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid var(--border-subtle); text-align:center;">
            <p style="font-size:0.8rem; margin-bottom:0.75rem; color:var(--text-muted);">¿Querés probar la plataforma de inmediato?</p>
            <button type="button" class="btn btn-secondary btn-sm" style="width:100%; border-color:var(--accent-green); color:var(--accent-green);" onclick="fillDemoCredentials()">
                ⚡ Iniciar como Nicolás (Usuario Demo)
            </button>
        </div>

        <div style="text-align:center; margin-top:1.5rem; font-size:0.875rem;">
            ¿No tenés una cuenta? <a href="<?= BASE_URL ?>/registro.php" style="font-weight:700;">Registrate gratis</a>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/auth.js"></script>
<script>
function fillDemoCredentials() {
    document.getElementById('email').value = 'demo@gymrat.com';
    document.getElementById('password').value = 'password123';
    document.forms[0].submit();
}
</script>
</body>
</html>
