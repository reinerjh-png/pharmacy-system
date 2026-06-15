<?php
// superadmin/farmacias/lista.php
// Lista completa de todas las farmacias del SaaS

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../auth/session_superadmin.php';
require_once __DIR__ . '/../../config/db.php';

verificar_super_admin();

$pdo = conectar();

$exito = $_SESSION['_sa_exito'] ?? '';
$error_msg = $_SESSION['_sa_error'] ?? '';
unset($_SESSION['_sa_exito'], $_SESSION['_sa_error']);

// Toggle activo/inactivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    sa_csrf_verify();
    $id     = (int)$_POST['toggle_id'];
    $activo = (int)$_POST['activo'];
    $nuevo  = $activo ? 0 : 1;
    $pdo->prepare("UPDATE farmacias SET activo = ? WHERE id = ?")->execute([$nuevo, $id]);
    $_SESSION['_sa_exito'] = $nuevo ? 'Farmacia activada correctamente.' : 'Farmacia suspendida. Sus usuarios no podrán ingresar.';
    header('Location: lista.php');
    exit;
}

// Buscar
$buscar = trim($_GET['q'] ?? '');
$where  = $buscar ? "WHERE f.nombre LIKE ? OR f.ruc LIKE ? OR f.email_contacto LIKE ?" : '';
$params = $buscar ? ["%$buscar%", "%$buscar%", "%$buscar%"] : [];

$farmacias = $pdo->prepare("
    SELECT f.*,
           COUNT(DISTINCT u.id) as total_usuarios,
           sa.nombre as creado_por_nombre
    FROM farmacias f
    LEFT JOIN usuarios u ON u.farmacia_id = f.id
    LEFT JOIN super_admins sa ON f.creado_por = sa.id
    $where
    GROUP BY f.id
    ORDER BY f.activo DESC, f.creado_en DESC
");
$farmacias->execute($params);
$farmacias = $farmacias->fetchAll();

$pagina_titulo = 'Farmacias';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="sa-page-header">
    <div>
        <h1>Gestión de Farmacias</h1>
        <p class="sa-page-subtitle">Administra todos los tenants registrados en la plataforma</p>
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

<?php if ($exito): ?>
    <div class="sa-alerta sa-alerta-exito">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <?= htmlspecialchars($exito) ?>
    </div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="sa-alerta sa-alerta-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        <?= htmlspecialchars($error_msg) ?>
    </div>
<?php endif; ?>

<!-- Buscador -->
<div class="sa-card" style="margin-bottom: 20px; padding: 16px 20px;">
    <form method="GET" style="display:flex; gap:10px; align-items:center;">
        <div style="position:relative; flex:1; max-width:400px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                 style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94a3b8;pointer-events:none;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input type="text" name="q" class="sa-form-input" style="padding-left:38px;"
                   placeholder="Buscar por nombre, RUC o email..." value="<?= htmlspecialchars($buscar) ?>">
        </div>
        <button type="submit" class="sa-btn sa-btn-ghost">Buscar</button>
        <?php if ($buscar): ?>
            <a href="lista.php" class="sa-btn sa-btn-ghost">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabla -->
<div class="sa-card">
    <div class="sa-table-wrap">
        <?php if (empty($farmacias)): ?>
            <div class="sa-empty">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                </svg>
                <div class="sa-empty-title">No hay farmacias<?= $buscar ? ' que coincidan con la búsqueda' : '' ?></div>
                <div class="sa-empty-msg">
                    <?= $buscar ? 'Intenta con otros términos.' : 'Crea la primera farmacia para empezar.' ?>
                </div>
            </div>
        <?php else: ?>
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Farmacia</th>
                        <th>Contacto</th>
                        <th>Usuarios</th>
                        <th>Estado</th>
                        <th>Registrada</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($farmacias as $f): ?>
                        <tr>
                            <td style="color:var(--sa-texto-sec);font-size:0.78rem;font-weight:600;">#<?= $f['id'] ?></td>

                            <td>
                                <div style="display:flex;align-items:center;gap:11px;">
                                    <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#eef2ff,#ede9fe);color:#6366f1;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.05rem;flex-shrink:0;">
                                        <?= strtoupper(substr($f['nombre'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:0.875rem;"><?= htmlspecialchars($f['nombre']) ?></div>
                                        <?php if ($f['ruc']): ?>
                                            <div style="font-size:0.73rem;color:var(--sa-texto-sec);">RUC: <?= htmlspecialchars($f['ruc']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <?php if ($f['email_contacto']): ?>
                                    <div style="font-size:0.83rem;"><?= htmlspecialchars($f['email_contacto']) ?></div>
                                <?php endif; ?>
                                <?php if ($f['telefono']): ?>
                                    <div style="font-size:0.78rem;color:var(--sa-texto-sec);"><?= htmlspecialchars($f['telefono']) ?></div>
                                <?php endif; ?>
                                <?php if (!$f['email_contacto'] && !$f['telefono']): ?>
                                    <span style="color:var(--sa-texto-sec);font-size:0.8rem;">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span style="font-weight:700;"><?= $f['total_usuarios'] ?></span>
                                <span style="font-size:0.75rem;color:var(--sa-texto-sec);"> usuarios</span>
                            </td>

                            <td>
                                <?php if ($f['activo']): ?>
                                    <span class="sa-badge sa-badge-activo">
                                        <span style="width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block;"></span>
                                        Activa
                                    </span>
                                <?php else: ?>
                                    <span class="sa-badge sa-badge-inactivo">
                                        <span style="width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block;"></span>
                                        Suspendida
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td style="font-size:0.8rem;color:var(--sa-texto-sec);">
                                <?= date('d/m/Y', strtotime($f['creado_en'])) ?>
                            </td>

                            <td style="text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                                    <!-- Editar -->
                                    <a href="<?= BASE_URL ?>/superadmin/farmacias/editar.php?id=<?= $f['id'] ?>"
                                       class="sa-btn sa-btn-ghost sa-btn-sm" title="Editar farmacia">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                        </svg>
                                        Editar
                                    </a>

                                    <!-- Acceder como admin -->
                                    <a href="<?= BASE_URL ?>/superadmin/farmacias/acceder.php?id=<?= $f['id'] ?>"
                                       class="sa-btn sa-btn-ghost sa-btn-sm" title="Acceder al panel de esta farmacia"
                                       style="color:var(--sa-primario);border-color:rgba(99,102,241,0.25);">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                        Acceder
                                    </a>

                                    <!-- Activar/Suspender -->
                                    <form method="POST" style="margin:0;"
                                          onsubmit="return confirm('<?= $f['activo'] ? '¿Suspender esta farmacia? Sus usuarios no podrán ingresar.' : '¿Activar esta farmacia?' ?>')">
                                        <?= sa_csrf_field() ?>
                                        <input type="hidden" name="toggle_id" value="<?= $f['id'] ?>">
                                        <input type="hidden" name="activo" value="<?= $f['activo'] ?>">
                                        <button type="submit" class="sa-btn sa-btn-sm <?= $f['activo'] ? 'sa-btn-peligro' : '' ?>"
                                                style="<?= !$f['activo'] ? 'background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;' : '' ?>"
                                                title="<?= $f['activo'] ? 'Suspender' : 'Activar' ?>">
                                            <?php if ($f['activo']): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                                Suspender
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Activar
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
