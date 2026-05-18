<?php
// config/db.php
// Conexión PDO a MySQL — Sistema de Farmacia SaaS

// ====================================================
// PRODUCCIÓN: Desactivar la exposición de errores PHP
// ====================================================
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
// ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// ====================================================
// CREDENCIALES: usar variables de entorno si están definidas
// (En InfinityFree: definir en .htaccess o php.ini local)
// ====================================================
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'sys-farmacia');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Obtiene una conexión PDO singleton a la base de datos
 * @return PDO
 */
function conectar(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            error_log("Error de conexión a BD: " . $e->getMessage());
            die("Error de conexión al servidor. Intente más tarde.");
        }
    }
    return $pdo;
}
