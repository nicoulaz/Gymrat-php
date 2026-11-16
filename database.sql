-- ==============================================================================
-- GYMRAT - Base de Datos MySQL Oficial
-- Arquitectura Relacional InnoDB con Normalización, Integridad y Datos Semilla
-- ==============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notificaciones`;
DROP TABLE IF EXISTS `recordatorios`;
DROP TABLE IF EXISTS `feed_actividad`;
DROP TABLE IF EXISTS `comunidad_seguidores`;
DROP TABLE IF EXISTS `usuario_logros`;
DROP TABLE IF EXISTS `logros`;
DROP TABLE IF EXISTS `fotos_progreso`;
DROP TABLE IF EXISTS `medidas_corporales`;
DROP TABLE IF EXISTS `records_personales`;
DROP TABLE IF EXISTS `entrenamiento_series`;
DROP TABLE IF EXISTS `entrenamientos`;
DROP TABLE IF EXISTS `rutina_dia_ejercicios`;
DROP TABLE IF EXISTS `rutina_dias`;
DROP TABLE IF EXISTS `rutinas`;
DROP TABLE IF EXISTS `ejercicios`;
DROP TABLE IF EXISTS `registro_pasos`;
DROP TABLE IF EXISTS `registro_agua`;
DROP TABLE IF EXISTS `recetas`;
DROP TABLE IF EXISTS `comidas_guardadas`;
DROP TABLE IF EXISTS `registro_comidas`;
DROP TABLE IF EXISTS `alimentos`;
DROP TABLE IF EXISTS `usuarios`;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- 1. TABLA: usuarios
-- ------------------------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `fecha_nacimiento` DATE NULL,
  `edad` INT DEFAULT 25,
  `sexo` ENUM('masculino', 'femenino', 'otro') DEFAULT 'masculino',
  `peso_actual` DECIMAL(5,2) DEFAULT 80.00,
  `peso_inicial` DECIMAL(5,2) DEFAULT 84.00,
  `peso_objetivo` DECIMAL(5,2) DEFAULT 78.00,
  `altura` DECIMAL(5,2) DEFAULT 178.00,
  `nivel_actividad` ENUM('sedentario', 'poco_activo', 'moderadamente_activo', 'activo', 'muy_activo') DEFAULT 'moderadamente_activo',
  `objetivo` ENUM('perder_grasa', 'mantener_peso', 'ganar_masa', 'recomposicion', 'personalizado') DEFAULT 'perder_grasa',
  `experiencia` ENUM('principiante', 'intermedio', 'avanzado') DEFAULT 'intermedio',
  `bmr` INT DEFAULT 1812,
  `tdee` INT DEFAULT 2809,
  `calorias_objetivo` INT DEFAULT 2400,
  `proteinas_objetivo` INT DEFAULT 190,
  `carbohidratos_objetivo` INT DEFAULT 240,
  `grasas_objetivo` INT DEFAULT 70,
  `agua_objetivo` INT DEFAULT 2500,
  `pasos_objetivo` INT DEFAULT 10000,
  `tipo_cuenta` ENUM('free', 'premium') DEFAULT 'premium',
  `rol` ENUM('usuario', 'admin') DEFAULT 'usuario',
  `foto_perfil` VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
  `biografia` TEXT NULL,
  `streak_dias` INT DEFAULT 42,
  `ultimo_streak_fecha` DATE NULL,
  `nivel` INT DEFAULT 12,
  `puntos_xp` INT DEFAULT 4850,
  `perfil_publico` TINYINT(1) DEFAULT 1,
  `entrenamientos_publicos` TINYINT(1) DEFAULT 1,
  `prs_publicos` TINYINT(1) DEFAULT 1,
  `fotos_publicas` TINYINT(1) DEFAULT 0,
  `estadisticas_publicas` TINYINT(1) DEFAULT 1,
  `unidad_peso` ENUM('kg', 'lb') DEFAULT 'kg',
  `unidad_altura` ENUM('cm', 'ft') DEFAULT 'cm',
  `unidad_agua` ENUM('ml', 'oz') DEFAULT 'ml',
  `onboarding_completado` TINYINT(1) DEFAULT 1,
  `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acceso` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_usuarios_email` (`email`),
  INDEX `idx_usuarios_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. TABLA: alimentos
-- ------------------------------------------------------------------------------
CREATE TABLE `alimentos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `marca` VARCHAR(100) NULL,
  `categoria` VARCHAR(50) NOT NULL,
  `calorias` DECIMAL(6,2) NOT NULL,
  `proteinas` DECIMAL(5,2) NOT NULL,
  `carbohidratos` DECIMAL(5,2) NOT NULL,
  `grasas` DECIMAL(5,2) NOT NULL,
  `fibra` DECIMAL(5,2) DEFAULT 0.00,
  `azucar` DECIMAL(5,2) DEFAULT 0.00,
  `sodio` DECIMAL(6,2) DEFAULT 0.00,
  `tamano_porcion` DECIMAL(6,2) DEFAULT 100.00,
  `unidad` VARCHAR(20) DEFAULT 'g',
  `codigo_barras` VARCHAR(50) NULL,
  `es_favorito` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_alimentos_nombre` (`nombre`),
  INDEX `idx_alimentos_categoria` (`categoria`),
  INDEX `idx_alimentos_barcode` (`codigo_barras`),
  CONSTRAINT `fk_alimentos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. TABLA: registro_comidas
-- ------------------------------------------------------------------------------
CREATE TABLE `registro_comidas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `tipo_comida` ENUM('desayuno', 'media_manana', 'almuerzo', 'merienda', 'cena', 'otros') NOT NULL,
  `alimento_id` INT NULL,
  `nombre_personalizado` VARCHAR(150) NULL,
  `cantidad` DECIMAL(6,2) NOT NULL,
  `unidad` VARCHAR(20) DEFAULT 'g',
  `calorias` DECIMAL(6,2) NOT NULL,
  `proteinas` DECIMAL(5,2) NOT NULL,
  `carbohidratos` DECIMAL(5,2) NOT NULL,
  `grasas` DECIMAL(5,2) NOT NULL,
  `foto_comida` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_regcomidas_usuario_fecha` (`usuario_id`, `fecha`),
  CONSTRAINT `fk_regcomidas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_regcomidas_alimento` FOREIGN KEY (`alimento_id`) REFERENCES `alimentos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. TABLA: comidas_guardadas (Comidas frecuentes / plantillas rápidas)
-- ------------------------------------------------------------------------------
CREATE TABLE `comidas_guardadas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `tipo_comida` VARCHAR(50) DEFAULT 'desayuno',
  `datos_json` LONGTEXT NOT NULL,
  `total_calorias` DECIMAL(6,2) NOT NULL,
  `total_proteinas` DECIMAL(5,2) NOT NULL,
  `total_carbohidratos` DECIMAL(5,2) NOT NULL,
  `total_grasas` DECIMAL(5,2) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_comidasguardadas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. TABLA: recetas
-- ------------------------------------------------------------------------------
CREATE TABLE `recetas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `porciones` INT DEFAULT 1,
  `ingredientes_json` LONGTEXT NOT NULL,
  `calorias_total` DECIMAL(6,2) NOT NULL,
  `calorias_porcion` DECIMAL(6,2) NOT NULL,
  `proteinas_porcion` DECIMAL(5,2) NOT NULL,
  `carbohidratos_porcion` DECIMAL(5,2) NOT NULL,
  `grasas_porcion` DECIMAL(5,2) NOT NULL,
  `instrucciones` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_recetas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. TABLA: registro_agua
-- ------------------------------------------------------------------------------
CREATE TABLE `registro_agua` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `cantidad_ml` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_usuario_fecha_agua` (`usuario_id`, `fecha`),
  CONSTRAINT `fk_agua_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. TABLA: registro_pasos
-- ------------------------------------------------------------------------------
CREATE TABLE `registro_pasos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `pasos` INT NOT NULL DEFAULT 0,
  `distancia_km` DECIMAL(5,2) DEFAULT 0.00,
  `calorias_quemadas` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_usuario_fecha_pasos` (`usuario_id`, `fecha`),
  CONSTRAINT `fk_pasos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 8. TABLA: ejercicios
