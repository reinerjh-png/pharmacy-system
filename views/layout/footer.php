<?php
// views/layout/footer.php
?>
        </main> <!-- /main-content -->
        
        <!-- Bottom Nav solo para móvil -->
        <?php include 'bottom_nav.php'; ?>
        
    </div> <!-- /app-container -->

    <!-- Sidebar Backdrop for Mobile -->
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

    <!-- Script Global: Reloj Topbar -->
    <script>
    function actualizarReloj() {
        const elementoReloj = document.getElementById('reloj-topbar');
        if (elementoReloj) {
            const ahora = new Date();
            const opciones = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            elementoReloj.textContent = ahora.toLocaleTimeString('es-PE', opciones);
        }
    }
    
    // Iniciar reloj
    actualizarReloj();
    setInterval(actualizarReloj, 1000);

    // Mobile Sidebar Toggle Logic
    const btnMenu = document.getElementById('btn-menu-mobile');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    if (btnMenu && sidebar && backdrop) {
        function toggleMenu() {
            sidebar.classList.toggle('sidebar-open');
            backdrop.classList.toggle('show');
            document.body.style.overflow = sidebar.classList.contains('sidebar-open') ? 'hidden' : '';
        }

        btnMenu.addEventListener('click', toggleMenu);
        backdrop.addEventListener('click', toggleMenu);
    }
    </script>

    <!-- Scripts Específicos de Página -->
    <?php if (isset($extra_js)): echo $extra_js; endif; ?>
</body>
</html>
