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

try {
    // Agrupar inventario por producto — filtrado por farmacia del tenant activo
    $sql = "
        SELECT p.id as producto_id, p.nombre, p.nombre_generico, p.codigo_barras, p.requiere_receta,
               SUM(i.stock_actual) as stock, MAX(i.precio_venta) as precio_venta
        FROM productos p
        JOIN inventario i ON p.id = i.producto_id
        WHERE p.activo = 1 
          AND i.stock_actual > 0
          AND p.farmacia_id = :fid
          AND (p.nombre LIKE :q1 OR p.nombre_generico LIKE :q2 OR p.codigo_barras LIKE :q3)
        GROUP BY p.id, p.nombre, p.nombre_generico, p.codigo_barras, p.requiere_receta
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $q_param = "%$q%";
    $stmt->execute([':q1' => $q_param, ':q2' => $q_param, ':q3' => $q_param, ':fid' => farmacia_id()]);
    $resultados = $stmt->fetchAll();

    echo json_encode($resultados);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