-- ------------------------------------------------------------------------------
CREATE TABLE `ejercicios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(150) NOT NULL,
  `musculo_principal` VARCHAR(50) NOT NULL,
  `musculos_secundarios` VARCHAR(150) NULL,
  `categoria` ENUM('pecho','espalda','biceps','triceps','hombros','cuadriceps','femorales','gluteos','gemelos','abdominales','antebrazos','trapecios','cardio','cuerpo_completo') NOT NULL,
  `dificultad` ENUM('principiante','intermedio','avanzado') DEFAULT 'intermedio',
  `equipamiento` ENUM('barra','mancuernas','maquina','polea','peso_corporal','kettlebell','bandas','otro') DEFAULT 'barra',
  `descripcion` TEXT NULL,
  `instrucciones` TEXT NULL,
  `consejos` TEXT NULL,
  `errores_frecuentes` TEXT NULL,
  `imagen` VARCHAR(255) NULL,
  `video_url` VARCHAR(255) NULL,
  `es_predeterminado` TINYINT(1) DEFAULT 1,
  INDEX `idx_ejercicios_categoria` (`categoria`),
  INDEX `idx_ejercicios_musculo` (`musculo_principal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 9. TABLA: rutinas
-- ------------------------------------------------------------------------------
CREATE TABLE `rutinas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT NULL,
  `dias_semana` VARCHAR(100) NULL,
  `es_plantilla` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rutinas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 10. TABLA: rutina_dias
-- ------------------------------------------------------------------------------
CREATE TABLE `rutina_dias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rutina_id` INT NOT NULL,
  `nombre_dia` VARCHAR(100) NOT NULL,
  `orden` INT DEFAULT 1,
  CONSTRAINT `fk_rutinadias_rutina` FOREIGN KEY (`rutina_id`) REFERENCES `rutinas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 11. TABLA: rutina_dia_ejercicios
-- ------------------------------------------------------------------------------
CREATE TABLE `rutina_dia_ejercicios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rutina_dia_id` INT NOT NULL,
  `ejercicio_id` INT NOT NULL,
  `orden` INT DEFAULT 1,
  `series_objetivo` INT DEFAULT 3,
  `repeticiones_objetivo` VARCHAR(20) DEFAULT '8-12',
  `descanso_segundos` INT DEFAULT 90,
  CONSTRAINT `fk_rutinadiaejercicios_dia` FOREIGN KEY (`rutina_dia_id`) REFERENCES `rutina_dias`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rutinadiaejercicios_ejercicio` FOREIGN KEY (`ejercicio_id`) REFERENCES `ejercicios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 12. TABLA: entrenamientos
-- ------------------------------------------------------------------------------
CREATE TABLE `entrenamientos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `rutina_id` INT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `fecha` DATE NOT NULL,
  `hora_inicio` TIME NULL,
  `hora_fin` TIME NULL,
  `duracion_segundos` INT DEFAULT 0,
  `volumen_total` DECIMAL(10,2) DEFAULT 0.00,
  `series_totales` INT DEFAULT 0,
  `repeticiones_totales` INT DEFAULT 0,
  `prs_conseguidos` INT DEFAULT 0,
  `estado` ENUM('en_progreso', 'completado', 'cancelado') DEFAULT 'completado',
  `notas` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_entrenamientos_usuario_fecha` (`usuario_id`, `fecha`),
  CONSTRAINT `fk_entrenamientos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_entrenamientos_rutina` FOREIGN KEY (`rutina_id`) REFERENCES `rutinas`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 13. TABLA: entrenamiento_series
-- ------------------------------------------------------------------------------
CREATE TABLE `entrenamiento_series` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `entrenamiento_id` INT NOT NULL,
  `ejercicio_id` INT NOT NULL,
  `numero_serie` INT NOT NULL,
  `tipo_serie` ENUM('normal','calentamiento','fallo','dropset','rest_pause','amrap','backoff') DEFAULT 'normal',
  `peso` DECIMAL(6,2) NOT NULL,
  `repeticiones` INT NOT NULL,
  `rir` INT NULL,
  `rpe` DECIMAL(3,1) NULL,
  `completada` TINYINT(1) DEFAULT 1,
  `es_pr` TINYINT(1) DEFAULT 0,
  `one_rm_estimado` DECIMAL(6,2) NULL,
  `volumen` DECIMAL(8,2) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_series_entrenamiento` (`entrenamiento_id`),
  INDEX `idx_series_ejercicio` (`ejercicio_id`),
  CONSTRAINT `fk_series_entrenamiento` FOREIGN KEY (`entrenamiento_id`) REFERENCES `entrenamientos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_series_ejercicio` FOREIGN KEY (`ejercicio_id`) REFERENCES `ejercicios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 14. TABLA: records_personales (PRs)
-- ------------------------------------------------------------------------------
CREATE TABLE `records_personales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `ejercicio_id` INT NOT NULL,
  `peso` DECIMAL(6,2) NOT NULL,
  `repeticiones` INT NOT NULL,
  `one_rm_estimado` DECIMAL(6,2) NOT NULL,
  `volumen_serie` DECIMAL(8,2) NOT NULL,
  `tipo_record` ENUM('peso_maximo', 'repeticiones_max', '1rm_max', 'volumen_max') DEFAULT '1rm_max',
  `fecha` DATE NOT NULL,
  `entrenamiento_id` INT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_prs_usuario_ejercicio` (`usuario_id`, `ejercicio_id`),
  CONSTRAINT `fk_prs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prs_ejercicio` FOREIGN KEY (`ejercicio_id`) REFERENCES `ejercicios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prs_entrenamiento` FOREIGN KEY (`entrenamiento_id`) REFERENCES `entrenamientos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 15. TABLA: medidas_corporales
-- ------------------------------------------------------------------------------
CREATE TABLE `medidas_corporales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `peso` DECIMAL(5,2) NULL,
  `grasa_corporal` DECIMAL(4,1) NULL,
  `cintura` DECIMAL(5,2) NULL,
  `pecho` DECIMAL(5,2) NULL,
  `brazo_izq` DECIMAL(5,2) NULL,
  `brazo_der` DECIMAL(5,2) NULL,
  `antebrazo` DECIMAL(5,2) NULL,
  `muslo_izq` DECIMAL(5,2) NULL,
  `muslo_der` DECIMAL(5,2) NULL,
  `gemelo` DECIMAL(5,2) NULL,
  `cadera` DECIMAL(5,2) NULL,
  `cuello` DECIMAL(5,2) NULL,
  `notas` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_medidas_usuario_fecha` (`usuario_id`, `fecha`),
  CONSTRAINT `fk_medidas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 16. TABLA: fotos_progreso
-- ------------------------------------------------------------------------------
CREATE TABLE `fotos_progreso` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `categoria` ENUM('frente', 'espalda', 'perfil_izquierdo', 'perfil_derecho', 'personalizada') DEFAULT 'frente',
  `foto_url` VARCHAR(255) NOT NULL,
  `peso` DECIMAL(5,2) NULL,
  `es_publica` TINYINT(1) DEFAULT 0,
  `notas` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_fotos_usuario_fecha` (`usuario_id`, `fecha`),
  CONSTRAINT `fk_fotos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 17. TABLA: logros y usuario_logros (Gamificación)
