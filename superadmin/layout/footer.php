<?php
// superadmin/layout/footer.php
?>
        </main><!-- /sa-content -->
    </div><!-- /sa-main -->

</div><!-- /sa-app -->

<script>
// Reloj topbar
function saActualizarReloj() {
    const el = document.getElementById('sa-reloj');
    if (el) {
        const ahora = new Date();
        el.textContent = ahora.toLocaleTimeString('es-PE', {
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });
    }
}
saActualizarReloj();
setInterval(saActualizarReloj, 1000);
</script>

<?php if (isset($extra_js)): echo $extra_js; endif; ?>
</body>
</html>
