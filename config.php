<?php
/**
 * GYMRAT - Configuración Global
 * Sistema de Gestión Fitness y Nutrición de Alto Rendimiento
 */

// Evitar doble definición
if (!defined('GYMRAT_CONFIG_LOADED')) {
    define('GYMRAT_CONFIG_LOADED', true);

    // Zona Horaria y Codificación Global
    date_default_timezone_set('America/Argentina/Buenos_Aires');
    ini_set('default_charset', 'UTF-8');
    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }

    // Entorno: 'development' o 'production'
    define('APP_ENV', 'development');

    // Configuración de visualización de errores
    if (APP_ENV === 'development') {
        ini_set('display_errors', 0); // No contaminar respuestas JSON de APIs
        error_reporting(E_ALL);
    } else {
        ini_set('display_errors', 0);
        error_reporting(0);
    }

    // Datos de la Aplicación
    define('APP_NAME', 'Gymrat');
    define('APP_TAGLINE', 'Train. Track. Improve.');
    define('APP_VERSION', '1.0.0');

    // URL Base dinámica para XAMPP
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = '/gymrat';
    define('BASE_URL', $protocol . $host . $basePath);

    // Rutas del Sistema de Archivos
    define('ROOT_PATH', __DIR__);
    define('UPLOADS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
    define('UPLOADS_URL', BASE_URL . '/uploads');

    // Base de Datos MySQL (XAMPP por defecto: root sin contraseña)
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'gymrat_db');
    define('DB_CHARSET', 'utf8mb4');

    // Límites de subida de archivos (5MB max)
    define('MAX_FILE_SIZE', 5 * 1024 * 1024);
    define('ALLOWED_IMG_MIME', ['image/jpeg', 'image/png', 'image/webp']);
    define('ALLOWED_IMG_EXT', ['jpg', 'jpeg', 'png', 'webp']);

    // Iniciar sesión segura si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
