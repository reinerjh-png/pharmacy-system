<?php
// modules/reportes/ventas_diarias.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('reportes');
$pdo = conectar();

$fecha = $_GET['fecha'] ?? date('Y-m-d');

$fid = farmacia_id();

// Resumen general del día
$stmtR = $pdo->prepare("
    SELECT COUNT(id) as transacciones, SUM(total) as total_vendido
    FROM ventas 
    WHERE DATE(fecha) = ? AND estado = 'completada' AND farmacia_id = ?
");
$stmtR->execute([$fecha, $fid]);
$resumen = $stmtR->fetch();

$transacciones = (int)$resumen['transacciones'];
$total_vendido = (float)$resumen['total_vendido'];
$ticket_promedio = $transacciones > 0 ? $total_vendido / $transacciones : 0;

// Ventas por hora para el gráfico
$stmtH = $pdo->prepare("
    SELECT HOUR(fecha) as hora, SUM(total) as total
    FROM ventas
    WHERE DATE(fecha) = ? AND estado = 'completada' AND farmacia_id = ?
    GROUP BY HOUR(fecha)
    ORDER BY hora ASC
");
$stmtH->execute([$fecha, $fid]);
$ventas_hora = $stmtH->fetchAll();

$horas = [];
$totales = [];
// Inicializar arreglo de horas operativas de 8 a 22hrs
for ($i = 8; $i <= 22; $i++) {
    $horas[] = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
    $totales[$i] = 0;
}

foreach ($ventas_hora as $vh) {
    $h = (int)$vh['hora'];
    if (isset($totales[$h])) {
        $totales[$h] = (float)$vh['total'];
    }
}

// Lista de ventas para la tabla
$stmtLista = $pdo->prepare("
    SELECT v.id, v.fecha, v.total, v.tipo_pago, u.nombre as cajero
    FROM ventas v
    JOIN usuarios u ON v.usuario_id = u.id
    WHERE DATE(v.fecha) = ? AND v.estado = 'completada' AND v.farmacia_id = ?
    ORDER BY v.fecha DESC
");
$stmtLista->execute([$fecha, $fid]);
$lista_ventas = $stmtLista->fetchAll();

$pagina_titulo = 'Reporte Diario';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1200px;">
    <div class="page-header">
        <div>
            <h1>Reporte de Ventas Diarias</h1>
            <p class="page-subtitle">Analiza el rendimiento de ventas y el flujo de transacciones por hora.</p>
        </div>
        <div class="page-header-actions">
            <form action="" method="GET" class="flex items-center gap-2">
                <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" class="form-control" style="width: auto; height: 42px;">
                <button type="submit" class="btn btn-primario" style="height: 42px;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Actualizar
                </button>
            </form>
        </div>
    </div>

    <!-- Indicadores -->
    <div class="grid-3-cols gap-6 mb-6">
        <div class="kpi-card" style="border-left: 4px solid var(--color-primario);">
            <div class="kpi-icon text-primario">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V5.942c0-.754-.726-1.294-1.453-1.096V4.846M3.375 18v-4.5m17.25 4.5v-4.5m-17.25-3h17.25M12 15.75h.008v.008H12v-.008zM12 12h.008v.008H12V12z" />
                </svg>
            </div>
            <div class="kpi-title">Total Vendido</div>
            <div class="kpi-value text-primario">S/ <?= number_format($total_vendido, 2) ?></div>
        </div>
        
        <div class="kpi-card" style="border-left: 4px solid var(--color-info);">
            <div class="kpi-icon text-info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                </svg>
            </div>
            <div class="kpi-title">Transacciones Completadas</div>
            <div class="kpi-value"><?= $transacciones ?></div>
        </div>
        
        <div class="kpi-card" style="border-left: 4px solid var(--color-exito);">
            <div class="kpi-icon text-exito">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </div>
            <div class="kpi-title">Ticket Promedio</div>
            <div class="kpi-value text-exito">S/ <?= number_format($ticket_promedio, 2) ?></div>
        </div>
    </div>

    <div style="display: grid; gap: 24px;" class="report-layout">
        <style>
            @media(min-width: 1024px) {
                .report-layout { grid-template-columns: 2fr 1fr; }
            }
        </style>

        <!-- Gráfico -->
        <div class="card m-0" style="display: flex; flex-direction: column;">
            <div class="card-header border-b border-borde">
                <h2 class="card-titulo">Flujo de Ingresos por Hora</h2>
            </div>
            <div class="card-body" style="flex: 1; min-height: 350px;">
                <div style="position: relative; height: 100%; width: 100%;">
                    <canvas id="graficoHoras"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card m-0" style="display: flex; flex-direction: column;">
            <div class="card-header border-b border-borde">
                <h2 class="card-titulo">Últimas Transacciones</h2>
            </div>
            <div class="tabla-contenedor" style="border: none; flex: 1; max-height: 450px; overflow-y: auto; border-radius: 0 0 var(--radio-lg) var(--radio-lg);">
                <table class="tabla">
                    <thead style="position: sticky; top: 0; background: var(--bg-card); z-index: 10;">
                        <tr>
                            <th>Ticket</th>
                            <th>Hora</th>
                            <th style="text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista_ventas)): ?>
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state" style="padding: 30px 20px;">
                                        <svg class="empty-state-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="empty-state-titulo">Sin ventas registradas</div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lista_ventas as $v): ?>
                                <tr>
                                    <td>
                                        <a href="../ventas/detalle_venta.php?id=<?= $v['id'] ?>" class="text-primario font-medium hover:underline flex items-center gap-1" style="font-variant-numeric: tabular-nums;">
                                            #<?= str_pad($v['id'], 6, '0', STR_PAD_LEFT) ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 12px; height: 12px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                                            </svg>
                                        </a>
                                        <div class="text-xs text-secundario mt-1"><?= htmlspecialchars($v['cajero']) ?></div>
                                    </td>
                                    <td class="text-secundario font-medium" style="font-variant-numeric: tabular-nums;">
                                        <?= date('H:i', strtotime($v['fecha'])) ?>
                                    </td>
                                    <td style="text-align:right; font-variant-numeric: tabular-nums;" class="font-semibold text-primario">
                                        S/ <?= number_format($v['total'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Definición de variables CSS para el gráfico (detectando modo claro/oscuro)
    const rootStyles = getComputedStyle(document.documentElement);
    const colorPrimario = rootStyles.getPropertyValue("--color-primario").trim() || "#0f172a";
    const colorGrid = rootStyles.getPropertyValue("--color-borde").trim() || "#e2e8f0";
    const colorTexto = rootStyles.getPropertyValue("--texto-secundario").trim() || "#64748b";

    const ctx = document.getElementById("graficoHoras").getContext("2d");
    
    // Gradiente moderno
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, "rgba(15, 23, 42, 0.2)");
    gradient.addColorStop(1, "rgba(15, 23, 42, 0)");

    new Chart(ctx, {
        type: "line",
        data: {
            labels: ' . json_encode($horas) . ',
            datasets: [{
                label: "Ingresos (S/)",
                data: ' . json_encode(array_values($totales)) . ',
                borderColor: colorPrimario,
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4, // Curvas más suaves
                pointBackgroundColor: "#ffffff",
                pointBorderColor: colorPrimario,
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: "rgba(15, 23, 42, 0.9)",
                    titleFont: { size: 13, family: "Inter, sans-serif" },
                    bodyFont: { size: 14, family: "Inter, sans-serif", weight: "bold" },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return "S/ " + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: { 
                x: {
                    grid: { display: false },
                    ticks: { color: colorTexto, font: { family: "Inter, sans-serif" } }
                },
                y: { 
                    beginAtZero: true,
                    grid: { color: colorGrid, drawBorder: false },
                    ticks: { 
                        color: colorTexto, 
                        font: { family: "Inter, sans-serif" },
                        callback: function(value) {
                            return "S/ " + value;
                        }
                    }
                } 
            },
            interaction: {
                intersect: false,
                mode: "index",
            },
        }
    });
});
</script>
';
include __DIR__ . '/../../views/layout/footer.php'; 
?>
