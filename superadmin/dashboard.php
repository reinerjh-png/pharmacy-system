<?php
// superadmin/index.php
// Dashboard principal del Super Administrador del SaaS

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../auth/session_superadmin.php';
require_once __DIR__ . '/../config/db.php';

verificar_super_admin();

$pdo = conectar();

// ── KPIs globales ──
$total_farmacias  = (int)$pdo->query("SELECT COUNT(*) FROM farmacias")->fetchColumn();
$activas          = (int)$pdo->query("SELECT COUNT(*) FROM farmacias WHERE activo = 1")->fetchColumn();
$inactivas        = $total_farmacias - $activas;
$total_usuarios   = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

// ── Farmacias recientes ──
$farmacias_recientes = $pdo->query("
    SELECT f.*, 
           COUNT(DISTINCT u.id) as total_usuarios,
           sa.nombre as creado_por_nombre
    FROM farmacias f
    LEFT JOIN usuarios u ON u.farmacia_id = f.id
    LEFT JOIN super_admins sa ON f.creado_por = sa.id
    GROUP BY f.id
    ORDER BY f.creado_en DESC
    LIMIT 5
")->fetchAll();

$pagina_titulo = 'Dashboard';
require_once __DIR__ . '/layout/header.php';
?>

<!-- Page Header -->
<div class="sa-page-header">
    <div>
        <h1>Panel de Control</h1>
        <p class="sa-page-subtitle">Vista global de todas las farmacias en la plataforma · <?= date('d F Y') ?></p>
    </div>
    <div class="sa-page-actions">
        <a href="<?= BASE_URL ?>/superadmin/farmacias/crear.php" class="sa-btn sa-btn-primario">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nueva Farmacia
        </a>
    </div>
</div>

<!-- KPIs -->
<div class="sa-kpi-grid">
    <div class="sa-kpi-card">
        <div class="sa-kpi-icon sa-kpi-icon-indigo">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
            </svg>
        </div>
        <div>
            <div class="sa-kpi-label">Total Farmacias</div>
            <div class="sa-kpi-valor"><?= $total_farmacias ?></div>
        </div>
    </div>

    <div class="sa-kpi-card">
        <div class="sa-kpi-icon sa-kpi-icon-green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <div class="sa-kpi-label">Farmacias Activas</div>
            <div class="sa-kpi-valor" style="color: #059669;"><?= $activas ?></div>
        </div>
    </div>

    <div class="sa-kpi-card">
        <div class="sa-kpi-icon sa-kpi-icon-red">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
        </div>
        <div>
            <div class="sa-kpi-label">Suspendidas</div>
            <div class="sa-kpi-valor" style="color: #dc2626;"><?= $inactivas ?></div>
        </div>
    </div>

    <div class="sa-kpi-card">
        <div class="sa-kpi-icon sa-kpi-icon-purple">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>
        </div>
        <div>
            <div class="sa-kpi-label">Usuarios Totales</div>
            <div class="sa-kpi-valor"><?= $total_usuarios ?></div>
        </div>
    </div>
</div>

<!-- Tabla de farmacias recientes -->
<div class="sa-card">
    <div class="sa-card-header">
        <span class="sa-card-title">Farmacias Registradas</span>
        <a href="<?= BASE_URL ?>/superadmin/farmacias/lista.php" class="sa-btn sa-btn-ghost sa-btn-sm">
            Ver todas →
        </a>
    </div>
    <div class="sa-table-wrap">
        <?php if (empty($farmacias_recientes)): ?>
            <div class="sa-empty">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                </svg>
                <div class="sa-empty-title">Sin farmacias aún</div>
                <div class="sa-empty-msg">Crea la primera farmacia para empezar.</div>
            </div>
        <?php else: ?>
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Farmacia</th>
                        <th>RUC</th>
                        <th>Usuarios</th>
                        <th>Estado</th>
                        <th>Creada</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($farmacias_recientes as $f): ?>
                        <tr>
                            <td style="color: var(--sa-texto-sec); font-size: 0.8rem;">#<?= $f['id'] ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#eef2ff,#ede9fe);color:#6366f1;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.95rem;flex-shrink:0;">
                                        <?= strtoupper(substr($f['nombre'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($f['nombre']) ?></div>
                                        <?php if ($f['email_contacto']): ?>
                                            <div style="font-size:0.75rem;color:var(--sa-texto-sec);"><?= htmlspecialchars($f['email_contacto']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:0.85rem;color:var(--sa-texto-sec);"><?= htmlspecialchars($f['ruc'] ?? '—') ?></td>
                            <td>
                                <span style="font-weight:600;font-size:0.875rem;"><?= $f['total_usuarios'] ?></span>
                                <span style="font-size:0.75rem;color:var(--sa-texto-sec);"> usuarios</span>
                            </td>
                            <td>
                                <?php if ($f['activo']): ?>
                                    <span class="sa-badge sa-badge-activo">
                                        <span style="width:5px;height:5px;border-radius:50%;background:currentColor;"></span>
                                        Activa
                                    </span>
                                <?php else: ?>
                                    <span class="sa-badge sa-badge-inactivo">
                                        <span style="width:5px;height:5px;border-radius:50%;background:currentColor;"></span>
                                        Suspendida
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.8rem;color:var(--sa-texto-sec);">
                                <?= date('d/m/Y', strtotime($f['creado_en'])) ?>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <a href="<?= BASE_URL ?>/superadmin/farmacias/editar.php?id=<?= $f['id'] ?>" class="sa-btn sa-btn-ghost sa-btn-sm" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:15px;height:15px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                        </svg>
                                    </a>
                                    <a href="<?= BASE_URL ?>/superadmin/farmacias/acceder.php?id=<?= $f['id'] ?>" class="sa-btn sa-btn-ghost sa-btn-sm" title="Acceder como admin"
                                       style="color: var(--sa-primario);">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:15px;height:15px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