-- ------------------------------------------------------------------------------
CREATE TABLE `logros` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) NOT NULL UNIQUE,
  `categoria` ENUM('constancia', 'fuerza', 'nutricion', 'habitos') NOT NULL,
  `titulo` VARCHAR(100) NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `icono` VARCHAR(50) DEFAULT 'trophy',
  `xp_recompensa` INT DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario_logros` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `logro_id` INT NOT NULL,
  `fecha_obtenido` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `visto` TINYINT(1) DEFAULT 0,
  UNIQUE KEY `uk_usuario_logro` (`usuario_id`, `logro_id`),
  CONSTRAINT `fk_usuariologros_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usuariologros_logro` FOREIGN KEY (`logro_id`) REFERENCES `logros`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 18. TABLA: comunidad_seguidores
-- ------------------------------------------------------------------------------
CREATE TABLE `comunidad_seguidores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `seguidor_id` INT NOT NULL,
  `seguido_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_seguidor_seguido` (`seguidor_id`, `seguido_id`),
  CONSTRAINT `fk_seguidores_seguidor` FOREIGN KEY (`seguidor_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seguidores_seguido` FOREIGN KEY (`seguido_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 19. TABLA: feed_actividad
-- ------------------------------------------------------------------------------
CREATE TABLE `feed_actividad` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `tipo` ENUM('entrenamiento', 'pr', 'logro', 'meta_completada') NOT NULL,
  `referencia_id` INT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NULL,
  `datos_json` LONGTEXT NULL,
  `likes_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_feed_created` (`created_at`),
  CONSTRAINT `fk_feed_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 20. TABLA: recordatorios
-- ------------------------------------------------------------------------------
CREATE TABLE `recordatorios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `tipo` ENUM('entrenar', 'comer', 'agua', 'pesar', 'pasos', 'progreso') NOT NULL,
  `hora` TIME NOT NULL,
  `dias_semana` VARCHAR(50) DEFAULT 'todos',
  `activo` TINYINT(1) DEFAULT 1,
  `mensaje` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_recordatorios_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 21. TABLA: notificaciones
-- ------------------------------------------------------------------------------
CREATE TABLE `notificaciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `tipo` VARCHAR(50) NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `leido` TINYINT(1) DEFAULT 0,
  `enlace` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notificaciones_usuario` (`usuario_id`, `leido`),
  CONSTRAINT `fk_notificaciones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- INSERCIÓN DE DATOS SEMILLA (SEED DATA)
-- ==============================================================================

-- 1. Usuarios Demo
-- Contraseña de todos los usuarios demo: password123
-- Hash bcrypt verificado: $2y$10$VC7R7No33j6L.6y/wCqTgucK8722BZjmEB6VQXgcoJMpVkvtp5Hu.
INSERT INTO `usuarios` 
(`id`, `nombre`, `apellido`, `username`, `email`, `password`, `fecha_nacimiento`, `edad`, `sexo`, `peso_actual`, `peso_inicial`, `peso_objetivo`, `altura`, `nivel_actividad`, `objetivo`, `experiencia`, `bmr`, `tdee`, `calorias_objetivo`, `proteinas_objetivo`, `carbohidratos_objetivo`, `grasas_objetivo`, `agua_objetivo`, `pasos_objetivo`, `tipo_cuenta`, `rol`, `biografia`, `streak_dias`, `nivel`, `puntos_xp`, `onboarding_completado`) 
VALUES
(1, 'Nicolás', 'Gómez', 'nicolas_fit', 'demo@gymrat.com', '$2y$10$VC7R7No33j6L.6y/wCqTgucK8722BZjmEB6VQXgcoJMpVkvtp5Hu.', '2000-05-15', 26, 'masculino', 82.40, 85.00, 78.00, 178.00, 'moderadamente_activo', 'perder_grasa', 'intermedio', 1812, 2809, 2400, 190, 240, 70, 2500, 10000, 'premium', 'admin', 'Enfocado en la sobrecarga progresiva y nutrición limpia. Road to 78kg 💪', 42, 12, 4850, 1),
(2, 'Valentina', 'Ríos', 'valen_strong', 'valen@gymrat.com', '$2y$10$VC7R7No33j6L.6y/wCqTgucK8722BZjmEB6VQXgcoJMpVkvtp5Hu.', '1998-09-20', 28, 'femenino', 63.50, 65.00, 62.00, 165.00, 'activo', 'recomposicion', 'avanzado', 1420, 2450, 2100, 140, 230, 55, 2800, 12000, 'premium', 'usuario', 'Powerlifting athlete & coach. Disciplina sobre motivación.', 38, 15, 6200, 1),
(3, 'Lucas', 'Mendoza', 'lucas_lift', 'lucas@gymrat.com', '$2y$10$VC7R7No33j6L.6y/wCqTgucK8722BZjmEB6VQXgcoJMpVkvtp5Hu.', '2001-02-10', 25, 'masculino', 88.00, 82.00, 92.00, 182.00, 'muy_activo', 'ganar_masa', 'intermedio', 1950, 3100, 3400, 200, 420, 85, 3500, 10000, 'free', 'usuario', 'Volumen limpio en progreso. Apasionado del culturismo clásico.', 19, 8, 2900, 1),
(4, 'Sofía', 'Martínez', 'sofia_active', 'sofia@gymrat.com', '$2y$10$VC7R7No33j6L.6y/wCqTgucK8722BZjmEB6VQXgcoJMpVkvtp5Hu.', '2002-11-04', 24, 'femenino', 58.00, 60.00, 57.00, 162.00, 'activo', 'mantener_peso', 'principiante', 1350, 2200, 2200, 120, 260, 50, 2200, 11000, 'free', 'usuario', 'Running matutino, fuerza funcional y buena energía todos los días 🏃‍♀️', 14, 6, 1750, 1);

-- 2. Base de Datos Amplia de Alimentos (50+ Alimentos comunes con macros reales por 100g y Códigos de Barras)
INSERT INTO `alimentos` 
(`id`, `usuario_id`, `nombre`, `marca`, `categoria`, `calorias`, `proteinas`, `carbohidratos`, `grasas`, `fibra`, `azucar`, `sodio`, `tamano_porcion`, `unidad`, `codigo_barras`, `es_favorito`) 
VALUES
(1, NULL, 'Pechuga de Pollo hervida/plancha', 'Genérico', 'Carnes y Aves', 165.00, 31.00, 0.00, 3.60, 0.00, 0.00, 74.00, 100.00, 'g', '779001000001', 1),
(2, NULL, 'Arroz Blanco cocido', 'Genérico', 'Cereales y Granos', 130.00, 2.70, 28.20, 0.30, 0.40, 0.10, 1.00, 100.00, 'g', '779001000002', 1),
(3, NULL, 'Huevos Enteros (unidad media ~50g)', 'Granja', 'Huevos y Lácteos', 143.00, 12.60, 0.70, 9.50, 0.00, 0.40, 142.00, 100.00, 'g', '779001000003', 1),
(4, NULL, 'Claras de Huevo', 'Granja', 'Huevos y Lácteos', 52.00, 10.90, 0.70, 0.20, 0.00, 0.70, 166.00, 100.00, 'g', '779001000004', 0),
(5, NULL, 'Avena Tradicional en copos', 'Quaker', 'Cereales y Granos', 389.00, 16.90, 66.30, 6.90, 10.60, 1.00, 2.00, 100.00, 'g', '779001000005', 1),
(6, NULL, 'Banana / Plátano maduro', 'Frutería', 'Frutas', 89.00, 1.10, 22.80, 0.30, 2.60, 12.20, 1.00, 100.00, 'g', '779001000006', 1),
(7, NULL, 'Palta / Aguacate Hass', 'Frutería', 'Grasas Saludables', 160.00, 2.00, 8.50, 14.70, 6.70, 0.70, 7.00, 100.00, 'g', '779001000007', 1),
(8, NULL, 'Carne vacuna magra (Lomo/Cuadril)', 'Genérico', 'Carnes y Aves', 205.00, 27.00, 0.00, 10.50, 0.00, 0.00, 60.00, 100.00, 'g', '779001000008', 1),
(9, NULL, 'Salmón rosado a la plancha', 'Pescadería', 'Pescados y Mariscos', 208.00, 20.40, 0.00, 13.40, 0.00, 0.00, 59.00, 100.00, 'g', '779001000009', 0),
(10, NULL, 'Atún al natural en lata', 'La Campagnola', 'Pescados y Mariscos', 116.00, 26.00, 0.00, 1.00, 0.00, 0.00, 320.00, 100.00, 'g', '779001000010', 1),
(11, NULL, 'Yogur Griego Natural descremado', 'Fage / Dahi', 'Huevos y Lácteos', 59.00, 10.00, 3.60, 0.40, 0.00, 3.20, 36.00, 100.00, 'g', '779001000011', 1),
(12, NULL, 'Leche descremada 0%', 'La Serenísima', 'Huevos y Lácteos', 34.00, 3.20, 4.80, 0.10, 0.00, 4.80, 45.00, 100.00, 'ml', '779001000012', 0),
(13, NULL, 'Pan Integral de molde', 'Bimbo Vital', 'Panadería', 245.00, 9.50, 44.00, 3.20, 6.50, 4.00, 410.00, 100.00, 'g', '779001000013', 0),
(14, NULL, 'Pasta de Maní natural sin azúcar', 'Entrenuts', 'Grasas Saludables', 588.00, 25.00, 20.00, 50.00, 8.00, 4.00, 10.00, 100.00, 'g', '779001000014', 1),
(15, NULL, 'Whey Protein 100% Isolate', 'Star Nutrition', 'Suplementos', 380.00, 82.00, 4.50, 2.00, 1.00, 1.50, 140.00, 100.00, 'g', '779001000015', 1),
(16, NULL, 'Papa / Patata hervida', 'Verdulería', 'Tubérculos', 87.00, 1.90, 20.10, 0.10, 1.80, 0.80, 6.00, 100.00, 'g', '779001000016', 0),
(17, NULL, 'Batata / Camote horneado', 'Verdulería', 'Tubérculos', 86.00, 1.60, 20.10, 0.10, 3.00, 4.20, 55.00, 100.00, 'g', '779001000017', 0),
(18, NULL, 'Fideos / Pastas secas cocidas', 'Matarazzo', 'Pastas', 158.00, 5.80, 30.90, 0.90, 1.80, 0.60, 2.00, 100.00, 'g', '779001000018', 0),
(19, NULL, 'Manzana Roja con piel', 'Frutería', 'Frutas', 52.00, 0.30, 13.80, 0.20, 2.40, 10.40, 1.00, 100.00, 'g', '779001000019', 0),
(20, NULL, 'Brócoli al vapor', 'Verdulería', 'Verduras', 35.00, 2.40, 7.20, 0.40, 2.60, 1.40, 33.00, 100.00, 'g', '779001000020', 0),
(21, NULL, 'Aceite de Oliva Extra Virgen', 'Olivares', 'Aceites y Grasas', 884.00, 0.00, 0.00, 100.00, 0.00, 0.00, 2.00, 100.00, 'ml', '779001000021', 1),
(22, NULL, 'Almendras naturales tostadas', 'Dietética', 'Frutos Secos', 579.00, 21.20, 21.60, 49.90, 12.50, 4.40, 1.00, 100.00, 'g', '779001000022', 0),
(23, NULL, 'Lentejas cocidas', 'Legumbres', 'Legumbres', 116.00, 9.00, 20.10, 0.40, 7.90, 1.80, 2.00, 100.00, 'g', '779001000023', 0),
(24, NULL, 'Queso Por Salut Light', 'La Serenísima', 'Huevos y Lácteos', 215.00, 23.00, 1.50, 13.00, 0.00, 1.00, 390.00, 100.00, 'g', '779001000024', 0),
(25, NULL, 'Hamburguesa Casera de Res (140g)', 'Casera', 'Platos Preparados', 245.00, 22.00, 0.00, 17.00, 0.00, 0.00, 85.00, 100.00, 'g', '779001000025', 0),
(26, NULL, 'Pizza Muzarella masa tradicional', 'Pizzería', 'Platos Preparados', 266.00, 11.00, 33.00, 10.00, 2.30, 3.60, 560.00, 100.00, 'g', '779001000026', 0),
(27, NULL, 'Empanada de Carne al horno', 'Rotisería', 'Platos Preparados', 250.00, 9.00, 25.00, 12.50, 1.50, 1.20, 420.00, 100.00, 'g', '779001000027', 0),
(28, NULL, 'Café Solo expreso / americano', 'Cafetería', 'Bebidas', 2.00, 0.10, 0.30, 0.00, 0.00, 0.00, 5.00, 100.00, 'ml', '779001000028', 0),
(29, NULL, 'Espinaca fresca cruda', 'Verdulería', 'Verduras', 23.00, 2.90, 3.60, 0.40, 2.20, 0.40, 79.00, 100.00, 'g', '779001000029', 0),
(30, NULL, 'Tomate redondo fresco', 'Verdulería', 'Verduras', 18.00, 0.90, 3.90, 0.20, 1.20, 2.60, 5.00, 100.00, 'g', '779001000030', 0);

-- 3. Base de Datos Amplia de Ejercicios de Gimnasio (40+ Ejercicios por Grupo Muscular con Guías Técnicas)
INSERT INTO `ejercicios` 
(`id`, `nombre`, `musculo_principal`, `musculos_secundarios`, `categoria`, `dificultad`, `equipamiento`, `descripcion`, `instrucciones`, `consejos`, `errores_frecuentes`, `es_predeterminado`) 
VALUES
(1, 'Press de Banca Plano con Barra', 'Pectoral Mayor', 'Tríceps, Deltoides Anterior', 'pecho', 'intermedio', 'barra', 'El clásico ejercicio compuesto para construir masa y fuerza en el pecho.', '1. Acostate en el banco con los ojos alineados con la barra.\n2. Retraé escápulas y apoyá los pies firmes.\n3. Bajá la barra al esternón de forma controlada.\n4. Empujá con fuerza hasta extender brazos.', 'Mantené los codos a unos 45-70 grados del torso para proteger los hombros.', 'Rebotar la barra en el pecho o despegar los glúteos del banco.', 1),
(2, 'Press Inclinado con Mancuernas', 'Pectoral Superior', 'Deltoides Anterior, Tríceps', 'pecho', 'intermedio', 'mancuernas', 'Enfocado en las fibras claviculares del pecho superior con rango libre de movimiento.', '1. Ajustá el banco a 30-45 grados.\n2. Llevá las mancuernas sobre los hombros.\n3. Bajá abriendo los codos con control.\n4. Empujá hacia arriba en curva natural.', 'No inclines el banco a más de 45° para evitar que el deltoides tome todo el trabajo.', 'Juntar las mancuernas con choque ruidoso perdiendo tensión.', 1),
(3, 'Cruces en Polea para Pecho', 'Pectoral Mayor', 'Deltoides Anterior', 'pecho', 'principiante', 'polea', 'Aislamiento continuo para enfatizar la contracción máxima y bombeo.', '1. Ubicate en el centro de las poleas.\n2. Da un paso adelante con ligera inclinación.\n3. Llevá las manos hacia el frente apretando el pecho.\n4. Regresá estirando de forma suave.', 'Mantené los codos ligeramente flexionados fijos durante todo el recorrido.', 'Usar balanceo del torso o peso excesivo.', 1),
(4, 'Fondos en Paralelas (Dips)', 'Pectoral Inferior', 'Tríceps, Deltoides Anterior', 'pecho', 'avanzado', 'peso_corporal', 'Ejercicio básico de peso corporal demoledor para empuje.', '1. Apoyate en las barras paralelas.\n2. Incliná el torso 30 grados hacia adelante.\n3. Descendé hasta que los codos formen 90 grados.\n4. Empujá hacia arriba contrayendo el pecho.', 'La inclinación hacia adelante activa más el pecho; cuerpo recto activa más tríceps.', 'Bajar demasiado comprometiendo la articulación del hombro.', 1),
(5, 'Dominadas Pronas (Pull-ups)', 'Dorsal Ancho', 'Bíceps, Trapecios, Redondo Mayor', 'espalda', 'intermedio', 'peso_corporal', 'El rey de la amplitud de espalda y tracción vertical.', '1. Agarre prono ligeramente más ancho que los hombros.\n2. Iniciá deprimiendo escápulas.\n3. Traccioná llevando el pecho hacia la barra.\n4. Bajá con control total hasta estiramiento completo.', 'Pensá en llevar los codos hacia abajo y atrás, no en jalar con los brazos.', 'Kipping descontrolado o no completar el rango de movimiento.', 1),
(6, 'Remo con Barra Pendlay / 90°', 'Dorsal Ancho', 'Espalda Media, Bíceps, Espinales', 'espalda', 'intermedio', 'barra', 'Construye grosor y densidad de espalda con gran transferencia de fuerza.', '1. Pies ancho de caderas, torso casi paralelo al suelo.\n2. Espalda lumbar neutra y abdomen firme.\n3. Traccioná la barra hacia el ombligo/esternón.\n4. Apretá escápulas 1 segundo y bajá con control.', 'Mantené la columna en posición neutra y no uses impulso con las piernas.', 'Redondear la espalda baja, riesgo de lesión.', 1),
(7, 'Jalón al Pecho en Polea Alta', 'Dorsal Ancho', 'Bíceps, Redondo Mayor', 'espalda', 'principiante', 'polea', 'Excelente alternativa guiada para hipertrofia y conexión mente-músculo.', '1. Sentate ajustando las almohadillas a los muslos.\n2. Agarrá la barra amplia e incliná apenas el torso.\n3. Bajá la barra al nivel de la clavícula.\n4. Regresá estirando la espalda completamente.', 'Mantené el pecho arriba como sacando orgullo durante el tirón.', 'Tirarse excesivamente hacia atrás para trampear el peso.', 1),
(8, 'Remo en Polea Baja sentado', 'Espalda Media', 'Dorsal Ancho, Bíceps, Trapecios', 'espalda', 'principiante', 'polea', 'Permite un trabajo seguro y focalizado en los romboides y espalda media.', '1. Pies apoyados en la plataforma con rodillas semiflexionadas.\n2. Agarrá el manillar con brazos extendidos.\n3. Traccioná hacia el abdomen retrayendo escápulas.\n4. Controlá el retorno sin encorvar la columna.', 'Pausa 1 segundo con la espalda bien apretada al final de cada repetición.', 'Balancear el torso adelante y atrás como si fuera un remo de agua.', 1),
(9, 'Press Militar con Barra de Pie', 'Deltoides Anterior', 'Deltoides Lateral, Tríceps, Core', 'hombros', 'intermedio', 'barra', 'El ejercicio fundamental para construir hombros de roca y fuerza en la cabeza.', '1. Barra apoyada sobre las clavículas, agarre al ancho de hombros.\n2. Glúteos y abdomen apretados como un bloque.\n3. Empujá la barra verticalmente pasando la cabeza.\n4. Bloqueá arriba y bajá controlado.', 'Meté la cabeza ligeramente adelante una vez la barra pase la frente.', 'Arquear excesivamente la espalda baja compensando falta de movilidad.', 1),
(10, 'Elevaciones Laterales con Mancuernas', 'Deltoides Lateral', 'Trapecios', 'hombros', 'principiante', 'mancuernas', 'El ejercicio insustituible para lograr la apariencia en V y hombros redondos.', '1. De pie con mancuernas a los costados.\n2. Codos apenas flexionados.\n3. Elevá los brazos hacia los laterales hasta la altura del hombro.\n4. Bajá despacio resistiendo la gravedad.', 'Guiá el movimiento con los codos y mantené los hombros abajo.', 'Usar impulso balanceando el cuerpo o subir por encima del hombro.', 1),
(11, 'Pájaros / Deltoides Posterior en Polea', 'Deltoides Posterior', 'Romboides, Trapecios', 'hombros', 'principiante', 'polea', 'Crucial para el balance articular del hombro y postura erguida.', '1. Ajustá las poleas a la altura de los ojos.\n2. Agarrá los cables cruzados sin mangos.\n3. Abrí los brazos hacia atrás en forma de abrazo invertido.\n4. Retorná con lentitud.', 'Concentrate en sentir la parte trasera del hombro trabajando.', 'Jalar con los tríceps o retraer con trapecios superiores.', 1),
(12, 'Sentadilla Trasera con Barra (Squat)', 'Cuádriceps', 'Glúteos, Femorales, Core', 'cuadriceps', 'avanzado', 'barra', 'El ejercicio rey del tren inferior para desarrollo muscular global y fuerza.', '1. Barra apoyada en trapecios, pies al ancho de hombros con puntas hacia afuera.\n2. Inhalá y creá presión intra-abdominal.\n3. Descendé flexionando rodillas y caderas hasta romper paralelo.\n4. Empujá con fuerza el suelo para subir.', 'Mantené las rodillas alineadas en la dirección de las puntas de los pies.', 'Colapso de rodillas hacia adentro (valgo) o levantar los talones.', 1),
(13, 'Prensa de Piernas 45°', 'Cuádriceps', 'Glúteos, Aductores', 'cuadriceps', 'intermedio', 'maquina', 'Carga elevada con mínimo estrés espinal para volumen masivo de piernas.', '1. Apoyá los pies al centro de la plataforma.\n2. Desbloqueá los seguros con cuidado.\n3. Descendé flexionando rodillas sin despegar los glúteos del asiento.\n4. Empujá sin bloquear bruscamente las rodillas.', 'No despegues la zona lumbar del respaldo en la parte baja.', 'Bloqueo articular brusco de rodillas al subir con carga pesada.', 1),
(14, 'Extensiones de Cuádriceps en Máquina', 'Cuádriceps', 'Ninguno', 'cuadriceps', 'principiante', 'maquina', 'Aislamiento supremo para quemar y bombear el recto femoral.', '1. Ajustá el rodillo justo por encima de los tobillos.\n2. Sentate bien atrás con la espalda firme.\n3. Extendé las piernas hasta contracción completa.\n4. Bajá en 2-3 segundos de forma constante.', 'Apretá los cuádriceps arriba durante 1 segundo antes de bajar.', 'Patear con impulso sin controlar la fase excéntrica.', 1),
(15, 'Peso Muerto Rumano con Barra (RDL)', 'Femorales', 'Glúteos, Espalda Baja', 'femorales', 'intermedio', 'barra', 'El mejor constructor de la cadena posterior y grosor en femorales e isquios.', '1. De pie con la barra pegada a los muslos.\n2. Rodillas con una micro-flexión fija.\n3. Empujá las caderas hacia atrás como si quisieras tocar la pared.\n4. Bajá la barra rozando piernas hasta sentir el estirón en femorales.\n5. Subí empujando las caderas hacia adelante.', 'El movimiento es una bisagra de cadera, no una sentadilla.', 'Doblar la espalda baja o permitir que la barra se separe de las piernas.', 1),
(16, 'Curl Femoral Tumbado en Máquina', 'Femorales', 'Gemelos', 'femorales', 'principiante', 'maquina', 'Aislamiento directo de la flexión de rodilla para bíceps femoral.', '1. Acostate boca abajo ajustando el rodillo en los tendones de Aquiles.\n2. Mantené las caderas pegadas al banco.\n3. Flexioná las piernas llevando los talones a los glúteos.\n4. Descendé suavemente.', 'Mantené los pies dorsiflexionados para no involucrar gemelos.', 'Despegar las caderas del banco para conseguir impulso.', 1),
(17, 'Hip Thrust con Barra en Banco', 'Glúteos', 'Femorales, Cuádriceps', 'gluteos', 'intermedio', 'barra', 'La máxima activación de glúteo mayor por excelencia.', '1. Espalda apoyada debajo de los omóplatos en el banco.\n2. Barra con almohadilla sobre la pelvis, pies al ancho de hombros.\n3. Elevá la cadera hasta alinear rodillas, caderas y hombros.\n4. Apretá glúteos con fuerza máxima arriba durante 1 segundo.', 'Mantené la barbilla pegada al pecho mirando al frente para no hiper-extender la columna.', 'Empujar arqueando la espalda en lugar de usar los glúteos.', 1),
(18, 'Elevación de Talones de Pie para Gemelos', 'Gemelos', 'Sóleo', 'gemelos', 'principiante', 'maquina', 'Desarrollo del gastrocnemio con rango completo de movimiento.', '1. Apoyá la punta de los pies en el escalón.\n2. Bajá los talones al máximo sintiendo el estiramiento profundo.\n3. Elevate sobre las puntas de los pies contrayendo con fuerza.\n4. Pausá arriba y abajo.', 'Hacé pausas de 1-2 segundos abajo para disipar la elasticidad del tendón de Aquiles.', 'Rebotes rápidos sin rango ni tensión real.', 1),
(19, 'Curl de Bíceps con Barra Z', 'Bíceps', 'Braquial, Antebrazo', 'biceps', 'intermedio', 'barra', 'El constructor por excelencia de masa en el bíceps con agarre ergonómico.', '1. Agarrá la barra Z en las curvas interiores.\n2. Pegá los codos a los costados del torso.\n3. Flexioná los brazos llevando la barra hacia el pecho.\n4. Bajá con control total hasta estirar el bíceps.', 'Mantené los hombros quietos y no balancees el torso.', 'Balancear los codos hacia adelante y usar la espalda baja.', 1),
(20, 'Curl Martillo con Mancuernas', 'Braquial', 'Bíceps, Braquiorradial', 'biceps', 'principiante', 'mancuernas', 'Excelente para el grosor del brazo y antebrazo.', '1. De pie con mancuernas en agarre neutro (palmas enfrentadas).\n2. Subí las mancuernas manteniendo las palmas mirándose.\n3. Contrae arriba y descendé lentamente.', 'Podés alternar los brazos o levantarlos simultáneamente.', 'Girar las muñecas perdiendo el agarre neutro.', 1),
(21, 'Press Francés con Barra Z en Banco', 'Tríceps', 'Deltoides Anterior', 'triceps', 'intermedio', 'barra', 'Enfoque demoledor en la cabeza larga del tríceps.', '1. Acostate en banco plano con la barra extendida sobre el pecho.\n2. Flexioná únicamente los antebrazos llevando la barra hacia la frente/coronilla.\n3. Extendé los codos contrayendo los tríceps.', 'Mantené los codos apuntando al techo sin abrirlos hacia afuera.', 'Abrir los codos o mover los brazos desde el hombro.', 1),
(22, 'Extensiones de Tríceps en Polea con Cuerda', 'Tríceps', 'Antebrazo', 'triceps', 'principiante', 'polea', 'Aislamiento constante y contracción máxima en la cabeza lateral.', '1. Agarrá la cuerda con polea alta.\n2. Codos pegados a los costados.\n3. Empujá hacia abajo y abrí los extremos de la cuerda al final.\n4. Retorná con lentitud hasta ángulo de 90°.', 'Apretá al final del movimiento abriendo la cuerda hacia los lados.', 'Mover los hombros hacia arriba y usar el peso corporal.', 1),
(23, 'Crunch Abdominal en Polea Alta', 'Abdominales', 'Oblicuos', 'abdominales', 'principiante', 'polea', 'Permite sobrecarga progresiva real en los abdominales rectos.', '1. De rodillas frente a la polea, cuerda detrás de la cabeza.\n2. Enrollá la columna vertebral llevando los codos hacia las rodillas.\n3. Apretá el abdomen fuerte.\n4. Desenrollá con control.', 'Enfocate en curvar la columna con el abdomen, no en flexionar la cadera.', 'Mover las caderas hacia atrás como sentándose.', 1),
(24, 'Plancha Abdominal Estática (Plank)', 'Core', 'Hombros, Glúteos', 'abdominales', 'principiante', 'peso_corporal', 'Estabilidad del core y resistencia isométrica.', '1. Apoyá antebrazos y puntas de pies en el suelo.\n2. Formá una línea recta desde la cabeza hasta los talones.\n3. Apretá glúteos y abdomen sin permitir que caiga la cadera.', 'Respirá de manera fluida y constante durante todo el tiempo.', 'Dejar caer la cadera hacia abajo cargando la espalda lumbar.', 1);

-- 4. Rutinas y Plantillas
INSERT INTO `rutinas` (`id`, `usuario_id`, `nombre`, `descripcion`, `dias_semana`, `es_plantilla`) VALUES
(1, 1, 'PPL x Hypertrophy (Push Pull Legs)', 'Rutina de 6 o 3 días optimizada para máxima hipertrofia y balance muscular.', 'Lunes, Martes, Miércoles, Jueves, Viernes, Sábado', 1),
(2, 1, 'Torso / Pierna Power & Hypertrophy', 'División de 4 días enfocada en fuerza en compuestos y accesorios de hipertrofia.', 'Lunes, Martes, Jueves, Viernes', 1),
(3, 1, 'Full Body Clásica (3 Días)', 'Ideal para principiantes y personas con poco tiempo, frecuencia alta.', 'Lunes, Miércoles, Viernes', 1);

-- Días de Rutina PPL
INSERT INTO `rutina_dias` (`id`, `rutina_id`, `nombre_dia`, `orden`) VALUES
(1, 1, 'Día 1 - Push (Pecho, Hombros, Tríceps)', 1),
(2, 1, 'Día 2 - Pull (Espalda, Bíceps, Posterior)', 2),
(3, 1, 'Día 3 - Legs (Cuádriceps, Femorales, Gemelos)', 3);

-- Ejercicios para Push
INSERT INTO `rutina_dia_ejercicios` (`rutina_dia_id`, `ejercicio_id`, `orden`, `series_objetivo`, `repeticiones_objetivo`, `descanso_segundos`) VALUES
(1, 1, 1, 4, '6-8', 120),
(1, 2, 2, 3, '8-10', 90),
(1, 9, 3, 3, '8-10', 90),
(1, 10, 4, 4, '12-15', 60),
(1, 21, 5, 3, '10-12', 75),
(1, 22, 6, 3, '12-15', 60);

-- Ejercicios para Pull
INSERT INTO `rutina_dia_ejercicios` (`rutina_dia_id`, `ejercicio_id`, `orden`, `series_objetivo`, `repeticiones_objetivo`, `descanso_segundos`) VALUES
(2, 5, 1, 4, '6-8', 120),
(2, 6, 2, 4, '8-10', 90),
(2, 7, 3, 3, '10-12', 75),
(2, 11, 4, 3, '12-15', 60),
(2, 19, 5, 3, '8-10', 75),
(2, 20, 6, 3, '10-12', 60);

-- Ejercicios para Legs
INSERT INTO `rutina_dia_ejercicios` (`rutina_dia_id`, `ejercicio_id`, `orden`, `series_objetivo`, `repeticiones_objetivo`, `descanso_segundos`) VALUES
(3, 12, 1, 4, '6-8', 150),
(3, 15, 2, 4, '8-10', 120),
(3, 13, 3, 3, '10-12', 90),
(3, 16, 4, 3, '12-15', 75),
(3, 18, 5, 4, '15-20', 60),
(3, 23, 6, 3, '15-20', 60);

-- 5. Entrenamientos pasados y series completadas para Nicolás (Demo)
INSERT INTO `entrenamientos` 
(`id`, `usuario_id`, `rutina_id`, `nombre`, `fecha`, `hora_inicio`, `hora_fin`, `duracion_segundos`, `volumen_total`, `series_totales`, `repeticiones_totales`, `prs_conseguidos`, `estado`, `notas`) 
VALUES
(1, 1, 1, 'Push - Hipertrofia & Fuerza', DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), '18:00:00', '19:15:00', 4500, 14250.00, 18, 162, 2, 'completado', 'Sensaciones excelentes. Nuevo PR en Press de Banca y excelente congestión en laterales.'),
(2, 1, 1, 'Pull - Densidad de Espalda', DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), '17:30:00', '18:42:00', 4320, 13800.00, 17, 150, 1, 'completado', 'Gran bombeo en dorsales con pendlay rows y bíceps pesado.'),
(3, 1, 1, 'Legs - Cuádriceps & Femorales', CURRENT_DATE, '10:00:00', '11:18:00', 4680, 18600.00, 18, 155, 1, 'completado', 'Día de pierna demoledor. Nuevo 1RM récord en sentadilla.');

