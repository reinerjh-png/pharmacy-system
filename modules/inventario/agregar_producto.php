<?php
// modules/inventario/agregar_producto.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('inventario');
$pdo = conectar();

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
    
    // Lote inicial
    $proveedor_id = (int)$_POST['proveedor_id'];
    $lote = trim($_POST['lote']);
    $fecha_vencimiento = $_POST['fecha_vencimiento'];
    $stock = (int)$_POST['stock_actual'];
    $minimo = (int)$_POST['stock_minimo'];
    $compra = (float)$_POST['precio_compra'];
    $venta = (float)$_POST['precio_venta'];

    if (empty($nombre) || empty($categoria_id) || empty($laboratorio_id)) {
        $error = "Nombre, categoría y laboratorio son obligatorios.";
    } else {
        try {
            $pdo->beginTransaction();
            
                        // 1. Insertar Producto con farmacia_id del tenant activo
            $stmtP = $pdo->prepare("INSERT INTO productos (nombre, nombre_generico, codigo_barras, categoria_id, laboratorio_id, unidad_medida, requiere_receta, farmacia_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtP->execute([$nombre, $generico, $codigo ?: null, $categoria_id, $laboratorio_id, $unidad, $receta, farmacia_id()]);
            $producto_id = $pdo->lastInsertId();
            
            // 2. Insertar Lote Inicial si se especificó cantidad > 0
            if ($stock > 0) {
                $stmtI = $pdo->prepare("INSERT INTO inventario (producto_id, proveedor_id, lote, fecha_vencimiento, stock_actual, stock_minimo, precio_compra, precio_venta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtI->execute([$producto_id, $proveedor_id ?: null, $lote, $fecha_vencimiento ?: null, $stock, $minimo, $compra, $venta]);
            }
            
            $pdo->commit();
            header('Location: lista_productos.php?msg=creado');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = "El código de barras ya existe en el sistema.";
            } else {
                $error = "Error al guardar: " . $e->getMessage();
            }
        }
    }
}

$categorias  = $pdo->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();
$laboratorios = $pdo->query("SELECT * FROM laboratorios ORDER BY nombre")->fetchAll();
// Proveedores solo de esta farmacia
$stmt_prov = $pdo->prepare("SELECT * FROM proveedores WHERE activo=1 AND farmacia_id = ? ORDER BY nombre");
$stmt_prov->execute([farmacia_id()]);
$proveedores = $stmt_prov->fetchAll();

$pagina_titulo = 'Agregar Producto';
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
        <h1 class="text-2xl m-0">Agregar Nuevo Producto</h1>
    </div>

    <?php if ($error): ?>
        <div class="alerta alerta-peligro animate-shake">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="card">
            <div class="card-header"><h2 class="card-titulo">Datos Generales</h2></div>
            <div class="card-body grid-2-cols">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Nombre Comercial <span class="text-peligro">*</span></label>
                    <input type="text" name="nombre" class="form-control" required placeholder="Ej. Paracetamol 500mg" autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre Genérico</label>
                    <input type="text" name="nombre_generico" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Código de Barras</label>
                    <div class="input-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" />
                        </svg>
                        <input type="text" name="codigo_barras" class="form-control" style="font-variant-numeric: tabular-nums;">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Categoría <span class="text-peligro">*</span></label>
                    <select name="categoria_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Laboratorio <span class="text-peligro">*</span></label>
                    <select name="laboratorio_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($laboratorios as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Unidad de Medida</label>
                    <select name="unidad_medida" class="form-control">
                        <option value="unidad">Unidad</option>
                        <option value="caja">Caja</option>
                        <option value="blíster">Blíster</option>
                        <option value="frasco">Frasco</option>
                        <option value="tubo">Tubo</option>
                    </select>
                </div>
                <div class="form-group flex" style="align-items: center; margin-top: 28px;">
                    <label class="form-check">
                        <input type="checkbox" name="requiere_receta" value="1">
                        <span class="font-medium text-peligro">Requiere receta médica para la venta</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-titulo">Stock Inicial (Primer Lote)</h2></div>
            <div class="card-body grid-2-cols">
                <div class="form-group" style="grid-column: span 2;">
                    <div class="alerta alerta-info m-0" style="padding: 10px 16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        Si el producto ingresa sin stock inicial, deja la cantidad en 0 y podrás agregar lotes después.
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Proveedor Inicial</label>
                    <select name="proveedor_id" class="form-control">
                        <option value="">Seleccione...</option>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Número de Lote</label>
                    <input type="text" name="lote" class="form-control" placeholder="Ej. L-2049">
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha Vencimiento</label>
                    <input type="date" name="fecha_vencimiento" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Inicial</label>
                    <input type="number" name="stock_actual" class="form-control" value="0" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Mínimo (Alerta)</label>
                    <input type="number" name="stock_minimo" class="form-control" value="5" min="0">
                </div>
                <div></div>
                <div class="form-group">
                    <label class="form-label">Precio Compra (S/)</label>
                    <div class="input-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <input type="number" step="0.01" name="precio_compra" class="form-control" value="0.00" style="font-variant-numeric: tabular-nums;">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Precio Venta (S/)</label>
                    <div class="input-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <input type="number" step="0.01" name="precio_venta" class="form-control" value="0.00" style="font-variant-numeric: tabular-nums; color: var(--verde-700); font-weight: 600;">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-8">
            <button type="button" class="btn btn-ghost" onclick="window.history.back()">Cancelar</button>
            <button type="submit" class="btn btn-primario btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                </svg>
                Guardar Producto
            </button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
