<?php
// modules/inventario/lista_productos.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('inventario');
$pdo = conectar();

// Filtros básicos
$filtro_cat = $_GET['categoria_id'] ?? '';
$filtro_nombre = $_GET['q'] ?? '';

$where = ["1=1"];
$params = [];

if ($filtro_cat !== '') {
    $where[] = "p.categoria_id = :cat";
    $params[':cat'] = $filtro_cat;
}
if ($filtro_nombre !== '') {
    $where[] = "(p.nombre LIKE :q OR p.codigo_barras LIKE :q)";
    $params[':q'] = "%$filtro_nombre%";
}

$where_sql = implode(' AND ', $where);

// Listado de productos con stock total
$sql = "
    SELECT p.id, p.nombre, p.codigo_barras, c.nombre as categoria, 
           COALESCE(SUM(i.stock_actual), 0) as stock_total,
           MAX(i.precio_venta) as precio_venta, p.activo,
           MIN(i.fecha_vencimiento) as proximo_vencimiento,
           MAX(i.stock_minimo) as stock_minimo
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN inventario i ON p.id = i.producto_id
    WHERE $where_sql
    GROUP BY p.id
    ORDER BY p.nombre ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// Obtener categorías para filtro
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll();

$pagina_titulo = 'Catálogo de Productos';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1>Catálogo de Productos</h1>
            <p class="page-subtitle">Gestiona tu inventario, precios y stock disponible.</p>
        </div>
        <div class="page-header-actions">
            <a href="agregar_producto.php" class="btn btn-primario">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo Producto
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card">
        <div class="card-body">
            <form action="" method="GET" class="flex items-center gap-4" style="flex-wrap: wrap;">
                <div class="form-group m-0" style="flex: 1; min-width: 250px;">
                    <label class="form-label">Buscar producto</label>
                    <div class="input-icon-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input type="text" name="q" class="form-control" placeholder="Nombre o código de barras" value="<?= htmlspecialchars($filtro_nombre) ?>">
                    </div>
                </div>
                <div class="form-group m-0" style="width: 220px;">
                    <label class="form-label">Categoría</label>
                    <select name="categoria_id" class="form-control">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $filtro_cat == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-2" style="align-self: flex-end; margin-bottom: var(--espacio-4);">
                    <button type="submit" class="btn btn-secundario">Filtrar</button>
                    <?php if ($filtro_cat || $filtro_nombre): ?>
                        <a href="lista_productos.php" class="btn btn-ghost">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="tabla-wrapper">
        <div class="tabla-contenedor" style="border: none; border-radius: 0;">
            <table class="tabla">
                <thead>
                    <tr>
                        <th style="width: 120px;">Cód. Barras</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Precio Venta</th>
                        <th>Stock Total</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <svg class="empty-state-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>
                                    <div class="empty-state-titulo">No se encontraron productos</div>
                                    <div class="empty-state-msg">Intenta con otros filtros de búsqueda o agrega un producto nuevo.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <?php 
                                $stock = (int)$p['stock_total'];
                                $minimo = (int)$p['stock_minimo'];
                                $dias_vencimiento = null;
                                if ($p['proximo_vencimiento']) {
                                    $datetime1 = new DateTime();
                                    $datetime2 = new DateTime($p['proximo_vencimiento']);
                                    $interval = $datetime1->diff($datetime2);
                                    $dias_vencimiento = (int)$interval->format('%R%a');
                                }

                                // Indicador visual del semáforo
                                $dot_color = 'var(--texto-terciario)'; // Gris por defecto (sin vencimiento)
                                $dot_title = 'Sin información de vencimiento';
                                if ($dias_vencimiento !== null) {
                                    if ($dias_vencimiento <= 7) {
                                        $dot_color = 'var(--color-peligro)';
                                        $dot_title = "Crítico: Vence en $dias_vencimiento días o ya venció";
                                    } elseif ($dias_vencimiento <= 30) {
                                        $dot_color = 'var(--color-advertencia)';
                                        $dot_title = "Atención: Vence en $dias_vencimiento días";
                                    } else {
                                        $dot_color = 'var(--color-exito)';
                                        $dot_title = "Stock seguro: Vence en $dias_vencimiento días";
                                    }
                                }
                            ?>
                            <tr>
                                <td class="text-xs text-secundario font-medium" style="letter-spacing: 0.05em; font-variant-numeric: tabular-nums;">
                                    <?= htmlspecialchars($p['codigo_barras'] ?? 'N/A') ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background-color: <?= $dot_color ?>;" title="<?= $dot_title ?>"></div>
                                        <div class="font-medium text-primario"><?= htmlspecialchars($p['nombre']) ?></div>
                                    </div>
                                </td>
                                <td class="text-secundario"><?= htmlspecialchars($p['categoria']) ?></td>
                                <td class="font-medium">S/ <?= number_format($p['precio_venta'], 2) ?></td>
                                <td>
                                    <span style="font-weight: 600; color: <?= $stock <= $minimo ? 'var(--color-peligro)' : 'inherit' ?>;">
                                        <?= $stock ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['activo'] == 0): ?>
                                        <span class="badge badge-neutral">Inactivo</span>
                                    <?php else: ?>
                                        <?php if ($stock <= 0): ?>
                                            <span class="badge badge-peligro">Agotado</span>
                                        <?php elseif ($stock <= $minimo): ?>
                                            <span class="badge badge-advertencia">Bajo Stock</span>
                                        <?php else: ?>
                                            <span class="badge badge-exito">Activo</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="editar_producto.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-secundario">Editar</a>
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
