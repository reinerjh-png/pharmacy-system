// assets/js/venta.js
let carrito = [];

function agregarAlCarrito(producto) {
    const existe = carrito.find(p => p.producto_id === producto.producto_id);
    
    if (existe) {
        if (existe.cantidad + 1 > existe.stock) {
            alert('No hay suficiente stock total.');
            return;
        }
        existe.cantidad += 1;
        existe.subtotal = existe.cantidad * existe.precio_venta;
    } else {
        carrito.push({
            producto_id: producto.producto_id,
            nombre: producto.nombre,
            precio_venta: parseFloat(producto.precio_venta),
            cantidad: 1,
            stock: producto.stock,
            subtotal: parseFloat(producto.precio_venta)
        });
    }
    
    renderizarCarrito();
}

function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    renderizarCarrito();
}

function cambiarCantidad(index, nuevaCantidad) {
    const item = carrito[index];
    nuevaCantidad = parseInt(nuevaCantidad);
    
    if (isNaN(nuevaCantidad) || nuevaCantidad <= 0) {
        nuevaCantidad = 1;
    }
    
    if (nuevaCantidad > item.stock) {
        alert('Stock máximo superado.');
        nuevaCantidad = item.stock;
    }
    
    item.cantidad = nuevaCantidad;
    item.subtotal = item.cantidad * item.precio_venta;
    renderizarCarrito();
}

function renderizarCarrito() {
    const tbody = document.getElementById('tablaCarrito');
    const inputPayload = document.getElementById('carritoPayload');
    const resTotal = document.getElementById('resumenTotal');
    const lblTotal = document.getElementById('lblTotalCobro');
    
    tbody.innerHTML = '';
    
    let total = 0;
    
    if (carrito.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:var(--color-texto-secundario); padding:20px;">El carrito está vacío</td></tr>';
        inputPayload.value = '';
        resTotal.textContent = 'S/ 0.00';
        lblTotal.textContent = 'S/ 0.00';
        document.getElementById('btnConfirmarVenta').disabled = true;
        return;
    }
    
    document.getElementById('btnConfirmarVenta').disabled = false;
    
    carrito.forEach((item, index) => {
        total += item.subtotal;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="font-semibold">${item.nombre}</div>
            </td>
            <td>S/ ${item.precio_venta.toFixed(2)}</td>
            <td>
                <input type="number" value="${item.cantidad}" min="1" max="${item.stock}" 
                       onchange="cambiarCantidad(${index}, this.value)" 
                       class="form-control" style="width: 70px; padding: 4px; text-align: center;">
            </td>
            <td class="font-semibold">S/ ${item.subtotal.toFixed(2)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-peligro" onclick="eliminarDelCarrito(${index})" style="padding: 4px 8px;">
                    X
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    resTotal.textContent = `S/ ${total.toFixed(2)}`;
    lblTotal.textContent = `S/ ${total.toFixed(2)}`;
    inputPayload.value = JSON.stringify(carrito);
    
    calcularVuelto();
}

function calcularVuelto() {
    const efectivoInput = document.getElementById('montoEfectivo');
    const tipoPago = document.getElementById('tipoPago').value;
    const vueltoDiv = document.getElementById('vueltoCalculado');
    
    if (tipoPago !== 'efectivo') {
        efectivoInput.disabled = true;
        efectivoInput.value = '';
        vueltoDiv.textContent = 'S/ 0.00';
        return;
    }
    
    efectivoInput.disabled = false;
    
    let total = carrito.reduce((sum, item) => sum + item.subtotal, 0);
    let pagado = parseFloat(efectivoInput.value) || 0;
    
    let vuelto = pagado - total;
    if (vuelto < 0) vuelto = 0;
    
    vueltoDiv.textContent = `S/ ${vuelto.toFixed(2)}`;
}

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById('tipoPago')?.addEventListener('change', calcularVuelto);
    document.getElementById('montoEfectivo')?.addEventListener('input', calcularVuelto);
});
