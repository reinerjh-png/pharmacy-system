<?php
// modules/reportes/productos_mas_vendidos.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('reportes');
$pdo = conectar();

$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fin = $_GET['fin'] ?? date('Y-m-d');

$sql = "
    SELECT p.nombre, p.codigo_barras, c.nombre as categoria, 
           SUM(dv.cantidad) as cantidad_total, 
           SUM(dv.subtotal) as monto_total
    FROM detalle_ventas dv
    JOIN ventas v ON dv.venta_id = v.id
    JOIN productos p ON dv.producto_id = p.id
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE v.estado = 'completada' 
      AND DATE(v.fecha) BETWEEN :inicio AND :fin
    GROUP BY p.id
    ORDER BY cantidad_total DESC
    LIMIT 15
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':inicio' => $inicio, ':fin' => $fin]);
$productos = $stmt->fetchAll();

$pagina_titulo = 'Productos Más Vendidos';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1200px;">
    
    <div class="page-header">
        <div>
            <h1>Top Productos Más Vendidos</h1>
            <p class="page-subtitle">Identifica los medicamentos y artículos con mayor rotación en el rango de fechas.</p>
        </div>
        <div class="page-header-actions">
            <form action="" method="GET" class="flex items-center gap-2">
                <input type="date" name="inicio" value="<?= htmlspecialchars($inicio) ?>" class="form-control" style="width: auto; height: 42px;">
                <input type="date" name="fin" value="<?= htmlspecialchars($fin) ?>" class="form-control" style="width: auto; height: 42px;">
                <button type="submit" class="btn btn-primario" style="height: 42px;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Actualizar
                </button>
            </form>
        </div>
    </div>

    <div class="card m-0">
        <div class="card-header border-b border-borde">
            <h2 class="card-titulo">Ranking de Ventas</h2>
        </div>
        <div class="tabla-contenedor" style="border: none; border-radius: 0 0 var(--radio-lg) var(--radio-lg);">
            <table class="tabla">
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center;">Rank</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th style="text-align: right;">Cant. Vendida</th>
                        <th style="text-align: right;">Monto Generado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state" style="padding: 40px 20px;">
                                    <svg class="empty-state-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z" />
                                    </svg>
                                    <div class="empty-state-titulo">Sin registros de ventas</div>
                                    <div class="empty-state-msg">No hay datos suficientes para el rango de fechas seleccionado.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $rank = 1; foreach ($productos as $p): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <?php if ($rank <= 3): ?>
                                        <div style="width: 28px; height: 28px; margin: 0 auto; border-radius: 50%; background-color: var(--color-primario-claro); color: var(--color-primario); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem;">
                                            <?= $rank ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="font-medium text-secundario">#<?= $rank ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($p['nombre']) ?></div>
                                    <div class="text-xs text-secundario mt-1" style="font-variant-numeric: tabular-nums;"><?= htmlspecialchars($p['codigo_barras'] ?? 'N/A') ?></div>
                                </td>
                                <td class="text-secundario"><?= htmlspecialchars($p['categoria'] ?: 'Sin Categoría') ?></td>
                                <td style="text-align: right; font-weight: 600; font-size: 1.1rem; color: var(--color-texto);" style="font-variant-numeric: tabular-nums;">
                                    <?= $p['cantidad_total'] ?>
                                </td>
                                <td style="text-align: right; font-variant-numeric: tabular-nums;" class="text-primario font-semibold">
                                    S/ <?= number_format($p['monto_total'], 2) ?>
                                </td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
