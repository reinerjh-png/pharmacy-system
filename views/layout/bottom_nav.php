<?php
// views/layout/bottom_nav.php
// Navegación inferior (mobile only)
$rol_id = (int)($_SESSION['rol_id'] ?? 0);
$base_url = '';
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav class="bottom-nav">
    <?php if ($rol_id === 1): ?>
    <a href="<?= $base_url ?>/index.php" class="bottom-nav-item <?= ($current_page === 'index.php') ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z" />
        </svg>
        <span>Dashboard</span>
    </a>
    <?php endif; ?>

    <?php if (in_array($rol_id, [1, 2])): ?>
    <a href="<?= $base_url ?>/modules/ventas/nueva_venta.php" class="bottom-nav-item <?= $current_page === 'nueva_venta.php' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84" />
        </svg>
        <span>Caja</span>
    </a>
    <a href="<?= $base_url ?>/modules/recetas/lista.php" class="bottom-nav-item <?= $current_dir === 'recetas' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span>Recetas</span>
    </a>
    <?php endif; ?>

    <?php if (in_array($rol_id, [1, 3])): ?>
    <a href="<?= $base_url ?>/modules/inventario/lista_productos.php" class="bottom-nav-item <?= $current_dir === 'inventario' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
        <span>Stock</span>
    </a>
    <?php endif; ?>

    <a href="<?= $base_url ?>/auth/logout.php" class="bottom-nav-item" style="color: var(--color-peligro);">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15" />
        </svg>
        <span>Salir</span>
    </a>
</nav>
