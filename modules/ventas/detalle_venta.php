<?php
// modules/ventas/detalle_venta.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('ventas');
$pdo = conectar();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: historial.php');
    exit;
}

$stmtV = $pdo->prepare("
    SELECT v.*, u.nombre as cajero
    FROM ventas v
    JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.id = ?
");
$stmtV->execute([$id]);
$venta = $stmtV->fetch();

if (!$venta) {
    header('Location: historial.php');
    exit;
}

$stmtD = $pdo->prepare("
    SELECT d.*, p.nombre, p.codigo_barras, i.lote
    FROM detalle_ventas d
    JOIN productos p ON d.producto_id = p.id
    LEFT JOIN inventario i ON d.inventario_id = i.id
    WHERE d.venta_id = ?
");
$stmtD->execute([$id]);
$detalles = $stmtD->fetchAll();

$pagina_titulo = 'Detalle de Venta #' . $id;
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 900px;">
    
    <div class="mb-6 flex justify-between items-start">
        <div>
            <a href="historial.php" class="btn btn-ghost" style="padding-left: 0; margin-bottom: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al historial
            </a>
            <h1 class="text-2xl m-0 flex items-center gap-3">
                Ticket #<?= str_pad($venta['id'], 6, '0', STR_PAD_LEFT) ?>
                <?php if ($venta['estado'] === 'anulada'): ?>
                    <span class="badge badge-peligro text-sm">Venta Anulada</span>
                <?php endif; ?>
            </h1>
        </div>
        <div>
            <button class="btn btn-secundario" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.728 6.75H17.27m-10.542 0A2.25 2.25 0 004.5 9v4.5a2.25 2.25 0 002.25 2.25h.5v4.5h9.5v-4.5h.5a2.25 2.25 0 002.25-2.25V9a2.25 2.25 0 00-2.25-2.25h-.5m-10.542 0V4.5A2.25 2.25 0 019 2.25h6a2.25 2.25 0 012.25 2.25v4.5" />
                </svg>
                Imprimir Comprobante
            </button>
        </div>
    </div>

    <!-- Print styling -->
    <style>
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .sidebar, .topbar, .btn, .page-header-actions, .mb-6 a { display: none !important; }
            .container { max-width: 100% !important; margin: 0 !important; padding: 0 !important; box-shadow: none !important; }
            .card { border: none !important; box-shadow: none !important; margin-bottom: 0 !important; }
        }
    </style>

    <div class="card mb-6">
        <div class="card-body">
            <div class="grid-4-cols gap-6" style="margin-bottom: 8px;">
                <div>
                    <div class="text-sm font-medium text-secundario mb-1">Fecha y Hora</div>
                    <div class="font-semibold text-lg"><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></div>
                </div>
                <div>
                    <div class="text-sm font-medium text-secundario mb-1">Cajero</div>
                    <div class="font-semibold text-lg">
                        <div class="flex items-center gap-2">
                            <div style="width: 24px; height: 24px; border-radius: 50%; background-color: var(--color-primario-claro); color: var(--color-primario); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">
                                <?= strtoupper(substr($venta['cajero'], 0, 1)) ?>
                            </div>
                            <?= htmlspecialchars($venta['cajero']) ?>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="text-sm font-medium text-secundario mb-1">Método de Pago</div>
                    <div class="font-semibold text-lg" style="text-transform: capitalize;">
                        <?= htmlspecialchars($venta['tipo_pago']) ?>
                    </div>
                </div>
                <div>
                    <div class="text-sm font-medium text-secundario mb-1">Estado de Pago</div>
                    <div>
                        <?php if ($venta['estado'] === 'completada'): ?>
                            <span class="badge badge-exito">Procesado</span>
                        <?php else: ?>
                            <span class="badge badge-peligro">Reembolsado</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-b border-borde">
            <h2 class="card-titulo">Productos Adquiridos</h2>
        </div>
        <div class="tabla-contenedor" style="border: none; border-radius: 0;">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Lote</th>
                        <th style="text-align: right;">Precio Unit.</th>
                        <th style="text-align: center;">Cant.</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $d): ?>
                        <tr>
                            <td>
                                <div class="font-medium"><?= htmlspecialchars($d['nombre']) ?></div>
                                <div class="text-xs text-secundario mt-1" style="font-variant-numeric: tabular-nums;"><?= htmlspecialchars($d['codigo_barras'] ?? '') ?></div>
                            </td>
                            <td class="text-sm text-secundario" style="font-variant-numeric: tabular-nums;"><?= htmlspecialchars($d['lote'] ?? 'S/L') ?></td>
                            <td style="text-align: right; font-variant-numeric: tabular-nums;">S/ <?= number_format($d['precio_unitario'], 2) ?></td>
                            <td style="text-align: center; font-weight: 600;"><?= $d['cantidad'] ?></td>
                            <td style="text-align: right;" class="font-semibold" style="font-variant-numeric: tabular-nums;">S/ <?= number_format($d['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-body bg-fondo" style="border-top: 1px solid var(--color-borde);">
            <div style="display: flex; justify-content: flex-end;">
                <div style="width: 300px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 1.1rem; color: var(--texto-secundario);">
                        <span>Subtotal</span>
                        <span class="font-medium" style="font-variant-numeric: tabular-nums;">S/ <?= number_format($venta['subtotal'], 2) ?></span>
                    </div>
                    <?php if (isset($venta['descuento']) && $venta['descuento'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 1.1rem;">
                        <span class="text-secundario">Descuento</span>
                        <span class="text-peligro font-medium" style="font-variant-numeric: tabular-nums;">- S/ <?= number_format($venta['descuento'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <hr style="border: 0; border-top: 1px dashed var(--color-borde); margin: 16px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px;">
                        <span class="font-medium text-lg">Total</span>
                        <span class="font-bold text-primario" style="font-size: 2rem; line-height: 1; font-variant-numeric: tabular-nums;">S/ <?= number_format($venta['total'], 2) ?></span>
                    </div>
                    
                    <?php if (isset($venta['monto_efectivo']) && $venta['monto_efectivo'] > 0): ?>
                    <div style="background-color: white; padding: 16px; border-radius: var(--radio-md); border: 1px solid var(--color-borde);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;" class="text-sm">
                            <span class="text-secundario font-medium">Efectivo recibido</span>
                            <span style="font-variant-numeric: tabular-nums;">S/ <?= number_format($venta['monto_efectivo'], 2) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;" class="text-sm">
                            <span class="text-secundario font-medium">Vuelto entregado</span>
                            <span class="text-exito font-bold" style="font-variant-numeric: tabular-nums;">S/ <?= number_format($venta['monto_efectivo'] - $venta['total'], 2) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
