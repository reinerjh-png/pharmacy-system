<?php
// superadmin/farmacias/acceder.php
// Permite al super admin acceder temporalmente al panel de una farmacia como su administrador.
// Registra el evento en super_admin_impersonaciones para auditoría.

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../auth/session_superadmin.php';
require_once __DIR__ . '/../../config/db.php';

verificar_super_admin();

$pdo = conectar();
$farmacia_id = (int)($_GET['id'] ?? 0);

if (!$farmacia_id) {
    header('Location: ' . BASE_URL . '/superadmin/farmacias/lista.php');
    exit;
}

// Obtener la farmacia
$farmacia = $pdo->prepare("SELECT * FROM farmacias WHERE id = ? AND activo = 1");
$farmacia->execute([$farmacia_id]);
$farmacia = $farmacia->fetch();

if (!$farmacia) {
    $_SESSION['_sa_error'] = 'Farmacia no encontrada o está suspendida.';
    header('Location: ' . BASE_URL . '/superadmin/farmacias/lista.php');
    exit;
}

// Obtener el primer administrador (rol_id = 1) de la farmacia
$admin = $pdo->prepare("
    SELECT u.id, u.nombre, u.email, u.rol_id, u.farmacia_id,
           r.nombre as rol_nombre
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    WHERE u.farmacia_id = ? AND u.rol_id = 1 AND u.activo = 1
    ORDER BY u.id ASC
    LIMIT 1
");
$admin->execute([$farmacia_id]);
$admin = $admin->fetch();

if (!$admin) {
    $_SESSION['_sa_error'] = 'Esta farmacia no tiene un administrador activo.';
    header('Location: ' . BASE_URL . '/superadmin/farmacias/lista.php');
    exit;
}

// Registrar impersonación en el log de auditoría
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$pdo->prepare("
    INSERT INTO super_admin_impersonaciones (super_admin_id, farmacia_id, ip_origen)
    VALUES (?, ?, ?)
")->execute([super_admin_id(), $farmacia_id, $ip]);

// Guardar referencia de que es una sesión de impersonación
// (para que el sistema de farmacia pueda mostrar aviso y el SA pueda "salir")
$_SESSION['super_admin_impersonando'] = true;
$_SESSION['super_admin_id_backup']    = $_SESSION['super_admin_id'];
$_SESSION['super_admin_nombre_backup']= $_SESSION['super_admin_nombre'];

// Establecer sesión de farmacia como si fuera el admin
$_SESSION['usuario_id']     = $admin['id'];
$_SESSION['usuario_nombre'] = $admin['nombre'];
$_SESSION['nombre']         = $admin['nombre'];
$_SESSION['rol_id']         = $admin['rol_id'];
$_SESSION['usuario_rol']    = $admin['rol_nombre'];
$_SESSION['farmacia_id']    = $admin['farmacia_id'];

// Redirigir al dashboard de la farmacia
header('Location: ' . BASE_URL . '/index.php');
exit;
