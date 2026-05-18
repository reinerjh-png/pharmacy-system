<?php
// modules/recetas/ver.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('ventas');
$pdo = conectar();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: lista.php');
    exit;
}

$stmtR = $pdo->prepare("
    SELECT r.*, u.nombre as despachador
    FROM recetas r
    JOIN usuarios u ON r.usuario_id = u.id
    WHERE r.id = ?
");
$stmtR->execute([$id]);
$receta = $stmtR->fetch();

if (!$receta) {
    header('Location: lista.php');
    exit;
}

$stmtD = $pdo->prepare("
    SELECT d.*, p.nombre, p.codigo_barras
    FROM detalle_recetas d
    JOIN productos p ON d.producto_id = p.id
    WHERE d.receta_id = ?
");
$stmtD->execute([$id]);
$detalles = $stmtD->fetchAll();

$pagina_titulo = 'Receta #' . str_pad($receta['id'], 5, '0', STR_PAD_LEFT);
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 900px;">
    
    <div class="mb-6 flex justify-between items-start">
        <div>
            <a href="lista.php" class="btn btn-ghost" style="padding-left: 0; margin-bottom: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al libro de recetas
            </a>
            <h1 class="text-2xl m-0 flex items-center gap-3">
                Receta Médica <?= htmlspecialchars($receta['numero_receta']) ?>
            </h1>
        </div>
        <div>
            <button class="btn btn-secundario" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.728 6.75H17.27m-10.542 0A2.25 2.25 0 004.5 9v4.5a2.25 2.25 0 002.25 2.25h.5v4.5h9.5v-4.5h.5a2.25 2.25 0 002.25-2.25V9a2.25 2.25 0 00-2.25-2.25h-.5m-10.542 0V4.5A2.25 2.25 0 019 2.25h6a2.25 2.25 0 012.25 2.25v4.5" />
                </svg>
                Imprimir Constancia
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
                    <div class="text-sm font-medium text-secundario mb-1">Fecha de Registro</div>
                    <div class="font-semibold text-lg"><?= date('d/m/Y H:i', strtotime($receta['fecha'])) ?></div>
                </div>
                <div>
                    <div class="text-sm font-medium text-secundario mb-1">Paciente</div>
                    <div class="font-semibold text-lg"><?= htmlspecialchars($receta['nombre_paciente']) ?></div>
                </div>
                <div style="grid-column: span 2;">
                    <div class="text-sm font-medium text-secundario mb-1">Médico Prescriptor</div>
                    <div class="font-semibold text-lg">
                        <?= htmlspecialchars($receta['nombre_medico'] ?: 'No especificado') ?>
                    </div>
                </div>
                <div>
                    <div class="text-sm font-medium text-secundario mb-1">Despachado por</div>
                    <div class="font-semibold">
                        <div class="flex items-center gap-2">
                            <div style="width: 24px; height: 24px; border-radius: 50%; background-color: var(--color-primario-claro); color: var(--color-primario); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">
                                <?= strtoupper(substr($receta['despachador'], 0, 1)) ?>
                            </div>
                            <?= htmlspecialchars($receta['despachador']) ?>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="text-sm font-medium text-secundario mb-1">Venta Vinculada</div>
                    <div>
                        <?php if ($receta['venta_id']): ?>
                            <a href="../ventas/detalle_venta.php?id=<?= $receta['venta_id'] ?>" class="btn btn-sm btn-ghost" style="padding-left: 0;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                                Venta #<?= str_pad($receta['venta_id'], 6, '0', STR_PAD_LEFT) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-secundario">Ninguna</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($receta['observaciones'])): ?>
            <div class="mt-6 pt-4" style="border-top: 1px solid var(--color-borde);">
                <div class="text-sm font-medium text-secundario mb-2">Observaciones / Posología</div>
                <div class="bg-fondo" style="padding: 16px; border-radius: var(--radio-md); border: 1px solid var(--color-borde);">
                    <?= nl2br(htmlspecialchars($receta['observaciones'])) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-b border-borde">
            <h2 class="card-titulo">Medicamentos Despachados</h2>
        </div>
        <div class="tabla-contenedor" style="border: none; border-radius: 0 0 var(--radio-lg) var(--radio-lg);">
            <table class="tabla">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">N°</th>
                        <th>Medicamento</th>
                        <th style="text-align: right;">Cantidad Prescrita</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($detalles as $d): ?>
                        <tr>
                            <td style="text-align: center; color: var(--texto-secundario);"><?= $i++ ?></td>
                            <td>
                                <div class="font-medium"><?= htmlspecialchars($d['nombre']) ?></div>
                                <div class="text-xs text-secundario mt-1" style="font-variant-numeric: tabular-nums;"><?= htmlspecialchars($d['codigo_barras'] ?? '') ?></div>
                            </td>
                            <td style="text-align: right; font-weight: 600; font-size: 1.1rem;"><?= $d['cantidad'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
