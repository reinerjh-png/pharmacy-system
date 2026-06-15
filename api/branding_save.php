<?php
// api/branding_save.php
// Endpoint POST para guardar la configuración de branding.
// Solo accesible para administradores (rol_id = 1).

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ── Función de respuesta ──
function json_out(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// ── Seguridad: sesión y rol ──
if (empty($_SESSION['usuario_id'])) {
    json_out(false, 'No autorizado. Sesión requerida.');
}
if ((int)($_SESSION['rol_id'] ?? 0) !== 1) {
    json_out(false, 'Acceso denegado. Solo el administrador puede cambiar el branding.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, 'Método no permitido.');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/branding.php';

// ── Recoger y sanitizar campos ──
$nombre     = trim($_POST['farmacia_nombre'] ?? '');
$slogan     = trim($_POST['farmacia_slogan'] ?? '');
$color_p    = trim($_POST['farmacia_color_primario'] ?? '#059669');
$color_s    = trim($_POST['farmacia_color_secundario'] ?? '#10b981');
$logo_url   = trim($_POST['farmacia_logo_url'] ?? '');
$direccion  = trim($_POST['farmacia_direccion'] ?? '');
$telefono   = trim($_POST['farmacia_telefono'] ?? '');
$ruc        = trim($_POST['farmacia_ruc'] ?? '');

// ── Validaciones ──
if (empty($nombre)) {
    json_out(false, 'El nombre de la farmacia es obligatorio.');
}
if (strlen($nombre) > 150) {
    json_out(false, 'El nombre no puede superar los 150 caracteres.');
}

// Validar formato hex de colores
$hex_regex = '/^#[0-9a-fA-F]{6}$/';
if (!preg_match($hex_regex, $color_p)) {
    json_out(false, 'El color primario no es un valor hexadecimal válido.');
}
if (!preg_match($hex_regex, $color_s)) {
    json_out(false, 'El color secundario no es un valor hexadecimal válido.');
}

// ── Manejo de subida de logo (archivo) ──
$logo_final = $logo_url ?: null; // Por defecto usa la URL si existe

if (!empty($_FILES['logo_file']['name'])) {
    $file     = $_FILES['logo_file'];
    $max_size = 2 * 1024 * 1024; // 2 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_out(false, 'Error al subir el archivo. Código: ' . $file['error']);
    }
    if ($file['size'] > $max_size) {
        json_out(false, 'El archivo es demasiado grande. Máximo permitido: 2 MB.');
    }

    // Validar tipo MIME real
    $finfo     = new finfo(FILEINFO_MIME_TYPE);
    $mime      = $finfo->file($file['tmp_name']);
    $tipos_ok  = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif', 'image/webp'];

    if (!in_array($mime, $tipos_ok)) {
        json_out(false, 'Tipo de archivo no permitido. Solo JPG, PNG, SVG, GIF o WebP.');
    }

    // Extensión segura
    $ext_map = [
        'image/jpeg'   => 'jpg',
        'image/png'    => 'png',
        'image/svg+xml'=> 'svg',
        'image/gif'    => 'gif',
        'image/webp'   => 'webp',
    ];
    $ext = $ext_map[$mime];

    $upload_dir = __DIR__ . '/../uploads/branding/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Nombre único para evitar colisiones y ataques path traversal
    $filename = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        json_out(false, 'No se pudo guardar el archivo. Verifica los permisos de la carpeta uploads/branding/.');
    }

    $logo_final = '/uploads/branding/' . $filename;
}

// ── Guardar en BD ──
try {
    $pdo = conectar();

        // Verificar si ya existe un registro para esta farmacia
    $fid = (int)$_SESSION['farmacia_id'];
    $check = $pdo->prepare("SELECT id FROM branding WHERE farmacia_id = ? AND activo = 1 LIMIT 1");
    $check->execute([$fid]);
    $check = $check->fetch();

    if ($check) {
        // Actualizar registro de esta farmacia
        $sql = "UPDATE branding SET
                    farmacia_nombre           = :nombre,
                    farmacia_slogan           = :slogan,
                    farmacia_color_primario   = :color_p,
                    farmacia_color_secundario = :color_s,
                    farmacia_logo_url         = :logo_url,
                    farmacia_direccion        = :direccion,
                    farmacia_telefono         = :telefono,
                    farmacia_ruc              = :ruc,
                    actualizado_por           = :uid
                WHERE farmacia_id = :fid AND activo = 1";
    } else {
        // Insertar primera vez para esta farmacia
        $sql = "INSERT INTO branding (farmacia_id, farmacia_nombre, farmacia_slogan, farmacia_color_primario, farmacia_color_secundario, farmacia_logo_url, farmacia_direccion, farmacia_telefono, farmacia_ruc, activo, actualizado_por)
                VALUES (:fid, :nombre, :slogan, :color_p, :color_s, :logo_url, :direccion, :telefono, :ruc, 1, :uid)";
    }

    $params = [
        ':fid'       => $fid,
        ':nombre'    => $nombre,
        ':slogan'    => $slogan ?: null,
        ':color_p'   => $color_p,
        ':color_s'   => $color_s,
        ':logo_url'  => $logo_final,
        ':direccion' => $direccion ?: null,
        ':telefono'  => $telefono ?: null,
        ':ruc'       => $ruc ?: null,
        ':uid'       => (int)$_SESSION['usuario_id'],
    ];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Invalidar cache de sesión para que se recargue el branding
    branding_invalidar_cache();

    json_out(true, 'Branding actualizado correctamente.');

} catch (PDOException $e) {
    // No exponer detalles de BD al cliente
    json_out(false, 'Error al guardar en la base de datos. Intenta de nuevo.');
}