-- Series de Entrenamiento 1 (Push)
INSERT INTO `entrenamiento_series` 
(`entrenamiento_id`, `ejercicio_id`, `numero_serie`, `tipo_serie`, `peso`, `repeticiones`, `rir`, `rpe`, `completada`, `es_pr`, `one_rm_estimado`, `volumen`) 
VALUES
(1, 1, 1, 'calentamiento', 60.00, 12, 4, 6.0, 1, 0, 84.00, 720.00),
(1, 1, 2, 'normal', 80.00, 10, 2, 8.0, 1, 0, 106.67, 800.00),
(1, 1, 3, 'normal', 90.00, 8, 1, 9.0, 1, 0, 114.00, 720.00),
(1, 1, 4, 'normal', 100.00, 6, 0, 10.0, 1, 1, 120.00, 600.00),
(1, 2, 1, 'normal', 32.00, 10, 2, 8.0, 1, 0, 42.67, 320.00),
(1, 2, 2, 'normal', 36.00, 8, 1, 9.0, 1, 1, 45.60, 288.00),
(1, 10, 1, 'normal', 14.00, 15, 2, 8.0, 1, 0, 21.00, 210.00),
(1, 10, 2, 'normal', 14.00, 12, 1, 8.5, 1, 0, 19.60, 168.00),
(1, 21, 1, 'normal', 40.00, 10, 2, 8.0, 1, 0, 53.33, 400.00);

