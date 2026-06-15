<?php
// views/layout/header.php
// Cabecera HTML y Topbar compartida
require_once __DIR__ . '/../../config/branding.php';
require_once __DIR__ . '/../../config/farmacia.php';

$_brand  = branding();
$rol_id  = (int)($_SESSION['rol_id'] ?? 0);
$base_url = '';

// ── Cabeceras de Seguridad HTTP ──
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

$_brand_nombre = htmlspecialchars($_brand['farmacia_nombre']);
$_brand_logo   = $_brand['farmacia_logo_url'] ? htmlspecialchars($_brand['farmacia_logo_url']) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pagina_titulo) ? htmlspecialchars($pagina_titulo) . ' - ' : '' ?><?= $_brand_nombre ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS del Sistema de Diseño -->
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/main.css?v=2.1">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/layout.css?v=2.1">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/components.css?v=2.1">
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/responsive.css?v=2.1">

    <!-- CSS Variables dinámicas de Branding -->
    <?= branding_css_vars() ?>

    <?php if (isset($extra_css)): echo $extra_css; endif; ?>
</head>
<body>

    <div class="app-container">

        <!-- Sidebar Desktop/Tablet -->
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <!-- Topbar Mobile -->
            <div class="mobile-topbar">
                <div class="mobile-brand">
                    <?php if ($_brand_logo): ?>
                        <img src="<?= $_brand_logo ?>" alt="Logo <?= $_brand_nombre ?>"
                             style="height:28px; width:auto; object-fit:contain; border-radius:4px;">
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    <?php endif; ?>
                    <?= $_brand_nombre ?>
                </div>
                <button id="btn-menu-mobile" class="btn btn-ghost" aria-label="Abrir menú">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
            <!-- Fin de header.php -->
