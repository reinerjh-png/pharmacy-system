<?php
// modules/reportes/stock_critico.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/branding.php';

verificar_permiso('reportes');
$pdo = conectar();
$_sc_brand = branding();

// Obtener productos con stock crítico (agrupado por producto)
$sql = "
    SELECT p.nombre, p.codigo_barras, c.nombre as categoria,
           COALESCE(SUM(i.stock_actual), 0) as stock_total,
           MAX(i.stock_minimo) as stock_minimo,
           prov.nombre as proveedor_habitual
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN inventario i ON p.id = i.producto_id
    LEFT JOIN proveedores prov ON i.proveedor_id = prov.id
    WHERE p.activo = 1
    GROUP BY p.id
    HAVING stock_total <= stock_minimo
    ORDER BY stock_total ASC, p.nombre ASC
";
$productos = $pdo->query($sql)->fetchAll();

$imprimir = isset($_GET['print']);

if ($imprimir):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Stock Crítico - <?= htmlspecialchars($_sc_brand['farmacia_nombre']) ?></title>
    <style>
        body { 
            font-family: 'Inter', system-ui, sans-serif; 
            font-size: 12px; 
            color: #1e293b; 
            margin: 0;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }
        h1 { font-size: 20px; margin: 0 0 5px 0; color: #0f172a; }
        .meta { color: #64748b; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
        .stock-critico { color: #ef4444; font-weight: bold; }
        .stock-bajo { color: #f59e0b; font-weight: bold; }
        .footer { margin-top: 40px; font-size: 10px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px; }
        .btn-print {
            padding: 8px 16px;
            background: #0f172a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            position: fixed;
            top: 20px;
            right: 20px;
            font-family: inherit;
        }
        @media print {
            body { padding: 0; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <button class="btn-print" onclick="window.print()">Imprimir Documento</button>
    
    <div class="header">
        <div>
            <h1>Reporte de Stock Crítico</h1>
            <div class="meta">Generado el <?= date('d/m/Y a las H:i') ?></div>
        </div>
        <div style="text-align: right;">
            <strong><?= htmlspecialchars($_sc_brand['farmacia_nombre']) ?></strong><br>
            <?php if (!empty($_sc_brand['farmacia_ruc'])): ?>
                <span class="meta">RUC: <?= htmlspecialchars($_sc_brand['farmacia_ruc']) ?></span><br>
            <?php endif; ?>
            <?php if (!empty($_sc_brand['farmacia_direccion'])): ?>
                <span class="meta"><?= htmlspecialchars($_sc_brand['farmacia_direccion']) ?></span><br>
            <?php endif; ?>
            <?php if (!empty($_sc_brand['farmacia_telefono'])): ?>
                <span class="meta">Tel: <?= htmlspecialchars($_sc_brand['farmacia_telefono']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cód. Barras</th>
                <th>Categoría</th>
                <th style="text-align: right;">Stock Actual</th>
                <th style="text-align: right;">Stock Mínimo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $p): ?>
                <tr>
                    <td style="font-weight: 500;"><?= htmlspecialchars($p['nombre']) ?></td>
                    <td style="font-family: monospace; color: #64748b;"><?= htmlspecialchars($p['codigo_barras'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['categoria'] ?: 'Sin Categoría') ?></td>
                    <td style="text-align: right;" class="<?= $p['stock_total'] <= 0 ? 'stock-critico' : 'stock-bajo' ?>">
                        <?= $p['stock_total'] ?>
                    </td>
                    <td style="text-align: right; color: #64748b;"><?= $p['stock_minimo'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if(empty($productos)): ?>
                <tr><td colspan="5" style="text-align:center; padding: 40px; color: #64748b;">No hay productos en estado crítico.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        Documento generado automáticamente por <?= htmlspecialchars($_sc_brand['farmacia_nombre']) ?> &mdash; Módulo de Reportes
    </div>
</body>
</html>
<?php
exit;
endif;

$pagina_titulo = 'Stock Crítico';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1200px;">
    
    <div class="page-header">
        <div>
            <h1>Reporte de Stock Crítico</h1>
            <p class="page-subtitle">Monitorea los productos que requieren reabastecimiento urgente.</p>
        </div>
        <div class="page-header-actions">
            <a href="?print=1" target="_blank" class="btn btn-secundario">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0v2.796c0 .168.134.3.3.3h9.9c.166 0 .3-.132.3-.3V7.034z" />
                </svg>
                Imprimir Reporte
            </a>
        </div>
    </div>

    <!-- Resumen -->
    <div class="grid-3-cols gap-6 mb-6">
        <div class="kpi-card" style="border-left: 4px solid var(--color-peligro);">
            <div class="kpi-icon text-peligro">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="kpi-title">Productos en Alerta</div>
            <div class="kpi-value text-peligro"><?= count($productos) ?></div>
        </div>
    </div>

    <div class="card m-0">
        <div class="card-header border-b border-borde">
            <h2 class="card-titulo">Detalle de Productos</h2>
        </div>
        <div class="tabla-contenedor" style="border: none; border-radius: 0 0 var(--radio-lg) var(--radio-lg);">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th style="text-align: right;">Stock Actual</th>
                        <th style="text-align: right;">Mínimo Perm.</th>
                        <th style="text-align: center;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state" style="padding: 40px 20px;">
                                    <svg class="empty-state-icon text-exito" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div class="empty-state-titulo">Inventario Saludable</div>
                                    <div class="empty-state-msg">No hay productos en estado crítico o con bajo stock.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($p['nombre']) ?></div>
                                    <div class="text-xs text-secundario mt-1" style="font-variant-numeric: tabular-nums;"><?= htmlspecialchars($p['codigo_barras'] ?? 'N/A') ?></div>
                                </td>
                                <td class="text-secundario"><?= htmlspecialchars($p['categoria'] ?: 'Sin Categoría') ?></td>
                                <td style="text-align: right; font-weight: 600; font-size: 1.1rem; color: <?= $p['stock_total'] <= 0 ? 'var(--color-peligro)' : 'var(--color-advertencia)' ?>;" style="font-variant-numeric: tabular-nums;">
                                    <?= $p['stock_total'] ?>
                                </td>
                                <td style="text-align: right;" class="text-secundario" style="font-variant-numeric: tabular-nums;">
                                    <?= $p['stock_minimo'] ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($p['stock_total'] <= 0): ?>
                                        <span class="badge badge-peligro">
                                            <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background-color:currentColor; margin-right:4px;"></span>
                                            Agotado
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-advertencia">
                                            <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background-color:currentColor; margin-right:4px;"></span>
                                            Crítico
                                        </span>
                                    <?php endif; ?>
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
