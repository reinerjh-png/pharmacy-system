<?php
// superadmin/layout/sidebar.php
$sa_page     = basename($_SERVER['PHP_SELF']);
$sa_dir      = basename(dirname($_SERVER['PHP_SELF']));
$sa_nombre   = htmlspecialchars($_SESSION['super_admin_nombre'] ?? 'Super Admin');
$sa_inicial  = strtoupper(substr($sa_nombre, 0, 1));
?>
<aside class="sa-sidebar">
    <div class="sa-sidebar-header">
        <!-- Marca del SaaS -->
        <div class="sa-brand">
            <div class="sa-brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
            </div>
            <div>
                <div class="sa-brand-name">FarmaCloud</div>
                <div class="sa-brand-tag">SaaS Platform</div>
            </div>
        </div>

        <!-- Usuario super admin -->
        <div class="sa-user-card">
            <div class="sa-user-avatar"><?= $sa_inicial ?></div>
            <div style="overflow:hidden;">
                <div class="sa-user-nombre"><?= $sa_nombre ?></div>
                <div class="sa-user-rol">Super Admin</div>
            </div>
        </div>
    </div>

    <nav class="sa-nav">
        <!-- Principal -->
        <div class="sa-nav-section">
            <div class="sa-nav-label">Principal</div>
            <a href="<?= BASE_URL ?>/superadmin/index.php"
               class="sa-nav-item <?= $sa_page === 'index.php' && $sa_dir === 'superadmin' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                Dashboard
            </a>
        </div>

        <!-- Gestión de Farmacias -->
        <div class="sa-nav-section">
            <div class="sa-nav-label">Farmacias</div>
            <a href="<?= BASE_URL ?>/superadmin/farmacias/lista.php"
               class="sa-nav-item <?= $sa_dir === 'farmacias' && $sa_page !== 'crear.php' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                </svg>
                Todas las Farmacias
            </a>
            <a href="<?= BASE_URL ?>/superadmin/farmacias/crear.php"
               class="sa-nav-item <?= $sa_dir === 'farmacias' && $sa_page === 'crear.php' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva Farmacia
            </a>
        </div>
    </nav>

    <div class="sa-sidebar-footer">
        <a href="<?= BASE_URL ?>/superadmin/logout.php" class="sa-nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
            Cerrar Sesión
        </a>
    </div>
</aside>
