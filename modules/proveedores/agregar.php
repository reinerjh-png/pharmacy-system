<?php
// modules/proveedores/agregar.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('proveedores');
$pdo = conectar();

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $ruc = trim($_POST['ruc']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);

    if (empty($nombre)) {
        $error = "La Razón Social (nombre) es obligatoria.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO proveedores (nombre, ruc, telefono, email, direccion) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $ruc ?: null, $telefono ?: null, $email ?: null, $direccion ?: null]);
            header('Location: lista.php');
            exit;
        } catch (PDOException $e) {
            $error = "Error al guardar el proveedor: " . $e->getMessage();
        }
    }
}

$pagina_titulo = 'Agregar Proveedor';
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
                <h1 style="margin: 0;">Nuevo Proveedor</h1>
            </div>
            <p class="page-subtitle" style="margin-left: 45px;">Registra un nuevo laboratorio o distribuidor en el catálogo.</p>
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
        <div class="card-header border-b border-borde bg-card">
            <h2 class="card-titulo">Información de la Empresa</h2>
        </div>
        <div class="card-body">
            <div class="form-group mb-6">
                <label class="form-label" style="font-weight: 500; margin-bottom: 0.5rem; display: block;">Razón Social / Nombre <span class="text-peligro">*</span></label>
                <input type="text" name="nombre" class="form-control" required placeholder="Ej. Droguería del Sur S.A.C." style="font-size: 1.05rem; padding: 0.75rem;">
            </div>
            
            <div class="grid-2-cols gap-6 mb-6">
                <div class="form-group">
                    <label class="form-label text-secundario text-sm" style="margin-bottom: 0.25rem; display: block;">RUC</label>
                    <input type="text" name="ruc" class="form-control" placeholder="11 dígitos">
                </div>
                <div class="form-group">
                    <label class="form-label text-secundario text-sm" style="margin-bottom: 0.25rem; display: block;">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" placeholder="(01) 555-1234">
                </div>
            </div>
            
            <div class="form-group mb-6">
                <label class="form-label text-secundario text-sm" style="margin-bottom: 0.25rem; display: block;">Correo Electrónico</label>
                <div class="flex items-center">
                    <span style="background: var(--color-fondo); border: 1px solid var(--color-borde); border-right: none; border-radius: var(--radio-md) 0 0 var(--radio-md); padding: 0.6rem 0.75rem; color: var(--texto-secundario);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    <input type="email" name="email" class="form-control" style="border-radius: 0 var(--radio-md) var(--radio-md) 0;" placeholder="ventas@proveedor.com">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label text-secundario text-sm" style="margin-bottom: 0.25rem; display: block;">Dirección Fiscal</label>
                <input type="text" name="direccion" class="form-control" placeholder="Av. Principal 123, Distrito, Ciudad">
            </div>
        </div>
        <div class="card-body bg-fondo border-t border-borde flex items-center justify-end gap-3" style="padding: 1.25rem;">
            <a href="lista.php" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primario" style="min-width: 180px; justify-content: center;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px; margin-right: 6px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Guardar Proveedor
            </button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
