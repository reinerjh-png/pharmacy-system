<?php
// api/buscar_producto.php
require_once __DIR__ . '/../auth/session_check.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['rol_id'], [1, 2])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$q = $_GET['q'] ?? '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$pdo = conectar();
// Agrupar inventario por producto
$sql = "
    SELECT p.id as producto_id, p.nombre, p.nombre_generico, p.codigo_barras, p.requiere_receta,
           SUM(i.stock_actual) as stock, MAX(i.precio_venta) as precio_venta
    FROM productos p
    JOIN inventario i ON p.id = i.producto_id
    WHERE p.activo = 1 
      AND i.stock_actual > 0
      AND (p.nombre LIKE :q OR p.nombre_generico LIKE :q OR p.codigo_barras LIKE :q)
    GROUP BY p.id
    LIMIT 10
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':q' => "%$q%"]);
$resultados = $stmt->fetchAll();

echo json_encode($resultados);
