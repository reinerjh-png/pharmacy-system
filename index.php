<?php
// index.php
require_once __DIR__ . '/auth/session_check.php';
require_once __DIR__ . '/config/db.php';

verificar_sesion();
$rol_id = (int)$_SESSION['rol_id'];

// Redirección según rol (Solo admin ve el dashboard)
if ($rol_id === 2) {
    header('Location: /modules/ventas/nueva_venta.php');
    exit;
} elseif ($rol_id === 3) {
    header('Location: /modules/inventario/lista_productos.php');
    exit;
} elseif ($rol_id !== 1) {
    echo "Rol no autorizado.";
    exit;
}

$pdo = conectar();
$hoy = date('Y-m-d');

// 1. Total ventas del día
$stmtVentas = $pdo->prepare("SELECT SUM(total) as total_vendido, COUNT(id) as numero_ventas FROM ventas WHERE DATE(fecha) = :hoy AND estado = 'completada'");
$stmtVentas->execute([':hoy' => $hoy]);
$datosVentas = $stmtVentas->fetch();
$total_vendido = $datosVentas['total_vendido'] ?? 0;
$numero_ventas = $datosVentas['numero_ventas'] ?? 0;

// 2. Stock crítico
$stmtCritico = $pdo->query("SELECT COUNT(*) FROM inventario WHERE stock_actual <= stock_minimo");
$stock_critico = $stmtCritico->fetchColumn();

