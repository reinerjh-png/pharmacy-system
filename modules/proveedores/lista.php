<?php
// modules/proveedores/lista.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('proveedores');
$pdo = conectar();

$exito = '';
$error = '';

// Desactivar proveedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desactivar_id'])) {
    $id = (int)$_POST['desactivar_id'];
    try {
        $pdo->prepare("UPDATE proveedores SET activo = 0 WHERE id = ? AND farmacia_id = ?")->execute([$id, farmacia_id()]);
        $exito = "Proveedor desactivado correctamente.";
    } catch (PDOException $e) {
        $error = "Error al desactivar proveedor: " . $e->getMessage();
    }
}

// Activar proveedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activar_id'])) {
    $id = (int)$_POST['activar_id'];
    try {
        $pdo->prepare("UPDATE proveedores SET activo = 1 WHERE id = ? AND farmacia_id = ?")->execute([$id, farmacia_id()]);
        $exito = "Proveedor activado correctamente.";
    } catch (PDOException $e) {
        $error = "Error al activar proveedor: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM proveedores WHERE farmacia_id = ? ORDER BY nombre ASC");
$stmt->execute([farmacia_id()]);
$proveedores = $stmt->fetchAll();

$pagina_titulo = 'Proveedores';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1200px;">
    
    <div class="page-header">
        <div>
            <h1>Gestión de Proveedores</h1>
            <p class="page-subtitle">Administra el catálogo de laboratorios y distribuidores.</p>
        </div>
        <div class="page-header-actions">
            <a href="registro_compra.php" class="btn btn-secundario">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                Ingresar Factura
            </a>
            <a href="agregar.php" class="btn btn-primario">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo Proveedor
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
        <div class="tabla-contenedor" style="border: none; border-radius: var(--radio-lg);">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Razón Social / RUC</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proveedores)): ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <svg class="empty-state-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                    </svg>
                                    <div class="empty-state-titulo">No hay proveedores</div>
                                    <div class="empty-state-msg">Registra distribuidores para poder ingresar lotes de medicamentos.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($proveedores as $p): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div style="width: 40px; height: 40px; border-radius: var(--radio-md); background-color: var(--color-primario-claro); color: var(--color-primario); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.1rem; flex-shrink: 0;">
                                            <?= strtoupper(substr($p['nombre'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-medium"><?= htmlspecialchars($p['nombre']) ?></div>
                                            <div class="text-xs text-secundario mt-1" style="font-variant-numeric: tabular-nums;">RUC: <?= htmlspecialchars($p['ruc'] ?? 'N/A') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm font-medium" style="font-variant-numeric: tabular-nums; margin-bottom: 2px;">
                                        <?= htmlspecialchars($p['telefono'] ?? '-') ?>
                                    </div>
                                    <div class="text-xs text-secundario">
                                        <?= htmlspecialchars($p['email'] ?? '-') ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($p['activo']): ?>
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
                                        <?php if ($p['activo']): ?>
                                            <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('¿Desactivar proveedor?');">
                                                <input type="hidden" name="desactivar_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--color-peligro);" title="Desactivar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="" method="POST" style="margin: 0;">
                                                <input type="hidden" name="activar_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--verde-600);" title="Activar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            </form>
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
