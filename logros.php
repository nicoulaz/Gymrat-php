<?php
/**
 * GYMRAT — Centro de Gamificación & Logros Deportivos
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$user = getLoggedUser();
$pdo = getDBConnection();

// Calcular XP requerido para el próximo nivel
$nivelActual = (int)$user['nivel'];
$xpActual = (int)$user['puntos_xp'];
$xpSiguienteNivel = (int)(pow($nivelActual, 2) * 50);
$xpNivelBase = (int)(pow($nivelActual - 1, 2) * 50);
$rangoXp = max(1, $xpSiguienteNivel - $xpNivelBase);
$progresoXp = min(100, (int)round((($xpActual - $xpNivelBase) / $rangoXp) * 100));

// Todos los logros del sistema
$stmtLogros = $pdo->query("SELECT * FROM logros ORDER BY categoria ASC, id ASC");
$todosLogros = $stmtLogros->fetchAll();

// Logros que el usuario tiene desbloqueados
$stmtUserLogros = $pdo->prepare("SELECT logro_id, fecha_obtenido FROM usuario_logros WHERE usuario_id = ?");
$stmtUserLogros->execute([$user['id']]);
$userLogrosMap = $stmtUserLogros->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Logros & Gamificación — GYMRAT';
$pageSubtitle = 'Desbloqueá insignias y aumentá tu nivel de atleta a través de la constancia';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="page-body">
        <!-- Tarjeta de Nivel y Experiencia (XP) -->
        <div class="card card-glow" style="margin-bottom:2rem; background:linear-gradient(135deg, var(--bg-card) 0%, rgba(168, 85, 247, 0.08) 100%);">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.5rem;">
                <div style="display:flex; align-items:center; gap:1.25rem;">
                    <div style="width:72px; height:72px; border-radius:var(--radius-lg); background:linear-gradient(135deg, #a855f7 0%, #7e22ce 100%); display:flex; align-items:center; justify-content:center; font-size:2.2rem; box-shadow:0 6px 20px rgba(168, 85, 247, 0.4);">
                        ⚡
                    </div>
                    <div>
                        <div style="font-size:0.8rem; text-transform:uppercase; font-weight:700; color:var(--accent-purple); letter-spacing:0.06em;">Rango de Atleta</div>
                        <h2 style="font-size:2rem; margin:0.2rem 0;">Nivel <?= $nivelActual ?></h2>
                        <p style="font-size:0.85rem; margin:0;"><?= number_format($xpActual, 0, ',', '.') ?> XP acumulados • Próximo nivel a los <?= number_format($xpSiguienteNivel, 0, ',', '.') ?> XP</p>
                    </div>
                </div>

                <div style="min-width:280px; flex:1; max-width:400px;">
                    <div style="display:flex; justify-content:space-between; font-size:0.85rem; font-weight:700; margin-bottom:0.4rem;">
                        <span>Progreso de Nivel</span>
                        <span style="color:var(--accent-purple);"><?= $progresoXp ?>%</span>
                    </div>
                    <div class="progress-bar-container" style="height:10px;">
                        <div class="progress-bar-fill purple" style="width: <?= $progresoXp ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-bottom:1.5rem;">
            <h2>Catálogo de Logros e Insignias</h2>
            <p>Desbloqueados: <strong><?= count($userLogrosMap) ?></strong> de <?= count($todosLogros) ?> trofeos disponibles</p>
        </div>

        <!-- Grilla de Logros -->
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1.25rem;">
            <?php foreach ($todosLogros as $l): ?>
                <?php
                $desbloqueado = isset($userLogrosMap[$l['id']]);
                $fechaObtenido = $desbloqueado ? $userLogrosMap[$l['id']] : null;
                ?>
                <div class="card" style="opacity: <?= $desbloqueado ? '1' : '0.45' ?>; border-color: <?= $desbloqueado ? 'rgba(0, 245, 155, 0.25)' : 'var(--border-subtle)' ?>;">
                    <div style="display:flex; align-items:flex-start; gap:1rem;">
                        <div style="width:50px; height:50px; border-radius:var(--radius-md); background: <?= $desbloqueado ? 'rgba(0, 245, 155, 0.12)' : 'rgba(255, 255, 255, 0.04)' ?>; display:flex; align-items:center; justify-content:center; font-size:1.6rem; flex-shrink:0;">
                            <?= $desbloqueado ? '🏆' : '🔒' ?>
                        </div>
                        <div style="flex:1;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <h4 style="font-size:1rem; margin-bottom:0.25rem; color:var(--text-primary);"><?= htmlspecialchars($l['titulo']) ?></h4>
                                <span class="badge <?= $desbloqueado ? 'badge-green' : 'badge-secondary' ?>" style="font-size:0.65rem;">+<?= (int)$l['xp_recompensa'] ?> XP</span>
                            </div>
                            <p style="font-size:0.8rem; margin-bottom:0.5rem; line-height:1.4;"><?= htmlspecialchars($l['descripcion']) ?></p>
                            
                            <?php if ($desbloqueado): ?>
                                <span style="font-size:0.7rem; color:var(--accent-green); font-weight:700;">
                                    ✓ Desbloqueado el <?= date('d/m/Y', strtotime($fechaObtenido)) ?>
                                </span>
                            <?php else: ?>
                                <span style="font-size:0.7rem; color:var(--text-muted);">
                                    Bloqueado
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>
