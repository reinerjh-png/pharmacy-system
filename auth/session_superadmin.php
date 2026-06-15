<?php
// auth/session_superadmin.php
// Funciones de verificación de sesión exclusivas para el Super Administrador del SaaS.
// El super admin tiene sesión INDEPENDIENTE de los usuarios de farmacia.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica que exista una sesión de super admin activa.
 * Redirige al login del super admin si no la hay.
 */
function verificar_super_admin(): void {
    if (empty($_SESSION['super_admin_id'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Retorna true si la sesión actual es de un super admin.
 */
function es_super_admin(): bool {
    return !empty($_SESSION['super_admin_id']);
}

/**
 * Retorna el ID del super admin en sesión.
 */
function super_admin_id(): int {
    return (int)($_SESSION['super_admin_id'] ?? 0);
}

/**
 * Retorna el nombre del super admin en sesión.
 */
function super_admin_nombre(): string {
    return htmlspecialchars($_SESSION['super_admin_nombre'] ?? 'Super Admin');
}

// ====================================================
// PROTECCIÓN CSRF (reutiliza el mismo mecanismo)
// ====================================================

function sa_csrf_token(): string {
    if (empty($_SESSION['sa_csrf_token'])) {
        $_SESSION['sa_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['sa_csrf_token'];
}

function sa_csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(sa_csrf_token()) . '">';
}

function sa_csrf_verify(): void {
    $token_enviado = $_POST['_csrf'] ?? '';
    if (!hash_equals(sa_csrf_token(), $token_enviado)) {
        http_response_code(403);
        die('Solicitud inválida. Por favor recarga la página e inténtalo de nuevo.');
    }
}
