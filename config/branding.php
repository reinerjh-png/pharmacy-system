<?php
// config/branding.php
// Helper centralizado de identidad de marca.
// Carga la configuración desde BD UNA SOLA VEZ por sesión (cache en $_SESSION).
// Uso: $b = branding();  echo $b['farmacia_nombre'];
//      En el <head>: echo branding_css_vars();

require_once __DIR__ . '/db.php';

/**
 * Retorna la configuración de branding activa.
 * Usa caché de sesión para evitar consultas repetidas por petición/sesión.
 */
function branding(): array {
    // Cache de sesión — incluye farmacia_id para que no haya cruces entre tenants
    $fid = (int)($_SESSION['farmacia_id'] ?? 1);
    $cache_key = '_branding_cache_' . $fid;

    if (isset($_SESSION[$cache_key]) && is_array($_SESSION[$cache_key])) {
        return $_SESSION[$cache_key];
    }

    $defaults = _branding_defaults();

    try {
        $pdo  = conectar();
        // Filtrar por farmacia_id del tenant activo
        $stmt = $pdo->prepare("SELECT * FROM branding WHERE farmacia_id = ? AND activo = 1 ORDER BY id ASC LIMIT 1");
        $stmt->execute([$fid]);
        $row  = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

        if ($row) {
            $config = array_merge($defaults, array_filter($row, fn($v) => $v !== null));
        } else {
            $config = $defaults;
        }
    } catch (Throwable $e) {
        // Si la BD falla (ej. tabla aún no existe), usar defaults sin romper el sistema
        $config = $defaults;
    }

    // Calcular variantes de color dinámicas
    $config['_color_hover']     = _color_darken($config['farmacia_color_primario'], 10);
    $config['_color_claro']     = _color_lighten($config['farmacia_color_primario'], 88);
    $config['_color_muy_oscuro']= _color_darken($config['farmacia_color_primario'], 30);

    // Guardar en sesión con clave por farmacia
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION[$cache_key] = $config;
    }

    return $config;
}

/**
 * Invalida el cache de branding en la sesión actual.
 * Llamar después de guardar cambios en el panel de ajustes.
 */
function branding_invalidar_cache(): void {
    $fid = (int)($_SESSION['farmacia_id'] ?? 1);
    unset($_SESSION['_branding_cache_' . $fid]);
    // Compatibilidad hacia atrás
    unset($_SESSION['_branding_cache']);
}

/**
 * Genera el bloque <style> con CSS custom properties dinámicas.
 * Debe incluirse dentro de <head> en header.php y login.php.
 */
function branding_css_vars(): string {
    $b = branding();
    $primario    = htmlspecialchars($b['farmacia_color_primario'], ENT_QUOTES);
    $secundario  = htmlspecialchars($b['farmacia_color_secundario'], ENT_QUOTES);
    $hover       = htmlspecialchars($b['_color_hover'], ENT_QUOTES);
    $claro       = htmlspecialchars($b['_color_claro'], ENT_QUOTES);
    $muy_oscuro  = htmlspecialchars($b['_color_muy_oscuro'], ENT_QUOTES);

    return <<<CSS
    <style id="branding-css-vars">
        :root {
            --color-primario:        {$primario};
            --color-primario-hover:  {$hover};
            --color-primario-claro:  {$claro};
            --color-primario-oscuro: {$muy_oscuro};
            --color-secundario:      {$secundario};
        }
    </style>
    CSS;
}

/**
 * Valores por defecto del sistema (usados si la BD no responde o la tabla no existe aún).
 */
function _branding_defaults(): array {
    return [
        'id'                       => 1,
        'farmacia_nombre'          => 'Mi Farmacia',
        'farmacia_slogan'          => 'Sistema de Gestión',
        'farmacia_color_primario'  => '#059669',
        'farmacia_color_secundario'=> '#10b981',
        'farmacia_logo_url'        => null,
        'farmacia_direccion'       => null,
        'farmacia_telefono'        => null,
        'farmacia_ruc'             => null,
        'activo'                   => 1,
    ];
}

// ── Utilidades de color ──────────────────────────────────────────────────────

/**
 * Oscurece un color hex por un porcentaje dado (0-100).
 */
function _color_darken(string $hex, int $percent): string {
    [$r, $g, $b] = _hex_to_rgb($hex);
    $factor = 1 - ($percent / 100);
    return _rgb_to_hex(
        (int)round($r * $factor),
        (int)round($g * $factor),
        (int)round($b * $factor)
    );
}

/**
 * Aclara un color hex mezclándolo con blanco por un porcentaje dado (0-100).
 */
function _color_lighten(string $hex, int $percent): string {
    [$r, $g, $b] = _hex_to_rgb($hex);
    $factor = $percent / 100;
    return _rgb_to_hex(
        (int)round($r + (255 - $r) * $factor),
        (int)round($g + (255 - $g) * $factor),
        (int)round($b + (255 - $b) * $factor)
    );
}

function _hex_to_rgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function _rgb_to_hex(int $r, int $g, int $b): string {
    return sprintf('#%02x%02x%02x',
        max(0, min(255, $r)),
        max(0, min(255, $g)),
        max(0, min(255, $b))
    );
}
