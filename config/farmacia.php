<?php
// config/farmacia.php
// Thin wrapper de compatibilidad.
// Los valores ahora provienen de la base de datos via config/branding.php.
// Las constantes se mantienen para no romper código existente.

require_once __DIR__ . '/branding.php';

$_b = branding();

if (!defined('FARMACIA_NOMBRE'))    define('FARMACIA_NOMBRE',    $_b['farmacia_nombre']);
if (!defined('FARMACIA_SUBTITULO')) define('FARMACIA_SUBTITULO', $_b['farmacia_slogan'] ?? 'Sistema de Gestión');
if (!defined('FARMACIA_COLOR'))     define('FARMACIA_COLOR',     $_b['farmacia_color_primario']);
if (!defined('FARMACIA_VERSION'))   define('FARMACIA_VERSION',   '1.1.0');
