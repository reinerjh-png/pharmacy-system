<?php
// superadmin/farmacias/editar.php
// Editar datos básicos de una farmacia existente

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../auth/session_superadmin.php';
require_once __DIR__ . '/../../config/db.php';

verificar_super_admin();

$pdo = conectar();
$id  = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: ' . BASE_URL . '/superadmin/farmacias/lista.php');
    exit;
}

$farmacia = $pdo->prepare("SELECT * FROM farmacias WHERE id = ?");
$farmacia->execute([$id]);
$farmacia = $farmacia->fetch();

if (!$farmacia) {
    $_SESSION['_sa_error'] = 'Farmacia no encontrada.';
    header('Location: ' . BASE_URL . '/superadmin/farmacias/lista.php');
    exit;
}

// Usuarios de esta farmacia
$usuarios = $pdo->prepare("
    SELECT u.id, u.nombre, u.email, u.activo, r.nombre as rol
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    WHERE u.farmacia_id = ?
    ORDER BY u.rol_id ASC, u.nombre ASC
");
$usuarios->execute([$id]);
$usuarios = $usuarios->fetchAll();

$errores = [];
$exito   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sa_csrf_verify();

    $nombre    = trim($_POST['nombre'] ?? '');
    $ruc       = trim($_POST['ruc'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (empty($nombre)) $errores[] = 'El nombre de la farmacia es obligatorio.';

    if (empty($errores)) {
        $pdo->prepare("
            UPDATE farmacias
            SET nombre = ?, ruc = ?, telefono = ?, email_contacto = ?, direccion = ?
            WHERE id = ?
        ")->execute([$nombre, $ruc ?: null, $telefono ?: null, $email ?: null, $direccion ?: null, $id]);

        $exito = 'Datos de la farmacia actualizados correctamente.';
        // Refrescar datos
        $farmacia['nombre']         = $nombre;
        $farmacia['ruc']            = $ruc;
        $farmacia['telefono']       = $telefono;
        $farmacia['email_contacto'] = $email;
        $farmacia['direccion']      = $direccion;
    }
}

$pagina_titulo = 'Editar Farmacia';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="sa-page-header">
    <div>
        <h1>Editar Farmacia</h1>
        <p class="sa-page-subtitle">Modificando: <strong><?= htmlspecialchars($farmacia['nombre']) ?></strong> · #<?= $id ?></p>
    </div>
    <div class="sa-page-actions">
        <a href="<?= BASE_URL ?>/superadmin/farmacias/acceder.php?id=<?= $id ?>" class="sa-btn sa-btn-ghost" style="color:var(--sa-primario);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
            Acceder al Panel
        </a>
        <a href="<?= BASE_URL ?>/superadmin/farmacias/lista.php" class="sa-btn sa-btn-ghost">← Volver</a>
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
<?php if (!empty($errores)): ?>
    <div class="sa-alerta sa-alerta-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        <div><?php foreach ($errores as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
    </div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:start;">

    <!-- Formulario -->
    <form method="POST" novalidate>
        <?= sa_csrf_field() ?>
        <div class="sa-card">
            <div class="sa-card-header">
                <span class="sa-card-title">Datos de la Farmacia</span>
                <span class="sa-badge <?= $farmacia['activo'] ? 'sa-badge-activo' : 'sa-badge-inactivo' ?>">
                    <?= $farmacia['activo'] ? 'Activa' : 'Suspendida' ?>
                </span>
            </div>
            <div class="sa-card-body">
                <div class="sa-form-group">
                    <label class="sa-form-label" for="nombre">Nombre <span>*</span></label>
                    <input type="text" name="nombre" id="nombre" class="sa-form-input" required
                           value="<?= htmlspecialchars($farmacia['nombre']) ?>">
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label" for="ruc">RUC</label>
                    <input type="text" name="ruc" id="ruc" class="sa-form-input" maxlength="20"
                           value="<?= htmlspecialchars($farmacia['ruc'] ?? '') ?>">
                </div>
                <div class="sa-form-grid-2">
                    <div class="sa-form-group">
                        <label class="sa-form-label" for="telefono">Teléfono</label>
                        <input type="text" name="telefono" id="telefono" class="sa-form-input"
                               value="<?= htmlspecialchars($farmacia['telefono'] ?? '') ?>">
                    </div>
                    <div class="sa-form-group">
                        <label class="sa-form-label" for="email">Email Contacto</label>
                        <input type="email" name="email" id="email" class="sa-form-input"
                               value="<?= htmlspecialchars($farmacia['email_contacto'] ?? '') ?>">
                    </div>
                </div>
                <div class="sa-form-group" style="margin-bottom:0;">
                    <label class="sa-form-label" for="direccion">Dirección</label>
                    <textarea name="direccion" id="direccion" class="sa-form-textarea"><?= htmlspecialchars($farmacia['direccion'] ?? '') ?></textarea>
                </div>
            </div>
            <div style="padding:16px 24px; border-top:1px solid var(--sa-borde); display:flex; justify-content:flex-end; gap:10px;">
                <button type="submit" class="sa-btn sa-btn-primario">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6A2.25 2.25 0 016 3.75h1.5m9 0h-9" />
                    </svg>
                    Guardar Cambios
                </button>
            </div>
        </div>
    </form>

    <!-- Usuarios de la farmacia (solo lectura) -->
    <div class="sa-card">
        <div class="sa-card-header">
            <span class="sa-card-title">Usuarios de esta Farmacia</span>
            <span style="font-size:0.78rem;color:var(--sa-texto-sec);"><?= count($usuarios) ?> usuario(s)</span>
        </div>
        <div class="sa-table-wrap">
            <?php if (empty($usuarios)): ?>
                <div class="sa-empty" style="padding:30px;">
                    <div class="sa-empty-title">Sin usuarios</div>
                    <div class="sa-empty-msg">Esta farmacia no tiene usuarios registrados.</div>
                </div>
            <?php else: ?>
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;font-size:0.85rem;"><?= htmlspecialchars($u['nombre']) ?></div>
                                    <div style="font-size:0.75rem;color:var(--sa-texto-sec);"><?= htmlspecialchars($u['email']) ?></div>
                                </td>
                                <td>
                                    <span class="sa-badge sa-badge-admin"><?= htmlspecialchars($u['rol']) ?></span>
                                </td>
                                <td>
                                    <span class="sa-badge <?= $u['activo'] ? 'sa-badge-activo' : 'sa-badge-inactivo' ?>">
                                        <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
