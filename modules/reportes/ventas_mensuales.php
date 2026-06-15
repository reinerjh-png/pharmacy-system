<?php
// modules/reportes/ventas_mensuales.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('reportes');
$pdo = conectar();

$mes_anio = $_GET['mes'] ?? date('Y-m');
$partes = explode('-', $mes_anio);
$anio = (int)$partes[0];
$mes = (int)$partes[1];

// Calcular último día del mes
$ultimo_dia = date('t', strtotime($mes_anio . '-01'));

$fid = farmacia_id();

// Total del mes seleccionado
$stmtT = $pdo->prepare("SELECT SUM(total) as total FROM ventas WHERE YEAR(fecha) = ? AND MONTH(fecha) = ? AND estado = 'completada' AND farmacia_id = ?");
$stmtT->execute([$anio, $mes, $fid]);
$total_mes_actual = (float)$stmtT->fetchColumn();

// Total mes anterior
$mes_anterior = $mes - 1;
$anio_anterior = $anio;
if ($mes_anterior == 0) {
    $mes_anterior = 12;
    $anio_anterior--;
}
$stmtTa = $pdo->prepare("SELECT SUM(total) as total FROM ventas WHERE YEAR(fecha) = ? AND MONTH(fecha) = ? AND estado = 'completada' AND farmacia_id = ?");
$stmtTa->execute([$anio_anterior, $mes_anterior, $fid]);
$total_mes_anterior = (float)$stmtTa->fetchColumn();

// Variación
$variacion = 0;
if ($total_mes_anterior > 0) {
    $variacion = (($total_mes_actual - $total_mes_anterior) / $total_mes_anterior) * 100;
}

// Ventas por día del mes
$stmtD = $pdo->prepare("
    SELECT DAY(fecha) as dia, SUM(total) as total
    FROM ventas
    WHERE YEAR(fecha) = ? AND MONTH(fecha) = ? AND estado = 'completada' AND farmacia_id = ?
    GROUP BY DAY(fecha)
");
$stmtD->execute([$anio, $mes, $fid]);
$datos_dias = $stmtD->fetchAll();

$dias_grafico = [];
$totales_grafico = [];
for ($i = 1; $i <= $ultimo_dia; $i++) {
    $dias_grafico[] = $i;
    $totales_grafico[$i] = 0;
}

foreach ($datos_dias as $d) {
    $totales_grafico[(int)$d['dia']] = (float)$d['total'];
}

$pagina_titulo = 'Reporte Mensual';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1200px;">
    
    <div class="page-header">
        <div>
            <h1>Reporte de Ventas Mensuales</h1>
            <p class="page-subtitle">Analiza el rendimiento del mes y compáralo con el periodo anterior.</p>
        </div>
        <div class="page-header-actions">
            <form action="" method="GET" class="flex items-center gap-2">
                <input type="month" name="mes" value="<?= htmlspecialchars($mes_anio) ?>" class="form-control" style="width: auto; height: 42px;">
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
    <div class="grid-2-cols gap-6 mb-6">
        <div class="kpi-card" style="border-left: 4px solid var(--color-primario);">
            <div class="kpi-icon text-primario">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                </svg>
            </div>
            <div class="kpi-title">Total Mes Actual (<?= date('F Y', strtotime($mes_anio . '-01')) ?>)</div>
            <div class="kpi-value text-primario">S/ <?= number_format($total_mes_actual, 2) ?></div>
        </div>
        
        <div class="kpi-card" style="border-left: 4px solid <?= $variacion >= 0 ? 'var(--color-exito)' : 'var(--color-peligro)' ?>;">
            <div class="kpi-icon" style="color: <?= $variacion >= 0 ? 'var(--color-exito)' : 'var(--color-peligro)' ?>;">
                <?php if ($variacion >= 0): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181" />
                    </svg>
                <?php endif; ?>
            </div>
            <div class="kpi-title">Variación vs. Mes Anterior</div>
            <div class="kpi-value flex items-baseline gap-2" style="color: <?= $variacion >= 0 ? 'var(--color-exito)' : 'var(--color-peligro)' ?>;">
                <?= $variacion >= 0 ? '+' : '' ?><?= number_format($variacion, 1) ?>%
                <span class="text-sm font-normal text-secundario">(Mes ant: S/ <?= number_format($total_mes_anterior, 2) ?>)</span>
            </div>
        </div>
    </div>

    <div class="card m-0" style="display: flex; flex-direction: column;">
        <div class="card-header border-b border-borde">
            <h2 class="card-titulo">Ventas por Día</h2>
        </div>
        <div class="card-body" style="min-height: 400px;">
            <div style="position: relative; height: 100%; width: 100%;">
                <canvas id="graficoMes"></canvas>
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

    const ctx = document.getElementById("graficoMes").getContext("2d");
    new Chart(ctx, {
        type: "bar",
        data: {
            labels: ' . json_encode($dias_grafico) . ',
            datasets: [{
                label: "Ingresos (S/)",
                data: ' . json_encode(array_values($totales_grafico)) . ',
                backgroundColor: colorPrimario,
                borderRadius: 4,
                hoverBackgroundColor: "#334155" // color un poco más claro al hover
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
