<?php
// modules/inventario/editar_producto.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('inventario');
$pdo = conectar();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: lista_productos.php');
    exit;
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $generico = trim($_POST['nombre_generico']);
    $codigo = trim($_POST['codigo_barras']);
    $categoria_id = (int)$_POST['categoria_id'];
    $laboratorio_id = (int)$_POST['laboratorio_id'];
    $unidad = trim($_POST['unidad_medida']);
    $receta = isset($_POST['requiere_receta']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (empty($nombre) || empty($categoria_id) || empty($laboratorio_id)) {
        $error = "Nombre, categoría y laboratorio son obligatorios.";
    } else {
        try {
            $stmtP = $pdo->prepare("UPDATE productos SET nombre=?, nombre_generico=?, codigo_barras=?, categoria_id=?, laboratorio_id=?, unidad_medida=?, requiere_receta=?, activo=? WHERE id=?");
            $stmtP->execute([$nombre, $generico, $codigo ?: null, $categoria_id, $laboratorio_id, $unidad, $receta, $activo, $id]);
            $exito = "Producto actualizado correctamente.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "El código de barras ya existe en el sistema.";
            } else {
                $error = "Error al actualizar: " . $e->getMessage();
            }
        }
    }
}

// Cargar producto
$stmtProd = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmtProd->execute([$id]);
$producto = $stmtProd->fetch();

if (!$producto) {
    header('Location: lista_productos.php');
    exit;
}

$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();
$laboratorios = $pdo->query("SELECT * FROM laboratorios ORDER BY nombre")->fetchAll();

$pagina_titulo = 'Editar Producto';
include __DIR__ . '/../../views/layout/header.php';
?>
<div class="container" style="max-width: 800px;">
    
    <div class="mb-6">
        <a href="lista_productos.php" class="btn btn-ghost" style="padding-left: 0; margin-bottom: 8px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Volver al catálogo
        </a>
        <h1 class="text-2xl m-0">Editar Producto: <?= htmlspecialchars($producto['nombre']) ?></h1>
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

    <form action="" method="POST">
        <div class="card">
            <div class="card-header"><h2 class="card-titulo">Datos Generales</h2></div>
            <div class="card-body grid-2-cols">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Nombre Comercial <span class="text-peligro">*</span></label>
                    <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($producto['nombre']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre Genérico</label>
                    <input type="text" name="nombre_generico" class="form-control" value="<?= htmlspecialchars($producto['nombre_generico']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Código de Barras</label>
                    <div class="input-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                        </svg>
                        <input type="text" name="codigo_barras" class="form-control" style="font-variant-numeric: tabular-nums;" value="<?= htmlspecialchars($producto['codigo_barras']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Categoría <span class="text-peligro">*</span></label>
                    <select name="categoria_id" class="form-control" required>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id'] == $producto['categoria_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Laboratorio <span class="text-peligro">*</span></label>
                    <select name="laboratorio_id" class="form-control" required>
                        <?php foreach ($laboratorios as $l): ?>
                            <option value="<?= $l['id'] ?>" <?= $l['id'] == $producto['laboratorio_id'] ? 'selected' : '' ?>><?= htmlspecialchars($l['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Unidad de Medida</label>
                    <select name="unidad_medida" class="form-control">
                        <?php 
                        $unidades = ['unidad', 'caja', 'blíster', 'frasco', 'tubo'];
                        foreach ($unidades as $u): ?>
                            <option value="<?= $u ?>" <?= $producto['unidad_medida'] == $u ? 'selected' : '' ?>><?= ucfirst($u) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group flex" style="align-items: center; margin-top: 28px; gap: 24px;">
                    <label class="form-check">
                        <input type="checkbox" name="requiere_receta" value="1" <?= $producto['requiere_receta'] ? 'checked' : '' ?>>
                        <span class="font-medium text-peligro">Requiere receta</span>
                    </label>
                    <label class="form-check">
                        <input type="checkbox" name="activo" value="1" <?= $producto['activo'] ? 'checked' : '' ?>>
                        <span class="font-medium text-exito">Activo en Catálogo</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-8">
            <button type="button" class="btn btn-ghost" onclick="window.history.back()">Cancelar</button>
            <button type="submit" class="btn btn-primario btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Actualizar Datos
            </button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