-- Records Personales (PRs) de Nicolás
INSERT INTO `records_personales` 
(`id`, `usuario_id`, `ejercicio_id`, `peso`, `repeticiones`, `one_rm_estimado`, `volumen_serie`, `tipo_record`, `fecha`, `entrenamiento_id`) 
VALUES
(1, 1, 1, 100.00, 6, 120.00, 600.00, '1rm_max', DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), 1),
(2, 1, 2, 36.00, 8, 45.60, 288.00, 'peso_maximo', DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), 1),
(3, 1, 12, 140.00, 5, 163.33, 700.00, '1rm_max', CURRENT_DATE, 3),
(4, 1, 6, 95.00, 8, 120.33, 760.00, 'peso_maximo', DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), 2);

-- 6. Comidas Registradas de Hoy para Nicolás
INSERT INTO `registro_comidas` 
(`id`, `usuario_id`, `fecha`, `tipo_comida`, `alimento_id`, `nombre_personalizado`, `cantidad`, `unidad`, `calorias`, `proteinas`, `carbohidratos`, `grasas`) 
VALUES
(1, 1, CURRENT_DATE, 'desayuno', 5, 'Avena con Banana y Huevos', 80.00, 'g', 311.20, 13.52, 53.04, 5.52),
(2, 1, CURRENT_DATE, 'desayuno', 6, 'Banana madura', 120.00, 'g', 106.80, 1.32, 27.36, 0.36),
(3, 1, CURRENT_DATE, 'desayuno', 3, 'Huevos revueltos', 150.00, 'g', 214.50, 18.90, 1.05, 14.25),
(4, 1, CURRENT_DATE, 'media_manana', 15, 'Batido Whey Protein Isolate', 35.00, 'g', 133.00, 28.70, 1.58, 0.70),
(5, 1, CURRENT_DATE, 'almuerzo', 1, 'Pechuga de pollo grillada', 220.00, 'g', 363.00, 68.20, 0.00, 7.92),
(6, 1, CURRENT_DATE, 'almuerzo', 2, 'Arroz blanco cocido', 200.00, 'g', 260.00, 5.40, 56.40, 0.60),
(7, 1, CURRENT_DATE, 'almuerzo', 7, 'Palta Hass en rodajas', 70.00, 'g', 112.00, 1.40, 5.95, 10.29),
(8, 1, CURRENT_DATE, 'merienda', 11, 'Yogur Griego con frutos', 180.00, 'g', 106.20, 18.00, 6.48, 0.72),
(9, 1, CURRENT_DATE, 'merienda', 14, 'Pasta de Maní', 25.00, 'g', 147.00, 6.25, 5.00, 12.50);

