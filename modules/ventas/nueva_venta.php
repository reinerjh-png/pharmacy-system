<?php
// modules/ventas/nueva_venta.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('ventas');
$pdo = conectar();

$error = '';
$exito = '';
$comprobante_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode($_POST['carrito_payload'] ?? '[]', true);
    $tipo_pago = $_POST['tipo_pago'] ?? 'efectivo';
    $monto_efectivo = (float)($_POST['monto_efectivo'] ?? 0);
    
    if (empty($payload)) {
        $error = "El carrito está vacío.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $subtotal_venta = 0;
            
            // Calcular total real
            foreach ($payload as $item) {
                $subtotal_venta += ((float)$item['precio_venta'] * (int)$item['cantidad']);
            }
            
            // Insertar Cabecera de Venta
            $stmtVenta = $pdo->prepare("INSERT INTO ventas (usuario_id, subtotal, total, tipo_pago, monto_efectivo, estado) VALUES (?, ?, ?, ?, ?, 'completada')");
            $stmtVenta->execute([$_SESSION['usuario_id'], $subtotal_venta, $subtotal_venta, $tipo_pago, $monto_efectivo]);
            $venta_id = $pdo->lastInsertId();
            
            // Procesar cada producto con regla FIFO
            foreach ($payload as $item) {
                $p_id = (int)$item['producto_id'];
                $cant_requerida = (int)$item['cantidad'];
                $precio_uni = (float)$item['precio_venta'];
                
                // Buscar lotes de más antiguo a más nuevo
                $stmtLotes = $pdo->prepare("SELECT id, stock_actual FROM inventario WHERE producto_id = ? AND stock_actual > 0 ORDER BY fecha_vencimiento ASC FOR UPDATE");
                $stmtLotes->execute([$p_id]);
                $lotes = $stmtLotes->fetchAll();
                
                $cant_restante = $cant_requerida;
                
                foreach ($lotes as $lote) {
                    if ($cant_restante <= 0) break;
                    
                    $stock_lote = (int)$lote['stock_actual'];
                    $tomar = min($stock_lote, $cant_restante);
                    
                    // Actualizar inventario
                    $pdo->prepare("UPDATE inventario SET stock_actual = stock_actual - ? WHERE id = ?")->execute([$tomar, $lote['id']]);
                    
                    // Insertar detalle de venta (vinculando al lote exacto)
                    $subtotal_detalle = $tomar * $precio_uni;
                    $pdo->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, inventario_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$venta_id, $p_id, $lote['id'], $tomar, $precio_uni, $subtotal_detalle]);
                        
                    $cant_restante -= $tomar;
                }
                
                if ($cant_restante > 0) {
                    throw new Exception("Fallo: Stock insuficiente para " . $item['nombre']);
                }
            }
            
            $pdo->commit();
            $exito = "Venta registrada con éxito. Ticket #" . str_pad($venta_id, 6, '0', STR_PAD_LEFT);
            $comprobante_id = $venta_id;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

