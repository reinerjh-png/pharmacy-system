<?php
// config/db.php
// Conexión PDO a MySQL — Sistema de Farmacia SaaS

define('DB_HOST', 'localhost');
define('DB_NAME', 'sys-farmacia');
define('DB_USER', 'root');
define('DB_PASS', '');
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
