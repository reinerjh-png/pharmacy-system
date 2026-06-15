<?php
// modules/proveedores/registro_compra.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('compras');
$pdo = conectar();

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proveedor_id = (int)$_POST['proveedor_id'];
    $factura = trim($_POST['numero_factura']);
    $observaciones = trim($_POST['observaciones']);
    
    $p_ids = $_POST['producto_id'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $p_compras = $_POST['precio_compra'] ?? [];
    $p_ventas = $_POST['precio_venta'] ?? [];
    $lotes = $_POST['lote'] ?? [];
    $vencimientos = $_POST['fecha_vencimiento'] ?? [];
    
    if (!$proveedor_id || empty($p_ids)) {
        $error = "Debe seleccionar un proveedor y agregar al menos un producto.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Calcular total de la compra
            $total_compra = 0;
            for ($i = 0; $i < count($p_ids); $i++) {
                $total_compra += ((int)$cantidades[$i] * (float)$p_compras[$i]);
            }
            
            // Insertar compra con farmacia_id
            $stmtC = $pdo->prepare("INSERT INTO compras (proveedor_id, usuario_id, farmacia_id, numero_factura, total, observaciones) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtC->execute([$proveedor_id, $_SESSION['usuario_id'], farmacia_id(), $factura, $total_compra, $observaciones]);
            $compra_id = $pdo->lastInsertId();
            
            // Iterar sobre productos
            for ($i = 0; $i < count($p_ids); $i++) {
                $pid = (int)$p_ids[$i];
                $cant = (int)$cantidades[$i];
                $pc = (float)$p_compras[$i];
                $pv = (float)$p_ventas[$i];
                $lote = trim($lotes[$i]);
                $venc = !empty($vencimientos[$i]) ? $vencimientos[$i] : null;
                
                if ($cant <= 0) continue;
                
                // Obtener stock_minimo actual del producto (opcional, o 5 por defecto)
                $stock_minimo = 5; 
                
                // Buscar si este mismo lote ya existe para el producto
                $stmtLote = $pdo->prepare("SELECT id FROM inventario WHERE producto_id = ? AND lote = ? AND (fecha_vencimiento = ? OR fecha_vencimiento IS NULL)");
                $stmtLote->execute([$pid, $lote, $venc]);
                $inv = $stmtLote->fetch();
                
                $inv_id = null;
                if ($inv) {
                    $inv_id = $inv['id'];
                    $pdo->prepare("UPDATE inventario SET stock_actual = stock_actual + ?, precio_compra = ?, precio_venta = ?, proveedor_id = ? WHERE id = ?")
                        ->execute([$cant, $pc, $pv, $proveedor_id, $inv_id]);
                } else {
                    $pdo->prepare("INSERT INTO inventario (producto_id, lote, fecha_vencimiento, stock_actual, stock_minimo, precio_compra, precio_venta, proveedor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$pid, $lote, $venc, $cant, $stock_minimo, $pc, $pv, $proveedor_id]);
                    $inv_id = $pdo->lastInsertId();
                }
                
                // Detalle de compra
                $pdo->prepare("INSERT INTO detalle_compras (compra_id, producto_id, inventario_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$compra_id, $pid, $inv_id, $cant, $pc, $cant * $pc]);
            }
            
            $pdo->commit();
            $exito = "Compra registrada con éxito.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al registrar la compra: " . $e->getMessage();
        }
    }
}

$stmtProv = $pdo->prepare("SELECT id, nombre, ruc FROM proveedores WHERE activo = 1 AND farmacia_id = ? ORDER BY nombre");
$stmtProv->execute([farmacia_id()]);
$proveedores = $stmtProv->fetchAll();

$stmtProd = $pdo->prepare("SELECT id, nombre FROM productos WHERE activo = 1 AND farmacia_id = ? ORDER BY nombre");
$stmtProd->execute([farmacia_id()]);
$productos = $stmtProd->fetchAll();

$pagina_titulo = 'Registrar Compra';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1100px;">
    
    <div class="page-header">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="lista.php" class="btn btn-sm btn-secundario" style="padding: 0.25rem 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <h1 style="margin: 0;">Ingresar Factura de Compra</h1>
            </div>
            <p class="page-subtitle" style="margin-left: 45px;">Registra el ingreso de mercadería al inventario y actualiza los precios de venta.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alerta alerta-peligro animate-shake mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($exito): ?>
        <div class="alerta alerta-exito mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <?= htmlspecialchars($exito) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="card mb-6" style="overflow: hidden;">
            <div class="card-header border-b border-borde">
                <h2 class="card-titulo">Datos de la Factura</h2>
            </div>
            <div class="card-body grid-2-cols gap-6">
                <div class="form-group">
                    <label class="form-label" style="font-weight: 500; display: block; margin-bottom: 0.25rem;">Proveedor <span class="text-peligro">*</span></label>
                    <select name="proveedor_id" class="form-control" required>
                        <option value="">-- Seleccionar proveedor --</option>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> <?= $p['ruc'] ? ' — RUC: '.$p['ruc'] : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label text-secundario text-sm" style="display: block; margin-bottom: 0.25rem;">N° Factura / Guía de Remisión</label>
                    <input type="text" name="numero_factura" class="form-control" placeholder="E001-0001234">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label text-secundario text-sm" style="display: block; margin-bottom: 0.25rem;">Observaciones</label>
                    <input type="text" name="observaciones" class="form-control" placeholder="Notas adicionales sobre esta compra...">
                </div>
            </div>
        </div>

        <div class="card mb-6" style="overflow: hidden;">
            <div class="card-header border-b border-borde" style="display: flex; justify-content: space-between; align-items: center;">
                <h2 class="card-titulo">Detalle de Productos</h2>
                <button type="button" class="btn btn-sm btn-primario" onclick="agregarFila()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Añadir Producto
                </button>
            </div>
            <div class="tabla-contenedor" style="border: none; overflow: visible;">
                <table class="tabla" id="tablaProductos">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="width:90px;">Cant.</th>
                            <th style="width:120px;">P. Compra (S/)</th>
                            <th style="width:120px;">P. Venta (S/)</th>
                            <th style="width:110px;">Lote</th>
                            <th style="width:140px;">Vencimiento</th>
                            <th style="width:44px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Filas dinámicas -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="flex items-center justify-end gap-3 mb-10">
            <a href="lista.php" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primario btn-lg" style="min-width: 220px; justify-content: center;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px; margin-right: 6px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Procesar Ingreso de Stock
            </button>
        </div>
    </form>
</div>

<script>
const productosOpciones = `
    <option value="">Seleccione...</option>
    <?php foreach ($productos as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?></option>
    <?php endforeach; ?>
`;

function agregarFila() {
    const tbody = document.querySelector('#tablaProductos tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select name="producto_id[]" class="form-control" required style="padding:6px; min-width: 200px;">${productosOpciones}</select></td>
        <td><input type="number" name="cantidad[]" class="form-control" min="1" value="1" required style="padding:6px;"></td>
        <td><input type="number" step="0.01" name="precio_compra[]" class="form-control" value="0.00" required style="padding:6px;"></td>
        <td><input type="number" step="0.01" name="precio_venta[]" class="form-control" value="0.00" required style="padding:6px;"></td>
        <td><input type="text" name="lote[]" class="form-control" placeholder="Ej. LT-001" style="padding:6px;"></td>
        <td><input type="date" name="fecha_vencimiento[]" class="form-control" style="padding:6px;"></td>
        <td>
            <button type="button" class="btn btn-sm btn-ghost" style="color: var(--color-peligro); padding: 6px;" onclick="this.closest('tr').remove()" title="Quitar fila">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

// Agregar la primera fila por defecto
document.addEventListener('DOMContentLoaded', agregarFila);
</script>

<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
