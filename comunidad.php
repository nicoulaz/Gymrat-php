<?php
/**
 * GYMRAT — Módulo Social de Comunidad & Feed de Actividad
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();

// 1. Obtener Feed de Actividad Social
$feedStmt = $pdo->prepare("
    SELECT f.*, u.nombre, u.apellido, u.username, u.foto_perfil, u.nivel 
    FROM feed_actividad f
    JOIN usuarios u ON f.usuario_id = u.id
    WHERE u.perfil_publico = 1 OR u.id = ?
    ORDER BY f.created_at DESC 
    LIMIT 25
");
$feedStmt->execute([$user['id']]);
$feedItems = $feedStmt->fetchAll();

// 2. Obtener lista de atletas sugeridos de la comunidad
$usersStmt = $pdo->prepare("
    SELECT u.*, 
        (SELECT COUNT(*) FROM comunidad_seguidores WHERE seguido_id = u.id) as seguidores_count,
        (SELECT COUNT(*) FROM comunidad_seguidores WHERE seguidor_id = ? AND seguido_id = u.id) as lo_sigo
    FROM usuarios u
    WHERE u.id != ?
    ORDER BY seguidores_count DESC 
    LIMIT 10
");
$usersStmt->execute([$user['id'], $user['id']]);
$atletas = $usersStmt->fetchAll();

$pageTitle = 'Comunidad & Atletas — GYMRAT';
$pageSubtitle = 'Feed social de entrenamientos, récords personales y motivación atlética';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-body">
        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:2rem; align-items:start;">
            <!-- Columna Izquierda: Feed Social -->
            <div>
                <div style="margin-bottom:1.5rem;">
                    <h2>Feed de Actividad</h2>
                    <p>Celebrá los nuevos récords y progresos de la comunidad de atletas Gymrat.</p>
                </div>

                <div style="display:flex; flex-direction:column; gap:1.25rem;">
                    <?php if (empty($feedItems)): ?>
                        <div class="empty-state">
                            <div class="empty-state-title">No hay actividad reciente en el feed</div>
                            <div class="empty-state-desc">Comenzá a registrar entrenamientos y seguir atletas para ver actividad aquí.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($feedItems as $item): ?>
                            <?php
                            $datos = !empty($item['datos_json']) ? json_decode($item['datos_json'], true) : [];
                            $icon = '💪';
                            if ($item['tipo'] === 'pr') $icon = '🔥';
                            elseif ($item['tipo'] === 'logro') $icon = '🏆';
                            elseif ($item['tipo'] === 'meta_completada') $icon = '⚡';
                            ?>
                            <div class="card">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.85rem;">
                                    <div style="display:flex; align-items:center; gap:0.75rem;">
                                        <a href="<?= BASE_URL ?>/perfil.php?user=<?= urlencode($item['username']) ?>" style="text-decoration:none;">
                                            <img src="<?= BASE_URL ?>/assets/img/default-avatar.svg" alt="<?= htmlspecialchars($item['username']) ?>" style="width:42px; height:42px; border-radius:50%; border:2px solid var(--border-color); object-fit:cover;">
                                        </a>
                                        <div>
                                            <div style="font-weight:700; color:var(--text-primary);">
                                                <a href="<?= BASE_URL ?>/perfil.php?user=<?= urlencode($item['username']) ?>" style="color:var(--text-primary); text-decoration:none;">
                                                    <?= htmlspecialchars($item['nombre'] . ' ' . $item['apellido']) ?>
                                                </a>
                                                <span class="badge badge-green" style="font-size:0.65rem; margin-left:0.35rem;">Nv. <?= (int)$item['nivel'] ?></span>
                                            </div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);">
                                                @<?= htmlspecialchars($item['username']) ?> • <?= date('d M, H:i', strtotime($item['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <span style="font-size:1.3rem;"><?= $icon ?></span>
                                </div>

                                <h4 style="font-size:1.1rem; margin-bottom:0.4rem; color:var(--text-primary);">
                                    <?= htmlspecialchars($item['titulo']) ?>
                                </h4>
                                <p style="font-size:0.9rem; margin-bottom:1rem; color:var(--text-secondary);">
                                    <?= htmlspecialchars($item['descripcion'] ?: '') ?>
                                </p>

                                <?php if (!empty($datos)): ?>
                                    <div style="background:var(--bg-secondary); border-radius:var(--radius-sm); padding:0.65rem 0.85rem; font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem; display:flex; gap:1.25rem; flex-wrap:wrap;">
                                        <?php if (!empty($datos['volumen'])): ?>
                                            <span>Volumen: <strong style="color:var(--accent-green);"><?= $datos['volumen'] ?></strong></span>
                                        <?php endif; ?>
                                        <?php if (!empty($datos['duracion'])): ?>
                                            <span>Tiempo: <strong style="color:var(--text-primary);"><?= $datos['duracion'] ?></strong></span>
                                        <?php endif; ?>
                                        <?php if (!empty($datos['one_rm'])): ?>
                                            <span>1RM Estimado: <strong style="color:var(--accent-amber);"><?= $datos['one_rm'] ?> kg</strong></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-subtle); padding-top:0.75rem;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="likeFeedPost(<?= $item['id'] ?>, this)">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="var(--text-muted)"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                        <span class="like-count"><?= (int)$item['likes_count'] ?></span>
                                    </button>
                                    <span style="font-size:0.75rem; color:var(--text-muted);">Comunidad Gymrat</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna Derecha: Atletas Recomendados y Búsqueda -->
            <div>
                <div class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header" style="margin-bottom:1rem;">
                        <div class="card-title" style="font-size:1.1rem;">Atletas de la Comunidad</div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:0.85rem;">
                        <?php foreach ($atletas as $atleta): ?>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding-bottom:0.75rem; border-bottom:1px solid var(--border-subtle);">
                                <div style="display:flex; align-items:center; gap:0.65rem;">
                                    <a href="<?= BASE_URL ?>/perfil.php?user=<?= urlencode($atleta['username']) ?>">
                                        <img src="<?= BASE_URL ?>/assets/img/default-avatar.svg" alt="<?= htmlspecialchars($atleta['username']) ?>" style="width:36px; height:36px; border-radius:50%; border:1px solid var(--border-color); object-fit:cover;">
                                    </a>
                                    <div>
                                        <a href="<?= BASE_URL ?>/perfil.php?user=<?= urlencode($atleta['username']) ?>" style="font-size:0.85rem; font-weight:700; color:var(--text-primary); text-decoration:none; display:block;">
                                            <?= htmlspecialchars($atleta['nombre'] . ' ' . $atleta['apellido']) ?>
                                        </a>
                                        <span style="font-size:0.75rem; color:var(--text-muted);"><?= (int)$atleta['seguidores_count'] ?> seguidores</span>
                                    </div>
                                </div>
                                <button type="button" 
                                        class="btn <?= $atleta['lo_sigo'] ? 'btn-secondary' : 'btn-outline' ?> btn-sm" 
                                        data-following="<?= $atleta['lo_sigo'] ? 'true' : 'false' ?>" 
                                        onclick="toggleFollowUser(<?= $atleta['id'] ?>, this)">
                                    <?= $atleta['lo_sigo'] ? 'Siguiendo' : '+ Seguir' ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script src="<?= BASE_URL ?>/assets/js/community.js"></script>
</div>
