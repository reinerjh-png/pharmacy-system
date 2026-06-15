<?php
// modules/reportes/rotacion_productos.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('reportes');
$pdo = conectar();

$inicio = $_GET['inicio'] ?? date('Y-m-01', strtotime('-30 days'));
$fin = $_GET['fin'] ?? date('Y-m-d');

// Calculamos la rotación: Ventas (salidas) vs Stock Actual
// Se ordenará por el índice de rotación (Ventas / (Stock + 1) para evitar división por cero)
$fid = farmacia_id();
$sql = "
    SELECT p.id, p.nombre, p.codigo_barras, c.nombre as categoria,
           COALESCE((SELECT SUM(stock_actual) FROM inventario WHERE producto_id = p.id), 0) as stock_actual,
           COALESCE(SUM(dv.cantidad), 0) as cantidad_vendida
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN detalle_ventas dv ON p.id = dv.producto_id
    LEFT JOIN ventas v ON dv.venta_id = v.id AND v.estado = 'completada' AND DATE(v.fecha) BETWEEN :inicio AND :fin
    WHERE p.activo = 1 AND p.farmacia_id = :fid
    GROUP BY p.id
    ORDER BY (COALESCE(SUM(dv.cantidad), 0) / (COALESCE((SELECT SUM(stock_actual) FROM inventario WHERE producto_id = p.id), 0) + 1)) DESC, cantidad_vendida DESC
    LIMIT 50
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':inicio' => $inicio, ':fin' => $fin, ':fid' => $fid]);
$productos = $stmt->fetchAll();

$pagina_titulo = 'Rotación de Inventario';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1200px;">
    
    <div class="page-header">
        <div>
            <h1>Rotación de Inventario</h1>
            <p class="page-subtitle">Analiza el índice de rotación (Salidas vs Stock Actual) para optimizar compras.</p>
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

    <!-- Guía de Rotación -->
    <div class="grid-3-cols gap-6 mb-6">
        <div class="kpi-card" style="border-left: 4px solid var(--color-exito);">
            <div class="kpi-title">Alta Rotación</div>
            <div class="text-xs text-secundario mt-1">Se vende rápido en relación a su stock. Priorizar reabastecimiento.</div>
        </div>
        <div class="kpi-card" style="border-left: 4px solid var(--color-advertencia);">
            <div class="kpi-title">Rotación Media</div>
            <div class="text-xs text-secundario mt-1">Ventas constantes. Mantener niveles de inventario normales.</div>
        </div>
        <div class="kpi-card" style="border-left: 4px solid var(--color-peligro);">
            <div class="kpi-title">Baja Rotación / Estancado</div>
            <div class="text-xs text-secundario mt-1">Pocas ventas con alto stock. Riesgo de caducidad.</div>
        </div>
    </div>

    <div class="card m-0">
        <div class="card-header border-b border-borde">
            <h2 class="card-titulo">Índice de Rotación (Top 50)</h2>
        </div>
        <div class="tabla-contenedor" style="border: none; border-radius: 0 0 var(--radio-lg) var(--radio-lg);">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th style="text-align: right;">Stock Actual</th>
                        <th style="text-align: right;">Cant. Vendida</th>
                        <th style="text-align: right;">Índice (Salida/Stock)</th>
                        <th style="text-align: center;">Diagnóstico</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state" style="padding: 40px 20px;">
                                    <svg class="empty-state-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5z" />
                                    </svg>
                                    <div class="empty-state-titulo">Sin datos de rotación</div>
                                    <div class="empty-state-msg">No hay registros para calcular índices en este periodo.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): 
                            $stock = (int)$p['stock_actual'];
                            $vendido = (int)$p['cantidad_vendida'];
                            $indice = $vendido / ($stock > 0 ? $stock : 1);
                            
                            $diagnostico = '';
                            $clase_badge = '';
                            if ($indice > 1.5 || ($stock == 0 && $vendido > 0)) {
                                $diagnostico = 'Alta';
                                $clase_badge = 'badge-exito';
                            } elseif ($indice >= 0.5) {
                                $diagnostico = 'Media';
                                $clase_badge = 'badge-advertencia';
                            } else {
                                $diagnostico = 'Baja';
                                $clase_badge = 'badge-peligro';
                            }
                        ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($p['nombre']) ?></div>
                                    <div class="text-xs text-secundario mt-1" style="font-variant-numeric: tabular-nums;"><?= htmlspecialchars($p['codigo_barras'] ?? 'N/A') ?></div>
                                </td>
                                <td class="text-secundario"><?= htmlspecialchars($p['categoria'] ?: 'Sin Categoría') ?></td>
                                <td style="text-align: right; font-variant-numeric: tabular-nums;"><?= $stock ?></td>
                                <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums;"><?= $vendido ?></td>
                                <td style="text-align: right; font-variant-numeric: tabular-nums;" class="text-primario font-semibold">
                                    <?= number_format($indice, 2) ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($vendido == 0): ?>
                                        <span class="badge" style="background: var(--bg-card); border: 1px solid var(--color-borde); color: var(--texto-secundario);">Estancado</span>
                                    <?php else: ?>
                                        <span class="badge <?= $clase_badge ?>">Rotación <?= $diagnostico ?></span>
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