$pagina_titulo = 'Punto de Venta';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1440px;">
    
    <div class="page-header">
        <div>
            <h1>Punto de Venta</h1>
            <p class="page-subtitle">Procesa las ventas y gestiona el carrito de clientes.</p>
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
            <div style="flex: 1;">
                <?= htmlspecialchars($exito) ?>
            </div>
            <a href="historial.php" class="btn btn-sm btn-ghost" style="color: var(--verde-700); background: var(--verde-100);">Ver Historial</a>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr; gap: 24px; align-items: start;" class="pos-container">
        
        <style>
            @media(min-width: 1024px) {
                .pos-container { grid-template-columns: 65% 1fr !important; }
            }
            .resultado-item:hover { background-color: var(--color-fondo); }
            
            /* Buscador mejorado */
            #inputBusqueda {
                font-size: 1.1rem;
                padding: 14px 16px 14px 44px;
                border-radius: var(--radio-lg);
                border: 2px solid var(--color-borde);
                transition: all var(--transicion-rapida);
            }
            #inputBusqueda:focus {
                border-color: var(--color-primario);
                box-shadow: 0 0 0 4px var(--color-primario-claro);
            }
            .search-icon-pos {
                position: absolute;
                left: 16px;
                top: 50%;
                transform: translateY(-50%);
                color: var(--texto-terciario);
                width: 20px;
                height: 20px;
            }
            
            /* Ajustes tabla POS */
            .tabla-pos th {
                background: var(--bg-card);
                border-bottom: 2px solid var(--color-borde);
            }
            .tabla-pos td {
                vertical-align: middle;
            }
        </style>

        <!-- COLUMNA IZQUIERDA: Búsqueda y Carrito -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Buscador -->
            <div class="card m-0" id="contenedorBuscador">
                <div class="card-body">
                    <div class="form-group m-0">
                        <div style="position: relative;">
                            <svg class="search-icon-pos" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <input type="text" id="inputBusqueda" class="form-control" placeholder="Escanea el código de barras o busca por nombre..." autocomplete="off" onkeyup="buscarProducto()" autofocus>
                            <div id="resultadosBusqueda" style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid var(--color-borde); border-radius: var(--radio-lg); box-shadow: var(--sombra-lg); z-index: 50; display: none; max-height: 350px; overflow-y: auto; margin-top: 8px;">
                                <!-- Resultados AJAX -->
                            </div>
                        </div>
                        <div class="text-xs text-secundario mt-2 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                            Usa el lector de código de barras para añadir productos automáticamente.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carrito -->
            <div class="card m-0" style="flex: 1;">
                <div class="card-header border-b border-borde">
                    <h2 class="card-titulo flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; color: var(--texto-secundario);">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                        </svg>
                        Lista de Compra
                    </h2>
                </div>
                <div class="tabla-contenedor" style="border: none; border-radius: 0 0 var(--radio-lg) var(--radio-lg);">
                    <table class="tabla tabla-pos">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio Uni.</th>
                                <th style="width: 140px; text-align: center;">Cantidad</th>
                                <th style="text-align: right;">Subtotal</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tablaCarrito">
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state" style="padding: 40px 20px;">
                                        <svg class="empty-state-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                        </svg>
                                        <div class="empty-state-titulo">El carrito está vacío</div>
                                        <div class="empty-state-msg">Busca productos arriba para agregarlos a la venta.</div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

            <!-- COLUMNA DERECHA: Resumen y Cobro -->
        <div>
            <form action="" method="POST" id="formVenta" style="position: sticky; top: 90px;">
                <input type="hidden" name="carrito_payload" id="carritoPayload" value="">
                
                <div class="card">
                    <div class="card-header border-b border-borde bg-fondo">
                        <h2 class="card-titulo">Resumen de Pago</h2>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; justify-content: space-between; font-size: 1.1rem; margin-bottom: 12px; color: var(--texto-secundario);">
                            <span>Subtotal</span>
                            <span class="font-medium" id="resumenTotal" style="font-variant-numeric: tabular-nums;">S/ 0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 1.1rem; margin-bottom: 16px; color: var(--texto-secundario);">
                            <span>Descuentos</span>
                            <span class="font-medium">S/ 0.00</span>
                        </div>
                        <hr style="border: 0; border-top: 1px dashed var(--color-borde); margin: 16px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                            <span class="font-medium text-lg">Total a Pagar</span>
                            <span class="font-bold text-primario" id="lblTotalCobro" style="font-size: 2rem; line-height: 1; font-variant-numeric: tabular-nums;">S/ 0.00</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label font-medium">Método de Pago</label>
                            <select name="tipo_pago" id="tipoPago" class="form-control" style="font-size: 1.1rem; padding: 12px;">
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta (Débito/Crédito)</option>
                                <option value="mixto">Billetera Digital (Yape/Plin)</option>
                            </select>
                        </div>

                        <div class="form-group m-0" id="grupoEfectivo">
                            <label class="form-label font-medium">Efectivo Recibido (S/)</label>
                            <div class="input-icon-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V5.942c0-.754-.726-1.294-1.453-1.096V4.846M3.375 18v-4.5m17.25 4.5v-4.5m-17.25-3h17.25M12 15.75h.008v.008H12v-.008zM12 12h.008v.008H12V12z" />
                                </svg>
                                <input type="number" step="0.01" name="monto_efectivo" id="montoEfectivo" class="form-control" style="font-size: 1.25rem; padding: 14px; font-weight: 600; font-variant-numeric: tabular-nums;" placeholder="0.00">
                            </div>
                        </div>

                        <div id="grupoVuelto" style="display: flex; justify-content: space-between; font-size: 1.1rem; margin-top: 24px; align-items: center; padding-top: 16px; border-top: 1px solid var(--color-borde);">
                            <span class="text-secundario font-medium">Vuelto</span>
                            <span class="font-bold text-2xl text-exito" id="vueltoCalculado" style="font-variant-numeric: tabular-nums;">S/ 0.00</span>
                        </div>
                    </div>
                </div>

                <button type="submit" id="btnConfirmarVenta" class="btn btn-primario btn-bloque btn-lg" style="height: 56px; font-size: 1.1rem; box-shadow: var(--sombra-md);" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Procesar Pago y Facturar
                </button>
            </form>
        </div>
        
    </div>
</div>

<?php 
$extra_js = '
<script src="' . $base_url . '/assets/js/busqueda.js"></script>
<script src="' . $base_url . '/assets/js/venta.js"></script>
<script>
    // Pequeño script para ocultar el campo de efectivo si no es tipo pago efectivo
    document.getElementById("tipoPago").addEventListener("change", function(e) {
        const isEfectivo = e.target.value === "efectivo" || e.target.value === "mixto";
        document.getElementById("montoEfectivo").disabled = !isEfectivo;
        if (!isEfectivo) {
            document.getElementById("montoEfectivo").value = "";
            document.getElementById("vueltoCalculado").textContent = "S/ 0.00";
        }
    });
</script>
';
include __DIR__ . '/../../views/layout/footer.php'; 
?>
