<?php
/**
 * GYMRAT - Conexión Segura a Base de Datos MySQL mediante PDO
 */

require_once __DIR__ . '/config.php';

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Si la base de datos no existe (código 1049), intentar crearla automáticamente
            if ($e->getCode() == 1049) {
                try {
                    $initDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
                    $tempPdo = new PDO($initDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci;");
                    
                    // Conectar ahora a la BD creada
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                    
                    // Si existe database.sql, auto-importar
                    $sqlFile = __DIR__ . '/database.sql';
                    if (file_exists($sqlFile)) {
                        $sqlContent = file_get_contents($sqlFile);
                        if (!empty($sqlContent)) {
                            $pdo->exec($sqlContent);
                        }
                    }
                } catch (Exception $initEx) {
                    error_log("Error inicializando base de datos Gymrat: " . $initEx->getMessage());
                    throw new Exception("Error al conectar e inicializar la base de datos.");
                }
            } else {
                error_log("Error de conexión PDO Gymrat: " . $e->getMessage());
                throw new Exception("Error de conexión con la base de datos.");
            }
        }
    }

    return $pdo;
}