-- 7. Registro de Agua y Pasos (Hoy y últimos días para gráficos)
INSERT INTO `registro_agua` (`usuario_id`, `fecha`, `cantidad_ml`) VALUES
(1, DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY), 2500),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 2750),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), 2250),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), 2600),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), 2800),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), 2500),
(1, CURRENT_DATE, 1750);

INSERT INTO `registro_pasos` (`usuario_id`, `fecha`, `pasos`, `distancia_km`, `calorias_quemadas`) VALUES
(1, DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY), 10250, 7.60, 410),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 9800, 7.20, 390),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), 11400, 8.40, 460),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), 8900, 6.60, 350),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), 10850, 8.10, 435),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), 9500, 7.00, 380),
(1, CURRENT_DATE, 7842, 5.80, 315);

-- 8. Medidas Corporales Históricas
INSERT INTO `medidas_corporales` 
(`usuario_id`, `fecha`, `peso`, `grasa_corporal`, `cintura`, `pecho`, `brazo_der`, `muslo_der`, `notas`) 
VALUES
(1, DATE_SUB(CURRENT_DATE, INTERVAL 28 DAY), 85.00, 18.5, 88.00, 105.00, 37.50, 59.00, 'Inicio de definición.'),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 21 DAY), 84.20, 18.0, 87.00, 105.50, 37.80, 58.80, 'Buena respuesta inicial.'),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY), 83.50, 17.4, 85.50, 106.00, 38.00, 58.50, 'Fuerza mantenida.'),
(1, DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), 82.90, 16.9, 84.50, 106.00, 38.20, 58.20, 'Vena en hombro visible.'),
(1, CURRENT_DATE, 82.40, 16.3, 83.50, 106.50, 38.50, 58.00, 'Definición en camino, cintura bajando y brazos subiendo.');

