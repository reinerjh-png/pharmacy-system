<?php
// modules/usuarios/agregar.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('usuarios');
$pdo = conectar();

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email  = trim($_POST['email']);
    $password = $_POST['password'];
    $rol_id = (int)$_POST['rol_id'];

    if (empty($nombre) || empty($email) || empty($password) || empty($rol_id)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            // Asignar farmacia_id del admin que crea el usuario
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol_id, farmacia_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $email, $hash, $rol_id, farmacia_id()]);
            header('Location: lista.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "El correo ya está registrado en el sistema.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Solo roles de farmacia (excluyendo superadmin que no existe en esta tabla)
$roles = $pdo->query("SELECT * FROM roles WHERE id IN (1,2,3) ORDER BY id ASC")->fetchAll();

$pagina_titulo = 'Agregar Usuario';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 800px;">

    <div class="page-header">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="lista.php" class="btn btn-sm btn-secundario" style="padding: 0.25rem 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <h1 style="margin: 0;">Nuevo Usuario</h1>
            </div>
            <p class="page-subtitle" style="margin-left: 45px;">Crea una cuenta de acceso para un miembro del personal.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alerta alerta-peligro animate-shake mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="card" style="overflow: hidden;">
        <div class="card-header border-b border-borde">
            <h2 class="card-titulo">Información de la Cuenta</h2>
        </div>
        <div class="card-body">
            <div class="form-group mb-5">
                <label class="form-label" style="font-weight: 500; margin-bottom: 0.4rem; display: block;">
                    Nombre Completo <span class="text-peligro">*</span>
                </label>
                <input type="text" name="nombre" id="nombre" class="form-control" required
                    placeholder="Ej. María Pérez Chávez"
                    style="font-size: 1.05rem; padding: 0.75rem;">
            </div>
            <div class="form-group mb-5">
                <label class="form-label" style="font-weight: 500; margin-bottom: 0.4rem; display: block;">
                    Correo Electrónico <span class="text-peligro">*</span>
                </label>
                <div class="flex items-center">
                    <span style="background: var(--color-fondo); border: 1px solid var(--color-borde); border-right: none; border-radius: var(--radio-md) 0 0 var(--radio-md); padding: 0.7rem 0.75rem; color: var(--texto-secundario);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    <input type="email" name="email" class="form-control" required
                        placeholder="usuario@farmacia.com"
                        style="border-radius: 0 var(--radio-md) var(--radio-md) 0;">
                </div>
            </div>
            <div class="grid-2-cols gap-6 mb-5">
                <div class="form-group">
                    <label class="form-label text-secundario text-sm" style="display: block; margin-bottom: 0.25rem;">
                        Contraseña <span class="text-peligro">*</span>
                    </label>
                    <input type="password" name="password" class="form-control" required
                        placeholder="Mín. 8 caracteres">
                </div>
                <div class="form-group">
                    <label class="form-label text-secundario text-sm" style="display: block; margin-bottom: 0.25rem;">
                        Rol del Sistema <span class="text-peligro">*</span>
                    </label>
                    <select name="rol_id" class="form-control" required>
                        <option value="">-- Seleccionar rol --</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="alerta" style="background: var(--color-primario-claro); border-left: 3px solid var(--color-primario); border-radius: var(--radio-md); font-size: 0.85rem; color: var(--color-texto);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                El usuario podrá iniciar sesión con su correo y la contraseña que establezcas aquí.
            </div>
        </div>
        <div class="card-body border-t border-borde flex items-center justify-end gap-3" style="background: var(--color-fondo); padding: 1.25rem;">
            <a href="lista.php" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primario" style="min-width: 180px; justify-content: center;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px; margin-right: 6px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                </svg>
                Crear Usuario
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