// 3. Próximos a vencer
$stmtVencer = $pdo->query("SELECT COUNT(*) FROM inventario WHERE fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$proximos_vencer = $stmtVencer->fetchColumn();
$vence_pronto = $proximos_vencer;

// Semáforo de vencimientos
$stmtRojo = $pdo->query("SELECT COUNT(*) FROM inventario WHERE fecha_vencimiento IS NOT NULL AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
$semaforo_rojo = $stmtRojo->fetchColumn();

$stmtAmarillo = $pdo->query("SELECT COUNT(*) FROM inventario WHERE fecha_vencimiento > DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$semaforo_amarillo = $stmtAmarillo->fetchColumn();

$stmtVerde = $pdo->query("SELECT COUNT(*) FROM inventario WHERE fecha_vencimiento > DATE_ADD(CURDATE(), INTERVAL 30 DAY) OR fecha_vencimiento IS NULL");
$semaforo_verde = $stmtVerde->fetchColumn();

$es_admin = ($rol_id === 1);

// Gráfico: Ventas últimos 7 días — UNA sola query (elimina N+1 de 7 queries)
$stmtGrafico = $pdo->query("
    SELECT DATE(fecha) as dia, SUM(total) as total 
    FROM ventas 
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND estado = 'completada'
    GROUP BY DATE(fecha) 
    ORDER BY dia ASC
");
$ventas_semana = $stmtGrafico->fetchAll(PDO::FETCH_KEY_PAIR); // ['Y-m-d' => total]

// Rellenar días vacíos con PHP — sin queries adicionales
$fechas = [];
$totales_grafico = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha_iter = date('Y-m-d', strtotime("-$i days"));
    $fechas[]         = date('d/m', strtotime($fecha_iter));
    $totales_grafico[] = isset($ventas_semana[$fecha_iter]) ? (float)$ventas_semana[$fecha_iter] : 0;
}


$pagina_titulo = 'Dashboard';
include __DIR__ . '/views/layout/header.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1>Resumen General</h1>
            <p class="page-subtitle">Monitorea el estado actual de tu farmacia</p>
        </div>
        <div class="page-header-actions">
            <a href="modules/ventas/nueva_venta.php" class="btn btn-primario">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nueva Venta
            </a>
            <?php if ($es_admin): ?>
            <a href="modules/reportes/ventas_diarias.php" class="btn btn-secundario">
                Ver Reportes
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPIs Principales -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon kpi-icon-ventas">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Ventas Hoy</div>
                <div class="kpi-valor">S/ <?= number_format($total_vendido, 2) ?></div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon kpi-icon-tx">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                </svg>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Transacciones</div>
                <div class="kpi-valor"><?= $numero_ventas ?></div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon kpi-icon-stock">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Stock Crítico</div>
                <div class="kpi-valor kpi-valor-peligro">
                    <?php if ($stock_critico > 0): ?>
                        <span class="badge badge-peligro badge-pulso" style="font-size: 1rem; padding: 2px 8px;"><?= $stock_critico ?></span>
                    <?php else: ?>
                        0
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon kpi-icon-vence">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Próximos a Vencer</div>
                <div class="kpi-valor kpi-valor-advertencia"><?= $vence_pronto ?></div>
            </div>
        </div>
    </div>

    <!-- NUEVO: Semáforo de Vencimientos -->
    <div class="semaforo-widget">
        <h2 class="semaforo-widget-titulo">Semáforo de Vencimientos (Inventario Activo)</h2>
        <div class="semaforo-grid">
            <a href="modules/inventario/alertas_vencimiento.php" class="semaforo-item semaforo-item-rojo">
                <div class="semaforo-dot semaforo-dot-rojo"></div>
                <div class="semaforo-count semaforo-count-rojo"><?= $semaforo_rojo ?></div>
                <div class="semaforo-label">Vencidos o ≤ 7 días</div>
            </a>
            
            <a href="modules/inventario/alertas_vencimiento.php" class="semaforo-item semaforo-item-amarillo">
                <div class="semaforo-dot semaforo-dot-amarillo"></div>
                <div class="semaforo-count semaforo-count-amarillo"><?= $semaforo_amarillo ?></div>
                <div class="semaforo-label">Vencen en 8 - 30 días</div>
            </a>
            
            <a href="modules/inventario/lista_productos.php" class="semaforo-item semaforo-item-verde">
                <div class="semaforo-dot semaforo-dot-verde"></div>
                <div class="semaforo-count semaforo-count-verde"><?= $semaforo_verde ?></div>
                <div class="semaforo-label">Stock seguro (> 30 días)</div>
            </a>
        </div>
    </div>

    <!-- Gráfico -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-titulo">Ventas de los Últimos 7 Días</h2>
        </div>
        <div class="card-body">
            <?php if (array_sum($totales_grafico) == 0): ?>
                <div class="empty-state">
                    <svg class="empty-state-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    <div class="empty-state-titulo">No hay datos suficientes</div>
                    <div class="empty-state-msg">Registra ventas para visualizar el rendimiento de la semana.</div>
                </div>
            <?php else: ?>
                <div style="position: relative; height: 320px; width: 100%;">
                    <canvas id="graficoVentas"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
if (array_sum($totales_grafico) > 0) {
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById("graficoVentas").getContext("2d");
    
    // Crear gradiente verde para el área
    let gradient = ctx.createLinearGradient(0, 0, 0, 320);
    gradient.addColorStop(0, "rgba(16, 185, 129, 0.2)");
    gradient.addColorStop(1, "rgba(16, 185, 129, 0)");

    new Chart(ctx, {
        type: "line",
        data: {
            labels: ' . json_encode($fechas) . ',
            datasets: [{
                label: "Ingresos Totales (S/)",
                data: ' . json_encode($totales_grafico) . ',
                borderColor: "#10b981",
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: "#ffffff",
                pointBorderColor: "#10b981",
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "#0f172a",
                    titleFont: { size: 13, family: "Inter" },
                    bodyFont: { size: 14, weight: "bold", family: "Inter" },
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return "S/ " + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: { color: "#e2e8f0", drawBorder: false },
                    border: { display: false },
                    ticks: {
                        font: { family: "Inter" },
                        color: "#475569",
                        callback: function(value) { return "S/ " + value; }
                    }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    border: { display: false },
                    ticks: {
                        font: { family: "Inter" },
                        color: "#475569"
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
}
include __DIR__ . '/views/layout/footer.php'; 
?>