-- 9. Catálogo de Logros del Sistema
INSERT INTO `logros` (`id`, `codigo`, `categoria`, `titulo`, `descripcion`, `icono`, `xp_recompensa`) VALUES
(1, 'primer_entreno', 'constancia', 'Primer Paso de Acero', 'Completaste tu primer entrenamiento registrado.', 'dumbbell', 100),
(2, 'entrenos_7', 'constancia', 'Semana Imparable', 'Completaste 7 entrenamientos con disciplina.', 'fire', 250),
(3, 'entrenos_30', 'constancia', 'Mes de Titanio', 'Alcanzaste 30 entrenamientos completados.', 'shield-halved', 600),
(4, 'entrenos_50', 'constancia', 'Media Centuria de Hierro', 'Completaste 50 sesiones de entrenamiento.', 'trophy', 1000),
(5, 'entrenos_100', 'constancia', 'Centurión del Gimnasio', 'Completaste 100 entrenamientos legendarios.', 'crown', 2500),
(6, 'primer_pr', 'fuerza', 'Rompiendo Límites', 'Estableciste tu primer Récord Personal (PR).', 'bolt', 150),
(7, 'pr_5', 'fuerza', 'Poder Creciente', 'Superaste 5 Récords Personales.', 'bolt-lightning', 350),
(8, 'pr_10', 'fuerza', 'Fuerza Bruta Desatada', 'Alcanzaste 10 Récords Personales superados.', 'medal', 750),
(9, 'nutricion_1', 'nutricion', 'Conciencia Nutricional', 'Registraste todas tus comidas de un día completo.', 'apple-whole', 100),
(10, 'nutricion_7', 'nutricion', 'Precisión Calórica', '7 días consecutivos registrando tu nutrición.', 'chart-pie', 300),
(11, 'nutricion_30', 'nutricion', 'Maestro del Balance', '30 días de consistencia en tus macros.', 'scale-balanced', 800),
(12, 'agua_7', 'habitos', 'Hidratación Óptima', '7 días consecutivos alcanzando tu meta de agua.', 'droplet', 200),
(13, 'pasos_7', 'habitos', 'Caminante Incansable', '7 días consecutivos superando 10.000 pasos.', 'shoe-prints', 250),
(14, 'streak_14', 'constancia', 'Fuego Ardiente', 'Mantuviste una racha ininterrumpida de 14 días.', 'flame', 400),
(15, 'streak_30', 'constancia', 'Hábito Indestructible', '30 días consecutivos activo en Gymrat.', 'fire-flame-curved', 1000);

