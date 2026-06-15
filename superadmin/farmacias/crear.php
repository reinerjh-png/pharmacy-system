<?php
// superadmin/farmacias/crear.php
// Formulario para crear una nueva farmacia con su administrador inicial

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../auth/session_superadmin.php';
require_once __DIR__ . '/../../config/db.php';

verificar_super_admin();

$pdo    = conectar();
$errores = [];
$datos  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sa_csrf_verify();

    // ── Datos farmacia ──
    $datos['farmacia_nombre']   = trim($_POST['farmacia_nombre'] ?? '');
    $datos['farmacia_ruc']      = trim($_POST['farmacia_ruc'] ?? '');
    $datos['farmacia_telefono'] = trim($_POST['farmacia_telefono'] ?? '');
    $datos['farmacia_email']    = trim($_POST['farmacia_email'] ?? '');
    $datos['farmacia_direccion']= trim($_POST['farmacia_direccion'] ?? '');

    // ── Datos administrador ──
    $datos['admin_nombre']   = trim($_POST['admin_nombre'] ?? '');
    $datos['admin_email']    = trim($_POST['admin_email'] ?? '');
    $datos['admin_password'] = $_POST['admin_password'] ?? '';
    $datos['admin_password2']= $_POST['admin_password2'] ?? '';

    // ── Validaciones ──
    if (empty($datos['farmacia_nombre'])) $errores[] = 'El nombre de la farmacia es obligatorio.';

    if (empty($datos['admin_nombre']))  $errores[] = 'El nombre del administrador es obligatorio.';
    if (empty($datos['admin_email']))   $errores[] = 'El correo del administrador es obligatorio.';
    if (!filter_var($datos['admin_email'], FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo del administrador no es válido.';
    if (strlen($datos['admin_password']) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($datos['admin_password'] !== $datos['admin_password2']) $errores[] = 'Las contraseñas no coinciden.';

    // Verificar email único
    if (empty($errores)) {
        $existe = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $existe->execute([$datos['admin_email']]);
        if ($existe->fetch()) $errores[] = 'Ya existe un usuario con ese correo electrónico.';
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            // 1. Crear farmacia
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $datos['farmacia_nombre']));
            $slug = trim($slug, '-');

            // Verificar slug único
            $slugBase = $slug;
            $i = 1;
            while (true) {
                $check = $pdo->prepare("SELECT id FROM farmacias WHERE slug = ?");
                $check->execute([$slug]);
                if (!$check->fetch()) break;
                $slug = $slugBase . '-' . (++$i);
            }

            $stmtF = $pdo->prepare("
                INSERT INTO farmacias (nombre, slug, ruc, telefono, email_contacto, direccion, creado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtF->execute([
                $datos['farmacia_nombre'],
                $slug,
                $datos['farmacia_ruc']      ?: null,
                $datos['farmacia_telefono'] ?: null,
                $datos['farmacia_email']    ?: null,
                $datos['farmacia_direccion']?: null,
                super_admin_id(),
            ]);
            $farmacia_id = (int)$pdo->lastInsertId();

            // 2. Crear usuario administrador (rol_id = 1 = admin)
            $hash = password_hash($datos['admin_password'], PASSWORD_BCRYPT);
            $stmtU = $pdo->prepare("
                INSERT INTO usuarios (nombre, email, password_hash, rol_id, farmacia_id)
                VALUES (?, ?, ?, 1, ?)
            ");
            $stmtU->execute([
                $datos['admin_nombre'],
                $datos['admin_email'],
                $hash,
                $farmacia_id,
            ]);

            // 3. Crear branding inicial para la farmacia
            $stmtB = $pdo->prepare("
                INSERT INTO branding (farmacia_id, farmacia_nombre, farmacia_slogan, farmacia_color_primario, farmacia_color_secundario)
                VALUES (?, ?, 'Sistema de Gestión', '#059669', '#10b981')
            ");
            $stmtB->execute([$farmacia_id, $datos['farmacia_nombre']]);

            $pdo->commit();

            $_SESSION['_sa_exito'] = "Farmacia «{$datos['farmacia_nombre']}» creada correctamente con su administrador.";
            header('Location: ' . BASE_URL . '/superadmin/farmacias/lista.php');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores[] = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

$pagina_titulo = 'Nueva Farmacia';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="sa-page-header">
    <div>
        <h1>Nueva Farmacia</h1>
        <p class="sa-page-subtitle">Registra un nuevo tenant en la plataforma FarmaCloud</p>
    </div>
    <div class="sa-page-actions">
        <a href="<?= BASE_URL ?>/superadmin/farmacias/lista.php" class="sa-btn sa-btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Volver
        </a>
    </div>
</div>

<?php if (!empty($errores)): ?>
    <div class="sa-alerta sa-alerta-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        <div>
            <?php foreach ($errores as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<form method="POST" novalidate>
    <?= sa_csrf_field() ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">

        <!-- Sección: Datos de la Farmacia -->
        <div class="sa-card">
            <div class="sa-card-header">
                <span class="sa-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         style="width:18px;height:18px;display:inline-block;vertical-align:middle;margin-right:6px;color:var(--sa-primario);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                    </svg>
                    Datos de la Farmacia
                </span>
            </div>
            <div class="sa-card-body">
                <div class="sa-form-group">
                    <label class="sa-form-label" for="farmacia_nombre">Nombre de la Farmacia <span>*</span></label>
                    <input type="text" name="farmacia_nombre" id="farmacia_nombre" class="sa-form-input"
                           placeholder="Ej: Farmacia San Juan" required
                           value="<?= htmlspecialchars($datos['farmacia_nombre'] ?? '') ?>">
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label" for="farmacia_ruc">RUC</label>
                    <input type="text" name="farmacia_ruc" id="farmacia_ruc" class="sa-form-input"
                           placeholder="20123456789" maxlength="20"
                           value="<?= htmlspecialchars($datos['farmacia_ruc'] ?? '') ?>">
                </div>
                <div class="sa-form-grid-2">
                    <div class="sa-form-group">
                        <label class="sa-form-label" for="farmacia_telefono">Teléfono</label>
                        <input type="text" name="farmacia_telefono" id="farmacia_telefono" class="sa-form-input"
                               placeholder="01-4441234"
                               value="<?= htmlspecialchars($datos['farmacia_telefono'] ?? '') ?>">
                    </div>
                    <div class="sa-form-group">
                        <label class="sa-form-label" for="farmacia_email">Email de Contacto</label>
                        <input type="email" name="farmacia_email" id="farmacia_email" class="sa-form-input"
                               placeholder="contacto@farmacia.com"
                               value="<?= htmlspecialchars($datos['farmacia_email'] ?? '') ?>">
                    </div>
                </div>
                <div class="sa-form-group" style="margin-bottom:0;">
                    <label class="sa-form-label" for="farmacia_direccion">Dirección</label>
                    <textarea name="farmacia_direccion" id="farmacia_direccion" class="sa-form-textarea"
                              placeholder="Av. Principal 123, Lima..."
                              style="min-height:70px;"><?= htmlspecialchars($datos['farmacia_direccion'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Sección: Administrador Inicial -->
        <div class="sa-card">
            <div class="sa-card-header">
                <span class="sa-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         style="width:18px;height:18px;display:inline-block;vertical-align:middle;margin-right:6px;color:var(--sa-primario);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    Administrador de la Farmacia
                </span>
            </div>
            <div class="sa-card-body">
                <div class="sa-alerta sa-alerta-info" style="margin-bottom:20px;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    Este usuario tendrá acceso total a la farmacia y podrá crear más usuarios desde su panel.
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label" for="admin_nombre">Nombre Completo <span>*</span></label>
                    <input type="text" name="admin_nombre" id="admin_nombre" class="sa-form-input"
                           placeholder="Dr. Juan Pérez"
                           value="<?= htmlspecialchars($datos['admin_nombre'] ?? '') ?>">
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label" for="admin_email">Correo Electrónico <span>*</span></label>
                    <input type="email" name="admin_email" id="admin_email" class="sa-form-input"
                           placeholder="admin@farmacia.com"
                           value="<?= htmlspecialchars($datos['admin_email'] ?? '') ?>">
                    <div class="sa-form-hint">Será el usuario de acceso al sistema de la farmacia.</div>
                </div>
                <div class="sa-form-group">
                    <label class="sa-form-label" for="admin_password">Contraseña <span>*</span></label>
                    <input type="password" name="admin_password" id="admin_password" class="sa-form-input"
                           placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                </div>
                <div class="sa-form-group" style="margin-bottom:0;">
                    <label class="sa-form-label" for="admin_password2">Confirmar Contraseña <span>*</span></label>
                    <input type="password" name="admin_password2" id="admin_password2" class="sa-form-input"
                           placeholder="Repetir contraseña" autocomplete="new-password">
                </div>
            </div>
        </div>
    </div>

    <!-- Botones -->
    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
        <a href="<?= BASE_URL ?>/superadmin/farmacias/lista.php" class="sa-btn sa-btn-ghost">Cancelar</a>
        <button type="submit" class="sa-btn sa-btn-primario">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Crear Farmacia
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
