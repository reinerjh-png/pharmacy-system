// assets/js/busqueda.js
let debounceTimer;

function buscarProducto() {
    const input = document.getElementById('inputBusqueda');
    const q = input.value.trim();
    const resultadosDiv = document.getElementById('resultadosBusqueda');

    if (q.length < 2) {
        resultadosDiv.innerHTML = '';
        resultadosDiv.style.display = 'none';
        return;
    }

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetch(`/api/buscar_producto.php?q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                resultadosDiv.innerHTML = '';
                if (data.length === 0) {
                    resultadosDiv.innerHTML = '<div style="padding: 12px; color: var(--color-texto-secundario);">No hay resultados o sin stock</div>';
                } else {
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'resultado-item';
                        div.style.padding = '12px 16px';
                        div.style.cursor = 'pointer';
                        div.style.borderBottom = '1px solid var(--color-borde)';
                        div.style.display = 'flex';
                        div.style.justifyContent = 'space-between';
                        div.style.alignItems = 'center';
                        
                        let recetaBadge = item.requiere_receta == 1 ? '<span class="badge badge-peligro" style="font-size:0.6rem;">Receta</span>' : '';
                        
                        div.innerHTML = `
                            <div>
                                <div class="font-semibold">${item.nombre} ${recetaBadge}</div>
                                <div class="text-xs text-secundario">Stock total: ${item.stock}</div>
                            </div>
                            <div class="font-semibold text-primario">
                                S/ ${parseFloat(item.precio_venta).toFixed(2)}
                            </div>
                        `;
                        
                        div.addEventListener('click', () => {
                            agregarAlCarrito(item);
                            input.value = '';
                            resultadosDiv.innerHTML = '';
                            resultadosDiv.style.display = 'none';
                        });

                        resultadosDiv.appendChild(div);
                    });
                }
                resultadosDiv.style.display = 'block';
            })
            .catch(err => console.error("Error en búsqueda AJAX:", err));
    }, 300);
}

document.addEventListener('click', function(e) {
    const buscador = document.getElementById('contenedorBuscador');
    if (buscador && !buscador.contains(e.target)) {
        const res = document.getElementById('resultadosBusqueda');
        if (res) res.style.display = 'none';
    }
});
