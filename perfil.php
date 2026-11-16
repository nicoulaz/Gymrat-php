<?php
/**
 * GYMRAT — Perfil de Atleta y Estadísticas Deportivas
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$loggedUser = getLoggedUser();
$pdo = getDBConnection();

$viewUsername = trim($_GET['user'] ?? '');
$isOwnProfile = empty($viewUsername) || ($viewUsername === $loggedUser['username']);

if ($isOwnProfile) {
    $profileUser = $loggedUser;
} else {
    $uStmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ? LIMIT 1");
    $uStmt->execute([$viewUsername]);
    $profileUser = $uStmt->fetch();
    if (!$profileUser) {
        header("Location: " . BASE_URL . "/comunidad.php");
        exit;
    }
}

// 1. Estadísticas Deportivas Totales del Atleta
$entrenosCount = (int)$pdo->query("SELECT COUNT(*) FROM entrenamientos WHERE usuario_id = {$profileUser['id']} AND estado = 'completado'")->fetchColumn();
$prsCount = (int)$pdo->query("SELECT COUNT(*) FROM records_personales WHERE usuario_id = {$profileUser['id']}")->fetchColumn();
$volTotal = (float)$pdo->query("SELECT COALESCE(SUM(volumen_total), 0) FROM entrenamientos WHERE usuario_id = {$profileUser['id']}")->fetchColumn();

// 2. Seguidores y Seguidos
$seguidoresCount = (int)$pdo->query("SELECT COUNT(*) FROM comunidad_seguidores WHERE seguido_id = {$profileUser['id']}")->fetchColumn();
$seguidosCount = (int)$pdo->query("SELECT COUNT(*) FROM comunidad_seguidores WHERE seguidor_id = {$profileUser['id']}")->fetchColumn();

// 3. Logros Desbloqueados
$logrosStmt = $pdo->prepare("
    SELECT l.*, ul.fecha_obtenido 
    FROM usuario_logros ul 
    JOIN logros l ON ul.logro_id = l.id 
    WHERE ul.usuario_id = ? 
    ORDER BY ul.fecha_obtenido DESC
");
$logrosStmt->execute([$profileUser['id']]);
$misLogros = $logrosStmt->fetchAll();

// 4. Records Personales
$prsStmt = $pdo->prepare("
    SELECT pr.*, e.nombre as ejercicio_nombre 
    FROM records_personales pr 
    JOIN ejercicios e ON pr.ejercicio_id = e.id 
    WHERE pr.usuario_id = ? 
    ORDER BY pr.fecha DESC LIMIT 6
");
$prsStmt->execute([$profileUser['id']]);
$misPRs = $prsStmt->fetchAll();

$pageTitle = 'Perfil de ' . htmlspecialchars($profileUser['nombre']) . ' — GYMRAT';
$pageSubtitle = '@' . htmlspecialchars($profileUser['username']) . ' • Perfil de Atleta';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-body">
        <!-- Tarjeta de Cabecera del Perfil -->
        <div class="card card-glow" style="margin-bottom:2rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1.5rem;">
                <div style="display:flex; align-items:center; gap:1.5rem;">
                    <img src="<?= BASE_URL ?>/assets/img/default-avatar.svg" alt="<?= htmlspecialchars($profileUser['username']) ?>" style="width:84px; height:84px; border-radius:50%; border:3px solid var(--accent-green); object-fit:cover;">
                    <div>
                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                            <h2 style="font-size:1.8rem; margin:0;"><?= htmlspecialchars($profileUser['nombre'] . ' ' . $profileUser['apellido']) ?></h2>
                            <span class="badge badge-green">Nivel <?= (int)$profileUser['nivel'] ?></span>
                            <?php if ($profileUser['tipo_cuenta'] === 'premium'): ?>
                                <span class="badge badge-premium">PRO</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:0.9rem; color:var(--text-muted); margin-bottom:0.6rem;">
                            @<?= htmlspecialchars($profileUser['username']) ?> 
                            • <span id="user_followers_count"><?= $seguidoresCount ?></span> seguidores 
                            • <?= $seguidosCount ?> seguidos
                        </div>
                        <p style="font-size:0.9rem; color:var(--text-secondary); max-width:550px; margin:0;">
                            <?= htmlspecialchars($profileUser['biografia'] ?: 'Atleta enfocado en constancia y progresión.') ?>
                        </p>
                    </div>
                </div>

                <?php if ($isOwnProfile): ?>
                    <a href="<?= BASE_URL ?>/configuracion.php" class="btn btn-secondary btn-sm">
                        ⚙️ Editar Perfil
                    </a>
                <?php endif; ?>
            </div>

            <!-- Panel de Estadísticas Deportivas (Requerimiento 115) -->
            <div style="display:grid; grid-template-columns: repeat(5, 1fr); gap:1rem; text-align:center; border-top:1px solid var(--border-subtle); padding-top:1.5rem; margin-top:1.5rem;">
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Entrenamientos</div>
                    <div style="font-size:1.75rem; font-weight:900; color:var(--text-primary);"><?= $entrenosCount ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; color:var(--accent-amber); font-weight:700;">PRs Superados</div>
                    <div style="font-size:1.75rem; font-weight:900; color:var(--accent-amber);"><?= $prsCount ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Streak Activo</div>
                    <div style="font-size:1.75rem; font-weight:900; color:var(--text-primary);">🔥 <?= (int)$profileUser['streak_dias'] ?>d</div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; color:var(--accent-green); font-weight:700;">Volumen Total</div>
                    <div style="font-size:1.75rem; font-weight:900; color:var(--accent-green);"><?= number_format($volTotal / 1000, 1) ?>k kg</div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; color:var(--accent-blue); font-weight:700;">Nivel Atleta</div>
                    <div style="font-size:1.75rem; font-weight:900; color:var(--accent-blue);"><?= (int)$profileUser['nivel'] ?></div>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem;">
            <!-- Récords Personales del Atleta -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="var(--accent-amber)"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94A5.01 5.01 0 0011 15.9V19H7v2h10v-2h-4v-3.1c1.92-.37 3.42-1.84 3.61-3.96C19.08 11.63 21 9.55 21 7V5c0-1.1-.9-2-2-2zm-14 3V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>
                        Récords Personales (PRs)
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <?php if (empty($misPRs)): ?>
                        <p style="color:var(--text-muted); font-size:0.85rem;">No hay récords registrados.</p>
                    <?php else: ?>
                        <?php foreach ($misPRs as $pr): ?>
                            <div style="background:var(--bg-secondary); border-radius:var(--radius-sm); padding:0.75rem 1rem; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <div style="font-weight:700; color:var(--text-primary); font-size:0.95rem;"><?= htmlspecialchars($pr['ejercicio_nombre']) ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);"><?= date('d/m/Y', strtotime($pr['fecha'])) ?></div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-weight:800; font-size:1.1rem; color:var(--accent-amber);"><?= (float)$pr['peso'] ?> kg × <?= (int)$pr['repeticiones'] ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">1RM estimado: <?= (float)$pr['one_rm_estimado'] ?> kg</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Logros y Trofeos -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="var(--accent-green)"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                        Trofeos Desbloqueados (<?= count($misLogros) ?>)
                    </div>
                    <a href="<?= BASE_URL ?>/logros.php" style="font-size:0.8rem;">Ver todos &rarr;</a>
                </div>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <?php if (empty($misLogros)): ?>
                        <p style="color:var(--text-muted); font-size:0.85rem;">Completá entrenamientos y registros para desbloquear trofeos.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($misLogros, 0, 4) as $logro): ?>
                            <div style="background:var(--bg-secondary); border-radius:var(--radius-sm); padding:0.75rem 1rem; display:flex; align-items:center; gap:0.75rem;">
                                <div style="font-size:1.5rem;">🏆</div>
                                <div>
                                    <div style="font-weight:700; color:var(--text-primary); font-size:0.9rem;"><?= htmlspecialchars($logro['titulo']) ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($logro['descripcion']) ?> (+<?= (int)$logro['xp_recompensa'] ?> XP)</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>
