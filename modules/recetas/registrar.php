<?php
// modules/recetas/registrar.php
require_once __DIR__ . '/../../auth/session_check.php';
require_once __DIR__ . '/../../config/db.php';

verificar_permiso('ventas');
$pdo = conectar();

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_paciente = trim($_POST['nombre_paciente'] ?? '');
    $nombre_medico = trim($_POST['nombre_medico'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $venta_id = !empty($_POST['venta_id']) ? (int)$_POST['venta_id'] : null;
    $payload = json_decode($_POST['receta_payload'] ?? '[]', true);

    if (empty($nombre_paciente)) {
        $error = "El nombre del paciente es obligatorio.";
    } elseif (empty($payload)) {
        $error = "Debe agregar al menos un medicamento a la receta.";
    } else {
        try {
            $pdo->beginTransaction();

            $numero_receta = 'REC-' . date('YmdHis') . '-' . rand(100, 999);

            $stmtR = $pdo->prepare("INSERT INTO recetas (numero_receta, nombre_paciente, nombre_medico, venta_id, usuario_id, observaciones) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtR->execute([
                $numero_receta,
                $nombre_paciente,
                $nombre_medico,
                $venta_id,
                $_SESSION['usuario_id'],
                $observaciones
            ]);
            $receta_id = $pdo->lastInsertId();

            $stmtD = $pdo->prepare("INSERT INTO detalle_recetas (receta_id, producto_id, cantidad) VALUES (?, ?, ?)");
            foreach ($payload as $item) {
                $stmtD->execute([
                    $receta_id,
                    $item['producto_id'],
                    $item['cantidad']
                ]);
            }

            $pdo->commit();
            $exito = "Receta registrada correctamente. Reg. #" . str_pad($receta_id, 5, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al guardar la receta: " . $e->getMessage();
        }
    }
}

$pagina_titulo = 'Registrar Receta Médica';
include __DIR__ . '/../../views/layout/header.php';
?>

<div class="container" style="max-width: 1200px;">
    
    <div class="page-header flex justify-between items-start mb-6">
        <div>
            <a href="lista.php" class="btn btn-ghost" style="padding-left: 0; margin-bottom: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al libro de recetas
            </a>
            <h1 class="text-2xl m-0">Registrar Receta Médica</h1>
            <p class="page-subtitle">Guarda el registro de los medicamentos despachados bajo prescripción.</p>
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
            <div style="flex: 1;"><?= htmlspecialchars($exito) ?></div>
            <a href="lista.php" class="btn btn-sm btn-ghost" style="color: var(--verde-700); background: var(--verde-100);">Ver Libro</a>
        </div>
    <?php endif; ?>

    <div style="display: grid; gap: 24px;" class="receta-container">
        <style>
            @media(min-width: 1024px) {
                .receta-container { grid-template-columns: 1fr 350px !important; }
            }
            .resultado-item:hover { background-color: var(--color-fondo); cursor: pointer; }
        </style>

        <!-- Columna Izquierda: Buscador de medicamentos -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <div class="card m-0">
                <div class="card-header border-b border-borde">
                    <h2 class="card-titulo">Medicamentos Prescritos</h2>
                </div>
                <div class="card-body bg-fondo">
                    <div class="form-group m-0" style="position: relative;">
                        <input type="text" id="inputBusquedaReceta" class="form-control" style="font-size: 1.1rem; padding: 12px 16px;" placeholder="Buscar producto a despachar..." autocomplete="off">
                        <div id="resultadosReceta" style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid var(--color-borde); border-radius: var(--radio-md); box-shadow: var(--sombra-md); z-index: 50; display: none; max-height: 300px; overflow-y: auto;">
                            <!-- Resultados AJAX -->
                        </div>
                    </div>
                </div>
                
                <div class="tabla-contenedor" style="border: none; border-radius: 0 0 var(--radio-lg) var(--radio-lg);">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th style="width: 120px; text-align: center;">Cantidad</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tablaReceta">
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state" style="padding: 30px 20px;">
                                        <div class="empty-state-titulo">Añade medicamentos</div>
                                        <div class="empty-state-msg">Busca y selecciona los medicamentos de la receta.</div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Datos del Paciente -->
        <div>
            <form action="" method="POST" id="formReceta" style="position: sticky; top: 90px;">
                <input type="hidden" name="receta_payload" id="recetaPayload" value="">
                
                <div class="card">
                    <div class="card-header border-b border-borde">
                        <h2 class="card-titulo">Datos de la Receta</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label font-medium">Nombre del Paciente <span class="text-peligro">*</span></label>
                            <input type="text" name="nombre_paciente" class="form-control" required placeholder="Nombres y apellidos completos">
                        </div>

                        <div class="form-group">
                            <label class="form-label font-medium">Médico / Colegiatura</label>
                            <input type="text" name="nombre_medico" class="form-control" placeholder="Ej: Dr. Pérez / CMP 12345">
                        </div>

                        <div class="form-group">
                            <label class="form-label font-medium">Venta Vinculada (ID)</label>
                            <div class="text-xs text-secundario mb-1">Opcional. Si el medicamento fue cobrado por el sistema.</div>
                            <input type="number" name="venta_id" class="form-control" placeholder="Ej: 1045">
                        </div>

                        <div class="form-group m-0">
                            <label class="form-label font-medium">Observaciones Adicionales</label>
                            <textarea name="observaciones" class="form-control" rows="3" placeholder="Anotaciones sobre posología o despacho..."></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" id="btnGuardarReceta" class="btn btn-primario btn-bloque btn-lg" style="height: 52px;" disabled>
                    Guardar Registro de Receta
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    let carritoReceta = [];

    const inputBusqueda = document.getElementById('inputBusquedaReceta');
    const resultadosBox = document.getElementById('resultadosReceta');
    const tablaReceta = document.getElementById('tablaReceta');
    const payloadInput = document.getElementById('recetaPayload');
    const btnGuardar = document.getElementById('btnGuardarReceta');

    // Búsqueda simple (reutilizamos el endpoint existente pero lo adaptamos en JS)
    inputBusqueda.addEventListener('keyup', function(e) {
        const q = e.target.value.trim();
        if(q.length < 2) {
            resultadosBox.style.display = 'none';
            return;
        }

        fetch(`<?= $base_url ?>/api/buscar_producto.php?q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                if(data.length > 0) {
                    let html = '<ul style="list-style:none; padding:0; margin:0;">';
                    data.forEach(p => {
                        html += `
                        <li class="resultado-item" style="padding: 12px 16px; border-bottom: 1px solid var(--color-borde);" onclick="agregarProductoReceta(${p.id}, '${p.nombre}', '${p.codigo_barras}')">
                            <div class="font-medium">${p.nombre}</div>
                            <div class="text-xs text-secundario">Stock: ${p.stock_total}</div>
                        </li>`;
                    });
                    html += '</ul>';
                    resultadosBox.innerHTML = html;
                    resultadosBox.style.display = 'block';
                } else {
                    resultadosBox.innerHTML = '<div style="padding: 12px 16px; color: var(--texto-secundario);">No se encontraron productos.</div>';
                    resultadosBox.style.display = 'block';
                }
            });
    });

    document.addEventListener('click', function(e) {
        if(e.target !== inputBusqueda && e.target !== resultadosBox) {
            resultadosBox.style.display = 'none';
        }
    });

    window.agregarProductoReceta = function(id, nombre, codigo) {
        const existe = carritoReceta.find(i => i.producto_id == id);
        if(existe) {
            existe.cantidad++;
        } else {
            carritoReceta.push({
                producto_id: id,
                nombre: nombre,
                codigo: codigo,
                cantidad: 1
            });
        }
        inputBusqueda.value = '';
        resultadosBox.style.display = 'none';
        renderTabla();
    };

    window.cambiarCantReceta = function(id, cant) {
        const item = carritoReceta.find(i => i.producto_id == id);
        if(item) {
            item.cantidad = parseInt(cant) || 1;
        }
        renderTabla();
    };

    window.eliminarItemReceta = function(id) {
        carritoReceta = carritoReceta.filter(i => i.producto_id != id);
        renderTabla();
    };

    function renderTabla() {
        if(carritoReceta.length === 0) {
            tablaReceta.innerHTML = `
                <tr>
                    <td colspan="3">
                        <div class="empty-state" style="padding: 30px 20px;">
                            <div class="empty-state-titulo">Añade medicamentos</div>
                            <div class="empty-state-msg">Busca y selecciona los medicamentos de la receta.</div>
                        </div>
                    </td>
                </tr>
            `;
            btnGuardar.disabled = true;
            payloadInput.value = '';
            return;
        }

        let html = '';
        carritoReceta.forEach(item => {
            html += `
            <tr>
                <td>
                    <div class="font-medium">${item.nombre}</div>
                </td>
                <td style="text-align: center;">
                    <input type="number" class="form-control form-control-sm" style="text-align: center;" min="1" value="${item.cantidad}" onchange="cambiarCantReceta(${item.producto_id}, this.value)">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-ghost" style="color: var(--color-peligro);" onclick="eliminarItemReceta(${item.producto_id})">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </button>
                </td>
            </tr>`;
        });
        
        tablaReceta.innerHTML = html;
        payloadInput.value = JSON.stringify(carritoReceta);
        btnGuardar.disabled = false;
    }
</script>

<?php include __DIR__ . '/../../views/layout/footer.php'; ?>
