<?php
/**
 * GYMRAT — Onboarding y Configuración Metabólica Científica
 * Implementación de la fórmula Mifflin-St Jeor con distribución de macronutrientes
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$user = getLoggedUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edad = (int)($_POST['edad'] ?? 25);
    $sexo = $_POST['sexo'] ?? 'masculino';
    $altura = (float)($_POST['altura'] ?? 175);
    $peso = (float)($_POST['peso'] ?? 75);
    $pesoObjetivo = (float)($_POST['peso_objetivo'] ?? $peso);
    $nivelActividad = $_POST['nivel_actividad'] ?? 'moderadamente_activo';
    $objetivo = $_POST['objetivo'] ?? 'mantener_peso';
    $experiencia = $_POST['experiencia'] ?? 'intermedio';

    if ($peso <= 30 || $peso > 350 || $altura <= 100 || $altura > 250 || $edad <= 10 || $edad > 110) {
        $error = 'Por favor ingresá valores antropométricos válidos.';
    } else {
        // Cálculo científico Mifflin-St Jeor
        $nutricion = calculateNutritionalNeeds($peso, $altura, $edad, $sexo, $nivelActividad, $objetivo);

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                UPDATE usuarios SET
                    edad = ?,
                    sexo = ?,
                    altura = ?,
                    peso_actual = ?,
                    peso_inicial = IFNULL(peso_inicial, ?),
                    peso_objetivo = ?,
                    nivel_actividad = ?,
                    objetivo = ?,
                    experiencia = ?,
                    bmr = ?,
                    tdee = ?,
                    calorias_objetivo = ?,
                    proteinas_objetivo = ?,
                    carbohidratos_objetivo = ?,
                    grasas_objetivo = ?,
                    agua_objetivo = ?,
                    pasos_objetivo = ?,
                    onboarding_completado = 1
                WHERE id = ?
            ");

            $stmt->execute([
                $edad,
                $sexo,
                $altura,
                $peso,
                $peso,
                $pesoObjetivo,
                $nivelActividad,
                $objetivo,
                $experiencia,
                $nutricion['bmr'],
                $nutricion['tdee'],
                $nutricion['calorias_objetivo'],
                $nutricion['proteinas_objetivo'],
                $nutricion['carbohidratos_objetivo'],
                $nutricion['grasas_objetivo'],
                $nutricion['agua_objetivo'],
                $nutricion['pasos_objetivo'],
                $user['id']
            ]);

            // Guardar primera medida en el historial
            $measureStmt = $pdo->prepare("INSERT INTO medidas_corporales (usuario_id, fecha, peso, notas) VALUES (?, CURRENT_DATE, ?, 'Peso inicial establecido durante el onboarding')");
            $measureStmt->execute([$user['id'], $peso]);

            // Desbloquear logro inicial si no lo tiene
            checkAndUnlockAchievements($user['id']);

            header("Location: " . BASE_URL . "/dashboard.php?welcome=1");
            exit;
        } catch (Exception $e) {
            error_log("Error guardando onboarding: " . $e->getMessage());
            $error = 'Ocurrió un error al guardar tu perfil metabólico.';
        }
    }
}

$pageTitle = 'Onboarding — Calibrá tu Perfil Metabólico | GYMRAT';
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
        .onboarding-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 2rem;
            max-width: 1050px;
            margin: 2rem auto;
            align-items: start;
        }
        .calculation-preview-box {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.75rem;
            position: sticky;
            top: 2rem;
        }
        @media(max-width: 900px) {
            .onboarding-grid { grid-template-columns: 1fr; }
            .calculation-preview-box { position: static; }
        }
    </style>
</head>
<body style="background-color:var(--bg-primary);">

    <header style="padding:1.5rem 2.5rem; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-subtle); max-width:1100px; margin:0 auto;">
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <div class="brand-logo-icon">
                <svg viewBox="0 0 24 24"><path d="M20.57 14.86L22 13.43 20.57 12 17 15.57 8.43 7 12 3.43 10.57 2 9.14 3.43 7.71 2 5.57 4.14 4.14 2.71 2.71 4.14l1.43 1.43L2 7.71l1.43 1.43L2 10.57 3.43 12 7 8.43 15.57 17 12 20.57 13.43 22l1.43-1.43L16.29 22l2.14-2.14 1.43 1.43 1.43-1.43-1.43-1.43L22 16.29z"/></svg>
            </div>
            <div class="brand-name">GYM<span>RAT</span></div>
        </div>
        <div class="badge badge-green">Paso 1 de 1: Calibración</div>
    </header>

    <main class="page-body">
        <div style="text-align:center; max-width:650px; margin:0 auto 1.5rem;">
            <h2>¡Hola, <?= htmlspecialchars($user['nombre']) ?>! Vamos a personalizar tu plan</h2>
            <p>Configurá tus datos biométricos para calcular tu gasto calórico real con la fórmula científica Mifflin-St Jeor y determinar tus macros de precisión.</p>
        </div>

        <?php if ($error): ?>
            <div style="max-width:1050px; margin:0 auto 1.5rem; background:rgba(244, 63, 94, 0.15); border:1px solid rgba(244, 63, 94, 0.3); color:#fb7185; padding:0.75rem 1rem; border-radius:var(--radius-md);">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="onboarding-grid">
            <!-- Formulario de Parámetros -->
            <div class="card card-glow">
                <form id="onboarding-form" method="POST" action="onboarding.php">
                    <h3 style="margin-bottom:1.25rem;">1. Datos Biométricos</h3>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label" for="onboard_edad">Edad (años)</label>
                            <input type="number" id="onboard_edad" name="edad" class="form-input" min="14" max="99" value="<?= (int)($user['edad'] ?? 25) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="onboard_sexo">Sexo Biológico</label>
                            <select id="onboard_sexo" name="sexo" class="form-select" required>
                                <option value="masculino" <?= (($user['sexo'] ?? 'masculino') === 'masculino') ? 'selected' : '' ?>>Masculino</option>
                                <option value="femenino" <?= (($user['sexo'] ?? '') === 'femenino') ? 'selected' : '' ?>>Femenino</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label class="form-label" for="onboard_altura">Altura (cm)</label>
                            <input type="number" step="0.5" id="onboard_altura" name="altura" class="form-input" value="<?= (float)($user['altura'] ?? 175) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="onboard_peso">Peso Actual (kg)</label>
                            <input type="number" step="0.1" id="onboard_peso" name="peso" class="form-input" value="<?= (float)($user['peso_actual'] ?? 78) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="onboard_peso_objetivo">Peso Objetivo (kg)</label>
                            <input type="number" step="0.1" id="onboard_peso_objetivo" name="peso_objetivo" class="form-input" value="<?= (float)($user['peso_objetivo'] ?? 75) ?>" required>
                        </div>
                    </div>

                    <h3 style="margin:1.5rem 0 1rem;">2. Estilo de Vida y Objetivos</h3>

                    <div class="form-group">
                        <label class="form-label" for="onboard_actividad">Nivel de Actividad Diaria</label>
                        <select id="onboard_actividad" name="nivel_actividad" class="form-select" required>
                            <option value="sedentario">Sedentario (Trabajo de escritorio, poco movimiento) — Factor 1.2</option>
                            <option value="poco_activo">Poco activo (Ejercicio ligero 1-3 días por semana) — Factor 1.375</option>
                            <option value="moderadamente_activo" selected>Moderadamente activo (Entrenamiento 3-5 días/semana) — Factor 1.55</option>
                            <option value="activo">Activo (Entrenamiento intenso 6-7 días/semana) — Factor 1.725</option>
                            <option value="muy_activo">Muy activo (Atleta de alto rendimiento o trabajo físico duro) — Factor 1.9</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="onboard_objetivo">Objetivo Físico Principal</label>
                        <select id="onboard_objetivo" name="objetivo" class="form-select" required>
                            <option value="perder_grasa" selected>Perder Grasa / Definición (-400 kcal de déficit)</option>
                            <option value="mantener_peso">Mantenimiento / Rendimiento atlético (0 kcal)</option>
                            <option value="ganar_masa">Ganar Masa Muscular / Volumen Limpio (+300 kcal superávit)</option>
                            <option value="recomposicion">Recomposición Corporal (-150 kcal leve déficit)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="onboard_experiencia">Experiencia en Entrenamiento de Fuerza</label>
                        <select id="onboard_experiencia" name="experiencia" class="form-select" required>
                            <option value="principiante">Principiante (Menos de 6 meses en el gimnasio)</option>
                            <option value="intermedio" selected>Intermedio (6 meses a 2 años levantando peso)</option>
                            <option value="avanzado">Avanzado (Más de 2 años de sobrecarga progresiva)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-top:1.5rem;">
                        Guardar Objetivos & Entrar al Dashboard &rarr;
                    </button>
                </form>
            </div>

            <!-- Vista Previa de Cálculos Metabólicos Dinámicos -->
            <div class="calculation-preview-box">
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem;">
                    <div class="brand-logo-icon" style="width:30px; height:30px;">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"/></svg>
                    </div>
                    <h3 style="font-size:1.15rem; margin:0;">Cálculo Metabólico</h3>
                </div>
                <p style="font-size:0.8rem; margin-bottom:1.25rem;">Estimación científica basada en Mifflin-St Jeor (1990) calculada en tiempo real:</p>

                <div style="display:flex; flex-direction:column; gap:0.85rem; border-bottom:1px solid var(--border-subtle); padding-bottom:1rem; margin-bottom:1rem;">
                    <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                        <span style="color:var(--text-muted);">Metabolismo Basal (BMR):</span>
                        <strong id="calc_bmr" style="color:var(--text-primary);">1.750 kcal</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                        <span style="color:var(--text-muted);">Gasto Diario Total (TDEE):</span>
                        <strong id="calc_tdee" style="color:var(--text-primary);">2.712 kcal</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                        <span style="color:var(--text-muted);">Ajuste por Objetivo:</span>
                        <strong id="calc_delta" style="color:var(--accent-rose);">-400 kcal (Déficit)</strong>
                    </div>
                </div>

                <div style="background:rgba(0, 245, 155, 0.06); border:1px solid rgba(0, 245, 155, 0.2); border-radius:var(--radius-md); padding:1rem; text-align:center; margin-bottom:1.25rem;">
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--accent-green); letter-spacing:0.06em;">Meta Calórica Diaria</div>
                    <div id="calc_target_kcal" style="font-family:var(--font-heading); font-size:2.2rem; font-weight:900; color:var(--text-primary); line-height:1.2;">
                        2.312 kcal
                    </div>
                </div>

                <div style="font-size:0.8rem; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.75rem;">Macronutrientes Óptimos:</div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.5rem; text-align:center;">
                    <div style="background:var(--bg-input); padding:0.6rem; border-radius:var(--radius-sm); border:1px solid var(--border-subtle);">
                        <div style="font-size:0.7rem; color:var(--accent-green); font-weight:700;">PROTEÍNA</div>
                        <div id="calc_protein" style="font-size:1.05rem; font-weight:800;">156 g</div>
                        <div style="font-size:0.65rem; color:var(--text-muted);">2.0g / kg</div>
                    </div>
                    <div style="background:var(--bg-input); padding:0.6rem; border-radius:var(--radius-sm); border:1px solid var(--border-subtle);">
                        <div style="font-size:0.7rem; color:var(--accent-blue); font-weight:700;">CARBOS</div>
                        <div id="calc_carbs" style="font-size:1.05rem; font-weight:800;">254 g</div>
                        <div style="font-size:0.65rem; color:var(--text-muted);">Energía</div>
                    </div>
                    <div style="background:var(--bg-input); padding:0.6rem; border-radius:var(--radius-sm); border:1px solid var(--border-subtle);">
                        <div style="font-size:0.7rem; color:var(--accent-amber); font-weight:700;">GRASAS</div>
                        <div id="calc_fats" style="font-size:1.05rem; font-weight:800;">70 g</div>
                        <div style="font-size:0.65rem; color:var(--text-muted);">Hormonal</div>
                    </div>
                </div>

                <div style="margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
                    <span>💧 Meta de Agua: <strong id="calc_water" style="color:var(--text-primary);">2.730 ml</strong></span>
                    <span>👟 Pasos: <strong style="color:var(--text-primary);">10.000</strong></span>
                </div>
            </div>
        </div>
    </main>

    <script src="<?= BASE_URL ?>/assets/js/auth.js"></script>
</body>
</html>
