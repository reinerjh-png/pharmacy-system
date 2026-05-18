<?php
// modules/usuarios/lista.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('usuarios');
$pdo = conectar();

$exito = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desactivar_id'])) {
    $id = (int)$_POST['desactivar_id'];
    if ($id === (int)$_SESSION['usuario_id']) {
        $error = "No puede desactivar su propio usuario.";
    } else {
        $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?")->execute([$id]);
        $exito = "Usuario desactivado.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activar_id'])) {
    $id = (int)$_POST['activar_id'];
    $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?")->execute([$id]);
    $exito = "Usuario activado.";
}

$usuarios = $pdo->query("
    SELECT u.*, r.nombre as rol 
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    ORDER BY u.nombre ASC
")->fetchAll();

$pagina_titulo = 'Usuarios';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1200px;">
    
    <div class="page-header">
        <div>
            <h1>Gestión de Usuarios</h1>
            <p class="page-subtitle">Administra los accesos, roles y el estado del personal en el sistema.</p>
        </div>
        <div class="page-header-actions">
            <a href="agregar.php" class="btn btn-primario">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo Usuario
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alerta alerta-peligro animate-shake">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($exito): ?>
        <div class="alerta alerta-exito">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <?= htmlspecialchars($exito) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Nombre y Correo</th>
                        <th>Rol Asignado</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <svg class="empty-state-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                    <div class="empty-state-titulo">No hay usuarios</div>
                                    <div class="empty-state-msg">Registra al personal para darles acceso al sistema.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div style="width: 40px; height: 40px; border-radius: var(--radio-full); background-color: var(--color-primario-claro); color: var(--color-primario); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.1rem; flex-shrink: 0;">
                                            <?= strtoupper(substr($u['nombre'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-medium"><?= htmlspecialchars($u['nombre']) ?></div>
                                            <div class="text-xs text-secundario mt-1"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="background: var(--bg-card); border: 1px solid var(--color-borde); color: var(--texto-secundario);">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px; margin-right: 4px; display: inline-block; vertical-align: text-bottom;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                        <?= htmlspecialchars($u['rol']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['activo']): ?>
                                        <span class="badge badge-exito">
                                            <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background-color:currentColor; margin-right:4px;"></span>
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-peligro">
                                            <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background-color:currentColor; margin-right:4px;"></span>
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="flex" style="justify-content: flex-end; gap: 8px;">
                                        <a href="editar.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-ghost" title="Editar Usuario">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                        <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                            <?php if ($u['activo']): ?>
                                                <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('¿Desactivar este usuario? Ya no podrá acceder al sistema.');">
                                                    <input type="hidden" name="desactivar_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--color-peligro);" title="Desactivar">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="" method="POST" style="margin: 0;">
                                                    <input type="hidden" name="activar_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--verde-600);" title="Activar">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