-- Desbloquear logros para Nicolás (Demo)
INSERT INTO `usuario_logros` (`usuario_id`, `logro_id`, `fecha_obtenido`, `visto`) VALUES
(1, 1, DATE_SUB(CURRENT_DATE, INTERVAL 40 DAY), 1),
(1, 2, DATE_SUB(CURRENT_DATE, INTERVAL 33 DAY), 1),
(1, 3, DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), 1),
(1, 6, DATE_SUB(CURRENT_DATE, INTERVAL 20 DAY), 1),
(1, 7, DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), 1),
(1, 9, DATE_SUB(CURRENT_DATE, INTERVAL 40 DAY), 1),
(1, 10, DATE_SUB(CURRENT_DATE, INTERVAL 33 DAY), 1),
(1, 12, DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 1),
(1, 14, DATE_SUB(CURRENT_DATE, INTERVAL 28 DAY), 1),
(1, 15, DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY), 1);

-- 10. Comunidad y Seguidores
INSERT INTO `comunidad_seguidores` (`seguidor_id`, `seguido_id`) VALUES
(1, 2), -- Nicolas sigue a Valentina
(1, 3), -- Nicolas sigue a Lucas
(2, 1), -- Valentina sigue a Nicolas
(3, 1), -- Lucas sigue a Nicolas
(4, 1); -- Sofia sigue a Nicolas

-- 11. Feed de Actividad Social
INSERT INTO `feed_actividad` (`usuario_id`, `tipo`, `referencia_id`, `titulo`, `descripcion`, `datos_json`, `likes_count`, `created_at`) VALUES
(1, 'pr', 1, '🔥 ¡Nuevo PR en Press de Banca Plano!', 'Superó su marca anterior: 100 kg × 6 reps (1RM estimado: 120 kg)', '{"ejercicio":"Press de Banca Plano con Barra","peso":100,"reps":6,"one_rm":120}', 8, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 'entrenamiento', NULL, 'Valentina completó Sentadillas Pesadas', 'Sesión de sentadilla técnica: 4x3 con 115 kg. Excelente velocidad.', '{"rutina":"Powerlifting Torso/Pierna","duracion":"1h 15m","volumen":"12.400 kg"}', 14, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(3, 'logro', 3, 'Lucas desbloqueó "Mes de Titanio" 🏆', 'Alcanzó los 30 entrenamientos con constancia de acero.', '{"logro":"Mes de Titanio","xp":600}', 6, DATE_SUB(NOW(), INTERVAL 7 HOUR)),
(1, 'entrenamiento', 3, 'Nicolás completó Legs - Cuádriceps & Femorales', 'Sentadillas profundas a 140 kg y volumen de 18.600 kg.', '{"duracion":"1h 18m","volumen":"18.600 kg","series":18}', 11, DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(4, 'meta_completada', NULL, 'Sofía alcanzó su meta de 12.000 pasos 🏃‍♀️', '10.5 km de recorrido matutino activo.', '{"pasos":12450,"distancia":"10.5 km"}', 9, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 12. Recordatorios de Nicolás
INSERT INTO `recordatorios` (`usuario_id`, `tipo`, `hora`, `dias_semana`, `activo`, `mensaje`) VALUES
(1, 'agua', '10:30:00', 'todos', 1, 'Momento de hidratarte: tomá al menos 500 ml de agua fresca.'),
(1, 'comer', '13:00:00', 'todos', 1, 'Hora del almuerzo: asegurá tus fuentes proteicas y carbohidratos.'),
(1, 'entrenar', '17:30:00', 'lun,mar,mie,jue,vie', 1, 'Tu sesión de entrenamiento te espera. ¡Prepará tu pre-entreno!'),
(1, 'pesar', '08:00:00', 'lun,vie', 1, 'Registro de peso semanal en ayunas.');

-- 13. Notificaciones para Nicolás
INSERT INTO `notificaciones` (`usuario_id`, `tipo`, `titulo`, `mensaje`, `leido`, `enlace`, `created_at`) VALUES
(1, 'pr', '🔥 ¡Felicitaciones por tu nuevo PR!', 'Alcanzaste 100 kg en Press de Banca con 6 repeticiones.', 0, 'progreso.php', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'seguidor', 'Nuevo seguidor en Gymrat', 'Sofía Martínez (@sofia_active) comenzó a seguirte.', 0, 'perfil.php?user=sofia_active', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(1, 'agua', '💧 Meta de Agua en Progreso', 'Llevás 1.750 ml de agua. Estás a solo 750 ml de completar tu meta.', 1, 'dashboard.php', DATE_SUB(NOW(), INTERVAL 1 HOUR));
