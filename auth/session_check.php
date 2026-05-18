<?php
// auth/session_check.php
// Funciones de verificación de sesión y permisos

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica que el usuario tenga una sesión activa
 * Redirige al login si no hay sesión
 */
function verificar_sesion(): void {
    if (empty($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
        header('Location: /sys-farmacia/auth/login.php');
        exit;
    }
}

// ====================================================
// PROTECCIÓN CSRF
// ====================================================

/**
 * Genera (o reutiliza) el token CSRF de la sesión actual
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retorna el campo hidden HTML con el token CSRF listo para insertar en formularios
 */
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Valida el token CSRF enviado en el request POST
 * Termina con 403 si el token es inválido o ausente
 */
function csrf_verify(): void {
    $token_enviado = $_POST['_csrf'] ?? '';
    if (!hash_equals(csrf_token(), $token_enviado)) {
        http_response_code(403);
        die('Solicitud inválida. Por favor recarga la página e inténtalo de nuevo.');
    }
}

/**
 * Verifica que el usuario tenga permiso para acceder a un módulo
 * Muestra 403 si no tiene acceso
 */
function verificar_permiso(string $modulo): void {
    verificar_sesion();
    $permisos = require __DIR__ . '/../config/permisos.php';
    $rol = (int) $_SESSION['rol_id'];
    if (!in_array($rol, $permisos[$modulo] ?? [])) {
        http_response_code(403);
        include __DIR__ . '/../views/403.php';
        exit;
    }
}

/**
 * Verifica si el usuario actual es administrador
 */
function es_admin(): bool {
    return isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === 1;
}

/**
 * Verifica si el usuario actual es cajero
 */
function es_cajero(): bool {
    return isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === 2;
}

/**
 * Verifica si el usuario actual es almacenero
 */
function es_almacenero(): bool {
    return isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === 3;
}

/**
 * Obtiene el nombre del usuario actual
 */
function nombre_usuario(): string {
    return htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');
}

/**
 * Obtiene el nombre del rol actual
 */
function nombre_rol(): string {
    $roles = [1 => 'Administrador', 2 => 'Cajero', 3 => 'Almacenero'];
    return $roles[$_SESSION['rol_id'] ?? 0] ?? 'Sin rol';
}

/**
 * Obtiene la URL base del sistema
 */
function base_url(): string {
    return '/sys-farmacia';
}
