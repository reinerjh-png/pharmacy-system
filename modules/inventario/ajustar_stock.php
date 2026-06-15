<?php
// modules/inventario/ajustar_stock.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('inventario');
$pdo = conectar();

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inventario_id = (int)$_POST['inventario_id'];
    $tipo = $_POST['tipo'];
    $cantidad = (int)$_POST['cantidad'];
    $motivo = trim($_POST['motivo']);
    $usuario_id = $_SESSION['usuario_id'];

    if (!$inventario_id || !$tipo || $cantidad <= 0 || empty($motivo)) {
        $error = "Todos los campos son obligatorios y la cantidad debe ser mayor a 0.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Bloquear fila de inventario para update
            $stmtInv = $pdo->prepare("SELECT stock_actual FROM inventario WHERE id = ? FOR UPDATE");
            $stmtInv->execute([$inventario_id]);
            $inv = $stmtInv->fetch();
            
            if (!$inv) {
                throw new Exception("El lote no existe.");
            }

            $nuevo_stock = $inv['stock_actual'];
            if ($tipo === 'entrada') {
                $nuevo_stock += $cantidad;
            } elseif ($tipo === 'salida') {
                if ($cantidad > $nuevo_stock) {
                    throw new Exception("No hay suficiente stock para la salida.");
                }
                $nuevo_stock -= $cantidad;
            } elseif ($tipo === 'correccion') {
                // Cantidad es el nuevo stock absoluto
                $cantidad_diff = abs($cantidad - $nuevo_stock);
                $nuevo_stock = $cantidad;
                $cantidad = $cantidad_diff; // Guardar la diferencia en el log
            }

            // Actualizar stock
            $stmtUpd = $pdo->prepare("UPDATE inventario SET stock_actual = ? WHERE id = ?");
            $stmtUpd->execute([$nuevo_stock, $inventario_id]);

            // Registrar log con farmacia_id
            $stmtLog = $pdo->prepare("INSERT INTO ajustes_stock (inventario_id, usuario_id, farmacia_id, tipo, cantidad, motivo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtLog->execute([$inventario_id, $usuario_id, farmacia_id(), $tipo, $cantidad, $motivo]);

            $pdo->commit();
            $exito = "Stock ajustado correctamente. Nuevo stock: $nuevo_stock";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Obtener todos los lotes disponibles para el selector, solo del tenant activo
$lotes = $pdo->prepare("
    SELECT i.id, p.nombre, i.lote, i.stock_actual, i.fecha_vencimiento 
    FROM inventario i
    JOIN productos p ON i.producto_id = p.id
    WHERE p.activo = 1 AND p.farmacia_id = ?
    ORDER BY p.nombre, i.fecha_vencimiento
");
$lotes->execute([farmacia_id()]);
$lotes = $lotes->fetchAll();

$pagina_titulo = 'Ajustar Stock';
include __DIR__ . '/../../views/layout/header.php';
?>
<div class="container" style="max-width: 640px;">
    
    <div class="mb-6">
        <a href="lista_productos.php" class="btn btn-ghost" style="padding-left: 0; margin-bottom: 8px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Volver al catálogo
        </a>
        <h1 class="text-2xl m-0">Ajuste de Stock</h1>
        <p class="text-secundario mt-1">Registra pérdidas, devoluciones o correcciones manuales del inventario.</p>
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
            <div class="card-header"><h2 class="card-titulo">Detalles del Ajuste</h2></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Producto y Lote <span class="text-peligro">*</span></label>
                    <select name="inventario_id" class="form-control" required autofocus>
                        <option value="">Seleccione el lote...</option>
                        <?php foreach ($lotes as $l): ?>
                            <option value="<?= $l['id'] ?>">
                                <?= htmlspecialchars($l['nombre']) ?> 
                                (Lote: <?= htmlspecialchars($l['lote'] ?: 'S/L') ?> | Stock actual: <?= $l['stock_actual'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipo de Ajuste <span class="text-peligro">*</span></label>
                    <select name="tipo" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <option value="entrada">Entrada (Devolución o sobrante)</option>
                        <option value="salida">Salida (Pérdida, daño, vencido)</option>
                        <option value="correccion">Corrección (Fijar stock exacto)</option>
                    </select>
                    <small class="text-secundario text-xs" style="display:block; margin-top:8px;">
                        Para "Corrección", ingrese el stock real contado en el campo Cantidad.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Cantidad <span class="text-peligro">*</span></label>
                    <input type="number" name="cantidad" class="form-control" min="1" required style="font-variant-numeric: tabular-nums;">
                </div>

                <div class="form-group">
                    <label class="form-label">Motivo <span class="text-peligro">*</span></label>
                    <textarea name="motivo" class="form-control" rows="3" required placeholder="Explique brevemente la razón del ajuste..."></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-8">
            <button type="button" class="btn btn-ghost" onclick="window.history.back()">Cancelar</button>
            <button type="submit" class="btn btn-primario btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Registrar Ajuste
            </button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
