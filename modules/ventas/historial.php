<?php
// modules/ventas/historial.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('ventas');
$pdo = conectar();
$es_admin = es_admin();

$error = '';
$exito = '';

// Lógica para anular venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['anular_id'])) {
    if (!$es_admin) {
        $error = "Solo un administrador puede anular ventas.";
    } else {
        $anular_id = (int)$_POST['anular_id'];
        try {
            $pdo->beginTransaction();
            
            // Verificar estado actual
            $stmtV = $pdo->prepare("SELECT estado FROM ventas WHERE id = ? FOR UPDATE");
            $stmtV->execute([$anular_id]);
            $venta = $stmtV->fetch();
            
            if (!$venta) {
                throw new Exception("La venta no existe.");
            }
            if ($venta['estado'] === 'anulada') {
                throw new Exception("La venta ya se encuentra anulada.");
            }
            
            // Actualizar estado a anulada
            $pdo->prepare("UPDATE ventas SET estado = 'anulada' WHERE id = ?")->execute([$anular_id]);
            
            // Obtener detalles para devolver stock
            $stmtDet = $pdo->prepare("SELECT inventario_id, cantidad FROM detalle_ventas WHERE venta_id = ?");
            $stmtDet->execute([$anular_id]);
            $detalles = $stmtDet->fetchAll();
            
            foreach ($detalles as $det) {
                // Devolver al lote exacto
                $pdo->prepare("UPDATE inventario SET stock_actual = stock_actual + ? WHERE id = ?")
                    ->execute([$det['cantidad'], $det['inventario_id']]);
                    
                // Loguear ajuste (opcional pero recomendado)
                $pdo->prepare("INSERT INTO ajustes_stock (inventario_id, usuario_id, tipo, cantidad, motivo) VALUES (?, ?, 'entrada', ?, ?)")
                    ->execute([$det['inventario_id'], $_SESSION['usuario_id'], $det['cantidad'], 'Anulación Venta #' . $anular_id]);
            }
            
            $pdo->commit();
            $exito = "Venta #$anular_id anulada. El stock ha sido restituido.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Filtros
$f_fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$f_fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$f_estado = $_GET['estado'] ?? '';
$f_cajero = $_GET['cajero'] ?? '';

$where = ["DATE(v.fecha) BETWEEN :inicio AND :fin"];
$params = [':inicio' => $f_fecha_inicio, ':fin' => $f_fecha_fin];

if ($f_estado !== '') {
    $where[] = "v.estado = :estado";
    $params[':estado'] = $f_estado;
}
if ($f_cajero !== '' && $es_admin) {
    $where[] = "v.usuario_id = :cajero";
    $params[':cajero'] = $f_cajero;
}

// Si es cajero, solo ve sus propias ventas o puede ver las de todos dependiendo de la regla de negocio. 
// Vamos a permitir que el cajero vea solo sus ventas si queremos restringirlo:
if (!$es_admin) {
    $where[] = "v.usuario_id = :mi_id";
    $params[':mi_id'] = $_SESSION['usuario_id'];
}

$where_sql = implode(' AND ', $where);

$sql = "
    SELECT v.id, v.fecha, v.total, v.tipo_pago, v.estado, u.nombre as cajero
    FROM ventas v
    JOIN usuarios u ON v.usuario_id = u.id
    WHERE $where_sql
    ORDER BY v.id DESC
";
$ventas = $pdo->prepare($sql);
$ventas->execute($params);
$ventas = $ventas->fetchAll();

$cajeros = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol_id = 2")->fetchAll();

$pagina_titulo = 'Historial de Ventas';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1200px;">
    
    <div class="page-header">
        <div>
            <h1>Historial de Ventas</h1>
            <p class="page-subtitle">Revisa comprobantes, filtra por fechas y anula ventas si es necesario.</p>
        </div>
        <div class="page-header-actions">
            <a href="nueva_venta.php" class="btn btn-primario">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva Venta
            </a>
        </div>
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

    <div class="card mb-6">
        <div class="card-body">
            <form action="" method="GET" class="flex" style="gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 150px;">
                    <label class="form-label text-sm text-secundario">Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($f_fecha_inicio) ?>">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label class="form-label text-sm text-secundario">Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($f_fecha_fin) ?>">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label class="form-label text-sm text-secundario">Estado</label>
                    <select name="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="completada" <?= $f_estado == 'completada' ? 'selected' : '' ?>>Completada</option>
                        <option value="anulada" <?= $f_estado == 'anulada' ? 'selected' : '' ?>>Anulada</option>
                    </select>
                </div>
                <?php if ($es_admin): ?>
                <div style="flex: 1; min-width: 150px;">
                    <label class="form-label text-sm text-secundario">Cajero</label>
                    <select name="cajero" class="form-control">
                        <option value="">Todos los cajeros</option>
                        <?php foreach ($cajeros as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $f_cajero == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div>
                    <button type="submit" class="btn btn-secundario" style="height: 42px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>N° Ticket</th>
                        <th>Fecha y Hora</th>
                        <th>Cajero</th>
                        <th>Tipo Pago</th>
                        <th style="text-align: right;">Total</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ventas)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <svg class="empty-state-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                    </svg>
                                    <div class="empty-state-titulo">Sin resultados</div>
                                    <div class="empty-state-msg">No se encontraron ventas para los filtros seleccionados.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ventas as $v): ?>
                            <tr>
                                <td class="font-medium text-secundario" style="font-variant-numeric: tabular-nums;">
                                    #<?= str_pad($v['id'], 6, '0', STR_PAD_LEFT) ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div style="width: 24px; height: 24px; border-radius: 50%; background-color: var(--color-primario-claro); color: var(--color-primario); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">
                                            <?= strtoupper(substr($v['cajero'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($v['cajero']) ?>
                                    </div>
                                </td>
                                <td style="text-transform: capitalize; color: var(--texto-secundario);">
                                    <?= htmlspecialchars($v['tipo_pago']) ?>
                                </td>
                                <td class="font-semibold" style="text-align: right; font-variant-numeric: tabular-nums;">
                                    S/ <?= number_format($v['total'], 2) ?>
                                </td>
                                <td>
                                    <?php if ($v['estado'] === 'completada'): ?>
                                        <span class="badge badge-exito">Completada</span>
                                    <?php else: ?>
                                        <span class="badge badge-peligro">Anulada</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div class="flex" style="justify-content: flex-end; gap: 8px;">
                                        <a href="detalle_venta.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-ghost" title="Ver Detalles">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>
                                        <?php if ($es_admin && $v['estado'] === 'completada'): ?>
                                            <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('¿Está seguro de anular esta venta? Esto devolverá el stock.');">
                                                <input type="hidden" name="anular_id" value="<?= $v['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-ghost" style="color: var(--color-peligro);" title="Anular Venta">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
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
