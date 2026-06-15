<?php
// modules/recetas/lista.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('ventas'); // O un permiso específico para recetas si se desea
$pdo = conectar();
$es_admin = es_admin();

// Filtros
$f_fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$f_fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$f_paciente = trim($_GET['paciente'] ?? '');

$where = ["DATE(r.fecha) BETWEEN :inicio AND :fin", "r.farmacia_id = :fid"];
$params = [':inicio' => $f_fecha_inicio, ':fin' => $f_fecha_fin, ':fid' => farmacia_id()];

if ($f_paciente !== '') {
    $where[] = "r.nombre_paciente LIKE :paciente";
    $params[':paciente'] = "%$f_paciente%";
}

$where_sql = implode(' AND ', $where);

$sql = "
    SELECT r.*, u.nombre as despachador
    FROM recetas r
    JOIN usuarios u ON r.usuario_id = u.id
    WHERE $where_sql
    ORDER BY r.fecha DESC
";
$recetas = $pdo->prepare($sql);
$recetas->execute($params);
$recetas = $recetas->fetchAll();

$pagina_titulo = 'Libro de Recetas';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1200px;">
    
    <div class="page-header">
        <div>
            <h1>Libro de Recetas</h1>
            <p class="page-subtitle">Registro de medicamentos despachados con receta médica.</p>
        </div>
        <div class="page-header-actions">
            <a href="registrar.php" class="btn btn-primario">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Registrar Receta
            </a>
        </div>
    </div>

    <!-- Buscador / Filtros -->
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
                <div style="flex: 2; min-width: 250px;">
                    <label class="form-label text-sm text-secundario">Buscar Paciente</label>
                    <input type="text" name="paciente" class="form-control" placeholder="Nombre del paciente..." value="<?= htmlspecialchars($f_paciente) ?>">
                </div>
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

    <!-- Tabla -->
    <div class="card">
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>ID Reg.</th>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Médico / Colegiatura</th>
                        <th>Venta Ref.</th>
                        <th>Despachado por</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recetas)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <svg class="empty-state-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    <div class="empty-state-titulo">No hay registros</div>
                                    <div class="empty-state-msg">No se encontraron recetas médicas para los filtros aplicados.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recetas as $r): ?>
                            <tr>
                                <td class="font-medium text-secundario" style="font-variant-numeric: tabular-nums;">
                                    #<?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($r['fecha'])) ?></td>
                                <td class="font-semibold"><?= htmlspecialchars($r['nombre_paciente']) ?></td>
                                <td class="text-secundario"><?= htmlspecialchars($r['nombre_medico'] ?: 'No especificado') ?></td>
                                <td>
                                    <?php if ($r['venta_id']): ?>
                                        <a href="../ventas/detalle_venta.php?id=<?= $r['venta_id'] ?>" class="text-primario hover:underline" style="font-variant-numeric: tabular-nums;">
                                            Venta #<?= str_pad($r['venta_id'], 6, '0', STR_PAD_LEFT) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-secundario">Ninguna</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['despachador']) ?></td>
                                <td style="text-align: right;">
                                    <a href="ver.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-secundario">Ver Detalles</a>
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
