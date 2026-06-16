<?php
// api/branding_reset.php
// Endpoint POST para restablecer el branding a valores de fábrica.
// Solo accesible para administradores (rol_id = 1).

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function json_out(bool $success, string $message): void {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if (empty($_SESSION['usuario_id']) || (int)($_SESSION['rol_id'] ?? 0) !== 1) {
    json_out(false, 'Acceso denegado.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, 'Método no permitido.');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/branding.php';

try {
    $pdo = conectar();
    $fid = (int)$_SESSION['farmacia_id'];
    $uid = (int)$_SESSION['usuario_id'];
    
    // Obtenemos el nombre real de la farmacia desde la tabla 'farmacias'
    $stmtF = $pdo->prepare("SELECT nombre FROM farmacias WHERE id = ? LIMIT 1");
    $stmtF->execute([$fid]);
    $farmacia = $stmtF->fetch();
    $nombre_real = $farmacia ? $farmacia['nombre'] : 'Mi Farmacia';

    // Actualizamos la base de datos a los valores neutros pero preservando el nombre
    $sql = "UPDATE branding SET
                farmacia_nombre           = :nombre,
                farmacia_slogan           = 'Sistema de Gestión',
                farmacia_color_primario   = '#059669',
                farmacia_color_secundario = '#10b981',
                farmacia_logo_url         = NULL,
                farmacia_direccion        = NULL,
                farmacia_telefono         = NULL,
                farmacia_ruc              = NULL,
                actualizado_por           = :uid
            WHERE activo = 1 AND farmacia_id = :fid";

    $params = [
        ':nombre'  => $nombre_real,
        ':uid'     => $uid,
        ':fid'     => $fid
    ];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Invalidar cache
    branding_invalidar_cache();

    json_out(true, 'Branding restablecido a los valores por defecto.');
} catch (PDOException $e) {
    json_out(false, 'Error al restablecer en la base de datos.');
}
