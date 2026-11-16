<?php
/**
 * GYMRAT — Landing Page Oficial (Si no hay sesión iniciada)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// Si el usuario ya está autenticado, llevar al dashboard
if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

$pageTitle = 'GYMRAT — Train. Track. Improve.';
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
        .landing-hero {
            min-height: 85vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 3rem 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .landing-glow {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(0, 245, 155, 0.15) 0%, rgba(10, 13, 20, 0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 0;
        }
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(0, 245, 155, 0.1);
            color: var(--accent-green);
            padding: 0.4rem 1rem;
            border-radius: var(--radius-full);
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 245, 155, 0.25);
        }
        .hero-title {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.03em;
            margin-bottom: 1.25rem;
        }
        .hero-title span {
            background: linear-gradient(135deg, #00f59b 0%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-desc {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }
        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto 5rem;
            padding: 0 1.5rem;
        }
        @media(max-width: 768px) {
            .hero-title { font-size: 2.75rem; }
            .feature-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body style="background-color:var(--bg-primary);">

    <!-- Barra de Navegación de Landing -->
    <header style="padding:1.5rem 2.5rem; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-subtle); max-width:1400px; margin:0 auto;">
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <div class="brand-logo-icon">
                <svg viewBox="0 0 24 24"><path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29z"/></svg>
            </div>
            <div class="brand-name">GYM<span>RAT</span></div>
        </div>
        <div style="display:flex; gap:1rem; align-items:center;">
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-secondary btn-sm">Iniciar Sesión</a>
            <a href="<?= BASE_URL ?>/registro.php" class="btn btn-primary btn-sm">Comenzar Ahora</a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="landing-hero">
        <div class="landing-glow"></div>
        <div class="hero-content">
            <div class="hero-badge">⚡ EL ECOSISTEMA FITNESS DEFINITIVO</div>
            <h1 class="hero-title">Train. Track.<br><span>Improve.</span></h1>
            <p class="hero-desc">Todo tu progreso en un solo lugar. Entrenamiento con sobrecarga progresiva, cálculo metabólico Mifflin-St Jeor, seguimiento de macros, mapa muscular dinámico y comunidad.</p>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/registro.php" class="btn btn-primary btn-lg">Comenzar Gratis</a>
                <a href="<?= BASE_URL ?>/login.php?demo=1" class="btn btn-secondary btn-lg" style="border-color:var(--accent-green); color:var(--accent-green);">
                    ⚡ Probar con Usuario Demo
                </a>
            </div>
        </div>
    </section>

    <!-- Pilares de la Plataforma -->
    <section class="feature-grid">
        <div class="card card-glow">
            <div class="stat-icon-wrapper green" style="width:48px; height:48px; margin-bottom:1rem;">
                <svg viewBox="0 0 24 24"><path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29z"/></svg>
            </div>
            <h3 style="margin-bottom:0.5rem;">Entrenamiento & PRs</h3>
            <p style="font-size:0.9rem;">Registrá tus series rápidamente en vivo con RIR/RPE, cronómetro de descanso integrado y detección automática de récords personales (1RM).</p>
        </div>

        <div class="card card-glow">
            <div class="stat-icon-wrapper blue" style="width:48px; height:48px; margin-bottom:1rem;">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
            </div>
            <h3 style="margin-bottom:0.5rem;">Nutrición & Macros</h3>
            <p style="font-size:0.9rem;">Base de datos de alimentos, escáner simulado, análisis fotográfico con IA y cálculo exacto de calorías, proteínas, carbohidratos y grasas.</p>
        </div>

        <div class="card card-glow">
            <div class="stat-icon-wrapper purple" style="width:48px; height:48px; margin-bottom:1rem;">
                <svg viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
            </div>
            <h3 style="margin-bottom:0.5rem;">Muscle Map & Progreso</h3>
            <p style="font-size:0.9rem;">Visualizá la sobrecarga en tu anatomía 2D, compará fotografías con slider Antes/Después y analizá tu evolución de peso y volumen.</p>
        </div>
    </section>

    <!-- Footer -->
    <footer style="padding:2rem; text-align:center; border-top:1px solid var(--border-subtle); color:var(--text-muted); font-size:0.85rem;">
        &copy; <?= date('Y') ?> GYMRAT — Train. Track. Improve. Todos los derechos reservados.
    </footer>

</body>
</html>
