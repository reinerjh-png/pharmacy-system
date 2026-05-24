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
    
    // Obtenemos los valores por defecto del helper
    $defaults = _branding_defaults();

    // Actualizamos la base de datos a los valores por defecto
    $sql = "UPDATE branding SET
                farmacia_nombre           = :nombre,
                farmacia_slogan           = :slogan,
                farmacia_color_primario   = :color_p,
                farmacia_color_secundario = :color_s,
                farmacia_logo_url         = NULL,
                farmacia_direccion        = NULL,
                farmacia_telefono         = NULL,
                farmacia_ruc              = NULL,
                actualizado_por           = :uid
            WHERE activo = 1";

    $params = [
        ':nombre'  => $defaults['farmacia_nombre'],
        ':slogan'  => $defaults['farmacia_slogan'],
        ':color_p' => $defaults['farmacia_color_primario'],
        ':color_s' => $defaults['farmacia_color_secundario'],
        ':uid'     => (int)$_SESSION['usuario_id']
    ];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Invalidar cache
    branding_invalidar_cache();

    json_out(true, 'Branding restablecido a los valores por defecto.');
} catch (PDOException $e) {
    json_out(false, 'Error al restablecer en la base de datos.');
}
