<?php
/**
 * GYMRAT — Presentación Comercial Gymrat PRO (Demostración de Membresía)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();

// Manejar activación demostrativa de cuenta PRO
if (isset($_POST['toggle_premium_demo'])) {
    $nuevoTipo = ($user['tipo_cuenta'] === 'premium') ? 'free' : 'premium';
    $pdo->prepare("UPDATE usuarios SET tipo_cuenta = ? WHERE id = ?")->execute([$nuevoTipo, $user['id']]);
    header("Location: " . BASE_URL . "/premium.php?status_updated=1");
    exit;
}

$pageTitle = 'Gymrat PRO — Rendimiento Sin Límites';
$pageSubtitle = 'Desbloqueá análisis fotográfico ilimitado, mapas musculares profundos y herramientas avanzadas';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-body">
        <div style="text-align:center; max-width:700px; margin:1rem auto 2.5rem;">
            <div class="badge badge-premium" style="font-size:0.85rem; padding:0.4rem 1rem; margin-bottom:1rem;">
                MEMBRESÍA GYMRAT PRO
            </div>
            <h1 style="font-size:2.8rem; margin-bottom:0.75rem;">Llevá tu Físico al Siguiente Nivel</h1>
            <p style="font-size:1.1rem; color:var(--text-secondary);">
                Herramientas biométricas avanzadas, estimaciones biomecánicas de volumen, análisis ilimitado de comidas y reportes de atletas.
            </p>
        </div>

        <?php if ($user['tipo_cuenta'] === 'premium'): ?>
            <div style="max-width:850px; margin:0 auto 2rem; background:rgba(0, 245, 155, 0.08); border:1px solid var(--accent-green); border-radius:var(--radius-lg); padding:1.25rem 2rem; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3 style="color:var(--accent-green); margin:0 0 0.25rem;">Tu cuenta tiene Gymrat PRO Activo (Modo Demostración)</h3>
                    <p style="margin:0; font-size:0.85rem;">Todas las herramientas y analíticas se encuentran completamente desbloqueadas.</p>
                </div>
                <form method="POST">
                    <button type="submit" name="toggle_premium_demo" class="btn btn-secondary btn-sm">
                        Cambiar a Versión Gratuita
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Comparación de Planes (Requerimiento 53) -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem; max-width:900px; margin:0 auto 3rem;">
            <!-- Plan Free -->
            <div class="card" style="display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                    <h3 style="font-size:1.5rem; margin-bottom:0.5rem;">Gymrat Free</h3>
                    <div style="font-size:2rem; font-weight:900; margin-bottom:1rem;">$0 <span style="font-size:1rem; color:var(--text-muted); font-weight:500;">/ siempre</span></div>
                    <p style="font-size:0.9rem; margin-bottom:1.5rem;">El núcleo esencial para registrar entrenamientos y llevar el control diario.</p>

                    <ul style="list-style:none; display:flex; flex-direction:column; gap:0.75rem; font-size:0.9rem; color:var(--text-secondary); margin-bottom:2rem;">
                        <li>✓ Registro de series, pesos y repeticiones</li>
                        <li>✓ Diario de nutrición y cálculo Mifflin-St Jeor</li>
                        <li>✓ Récords personales (PRs) básicos</li>
                        <li>✓ Historial de 30 días de entrenamientos</li>
                        <li>✓ Comunidad y feed social</li>
                        <li style="color:var(--text-muted); text-decoration:line-through;">✕ Muscle Map Anatómico Avanzado</li>
                        <li style="color:var(--text-muted); text-decoration:line-through;">✕ Análisis Fotográfico de Comidas con IA Ilimitado</li>
                        <li style="color:var(--text-muted); text-decoration:line-through;">✕ Informes mensuales descargables</li>
                    </ul>
                </div>

                <button type="button" class="btn btn-secondary" style="width:100%;" disabled>Plan Básico Actual</button>
            </div>

            <!-- Plan Premium -->
            <div class="card card-glow" style="display:flex; flex-direction:column; justify-content:space-between; border-color:var(--accent-amber); background:linear-gradient(135deg, var(--bg-card) 0%, rgba(245, 158, 11, 0.05) 100%);">
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                        <h3 style="font-size:1.5rem; margin:0;">Gymrat PRO</h3>
                        <span class="badge badge-premium">RECOMENDADO</span>
                    </div>
                    <div style="font-size:2rem; font-weight:900; color:var(--accent-amber); margin-bottom:1rem;">$9.99 <span style="font-size:1rem; color:var(--text-muted); font-weight:500;">/ mes</span></div>
                    <p style="font-size:0.9rem; margin-bottom:1.5rem;">Para atletas que buscan la máxima precisión y velocidad en sus progresos.</p>

                    <ul style="list-style:none; display:flex; flex-direction:column; gap:0.75rem; font-size:0.9rem; color:var(--text-primary); margin-bottom:2rem;">
                        <li>✓ <strong>Todo lo incluido en Free</strong></li>
                        <li>✓ <strong>Muscle Map Interactivo 2D</strong> con cálculo de hipertrofia</li>
                        <li>✓ <strong>Análisis Fotográfico de Comidas con IA</strong></li>
                        <li>✓ <strong>Historial de Entrenamientos Ilimitado</strong></li>
                        <li>✓ <strong>Slider de Progreso Antes / Después</strong></li>
                        <li>✓ <strong>Exportación de Informes Mensuales</strong></li>
                        <li>✓ Insignia dorada PRO en tu perfil público</li>
                    </ul>
                </div>

                <form method="POST">
                    <button type="submit" name="toggle_premium_demo" class="btn btn-primary" style="width:100%; background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:#06090e; font-weight:800;">
                        <?= ($user['tipo_cuenta'] === 'premium') ? 'Desactivar PRO (Demo)' : '⚡ Activar Gymrat PRO (Demostración Gratuita)' ?>
                    </button>
                </form>
            </div>
        </div>

        <div style="text-align:center; font-size:0.85rem; color:var(--text-muted); max-width:600px; margin:0 auto;">
            <em>Nota de demostración: Esta sección simula la experiencia comercial de suscripción sin pasarelas de pago reales, permitiéndote probar la versión PRO con un solo clic.</em>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>
