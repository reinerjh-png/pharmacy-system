<?php
// modules/inventario/alertas_vencimiento.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('inventario');
$pdo = conectar();

// Obtener lotes con stock que vencen en los próximos 90 días o ya están vencidos
$sql = "
    SELECT i.id as inventario_id, p.nombre as producto, i.lote, i.fecha_vencimiento, i.stock_actual,
           DATEDIFF(i.fecha_vencimiento, CURDATE()) as dias_restantes
    FROM inventario i
    JOIN productos p ON i.producto_id = p.id
    WHERE i.stock_actual > 0 
      AND i.fecha_vencimiento IS NOT NULL 
      AND DATEDIFF(i.fecha_vencimiento, CURDATE()) <= 90
      AND p.farmacia_id = ?
    ORDER BY i.fecha_vencimiento ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([farmacia_id()]);
$alertas = $stmt->fetchAll();

$pagina_titulo = 'Alertas de Vencimiento';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1000px;">
    
    <div class="page-header">
        <div>
            <h1>Alertas de Vencimiento</h1>
            <p class="page-subtitle">Monitoreo de lotes próximos a caducar (≤ 90 días) o ya vencidos.</p>
        </div>
        <div class="page-header-actions">
            <a href="lista_productos.php" class="btn btn-secundario">
                Volver al catálogo
            </a>
        </div>
    </div>

    <!-- Tarjetas de Resumen Semántico -->
    <div class="grid-3-cols" style="margin-bottom: 32px;">
        <?php
        $criticos = 0;
        $atencion = 0;
        $seguros = 0;
        foreach ($alertas as $a) {
            if ($a['dias_restantes'] <= 7) $criticos++;
            elseif ($a['dias_restantes'] <= 30) $atencion++;
            else $seguros++;
        }
        ?>
        <div class="card card-estado-peligro m-0">
            <div class="card-body">
                <div class="text-sm font-medium mb-1" style="color: var(--color-peligro);">Críticos (≤ 7 días)</div>
                <div class="text-2xl font-bold"><?= $criticos ?> Lotes</div>
            </div>
        </div>
        <div class="card card-estado-advertencia m-0">
            <div class="card-body">
                <div class="text-sm font-medium mb-1" style="color: var(--color-advertencia-texto);">Atención (8 - 30 días)</div>
                <div class="text-2xl font-bold"><?= $atencion ?> Lotes</div>
            </div>
        </div>
        <div class="card card-estado-exito m-0">
            <div class="card-body">
                <div class="text-sm font-medium mb-1" style="color: var(--color-exito-texto);">En Observación (> 30 días)</div>
                <div class="text-2xl font-bold"><?= $seguros ?> Lotes</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Lote</th>
                        <th>Vencimiento</th>
                        <th>Días Restantes</th>
                        <th>Stock Actual</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alertas)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <svg class="empty-state-icon" style="color: var(--color-exito);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div class="empty-state-titulo">Todo en orden</div>
                                    <div class="empty-state-msg">No hay lotes próximos a vencer en los próximos 90 días.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($alertas as $a): ?>
                            <?php 
                                $dias = (int)$a['dias_restantes'];
                                $badge_class = '';
                                $estado_texto = '';
                                $row_class = '';
                                
                                if ($dias < 0) {
                                    $badge_class = 'badge-peligro';
                                    $estado_texto = 'VENCIDO';
                                    $row_class = 'bg-peligro-50'; // Opción para resaltar la fila si se desea
                                } elseif ($dias <= 7) {
                                    $badge_class = 'badge-peligro badge-pulso';
                                    $estado_texto = 'Crítico';
                                } elseif ($dias <= 30) {
                                    $badge_class = 'badge-advertencia';
                                    $estado_texto = 'Atención';
                                } else {
                                    $badge_class = 'badge-exito';
                                    $estado_texto = 'Observación';
                                }
                            ?>
                            <tr class="<?= $row_class ?>">
                                <td class="font-medium"><?= htmlspecialchars($a['producto']) ?></td>
                                <td class="text-secundario text-sm" style="font-variant-numeric: tabular-nums;"><?= htmlspecialchars($a['lote'] ?: 'S/L') ?></td>
                                <td><?= date('d/m/Y', strtotime($a['fecha_vencimiento'])) ?></td>
                                <td style="font-variant-numeric: tabular-nums;">
                                    <?php if ($dias < 0): ?>
                                        <span class="text-peligro font-semibold">Hace <?= abs($dias) ?> días</span>
                                    <?php else: ?>
                                        <span class="font-medium"><?= $dias ?> días</span>
                                    <?php endif; ?>
                                </td>
                                <td class="font-semibold"><?= $a['stock_actual'] ?></td>
                                <td>
                                    <span class="badge <?= $badge_class ?>"><?= $estado_texto ?></span>
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
