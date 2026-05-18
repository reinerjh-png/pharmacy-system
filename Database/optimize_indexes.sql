-- ============================================================
-- Script de Optimización de Índices — Farmacia SaaS
-- ============================================================
-- Este script crea índices adicionales en las tablas para mejorar
-- significativamente el rendimiento de las consultas frecuentes,
-- especialmente búsquedas, reportes y generación de dashboard.
-- ============================================================

-- ── TABLA: productos ──
-- Optimización para búsquedas por nombre y código de barras (usado en POS)
ALTER TABLE `productos` ADD INDEX `idx_productos_nombre` (`nombre`);
ALTER TABLE `productos` ADD INDEX `idx_productos_codigo_barras` (`codigo_barras`);
-- Optimización para filtros por categoría en la lista de productos
ALTER TABLE `productos` ADD INDEX `idx_productos_categoria_id` (`categoria_id`);
-- Optimización para filtrado de productos activos
ALTER TABLE `productos` ADD INDEX `idx_productos_activo` (`activo`);

-- ── TABLA: inventario ──
-- Optimización para cálculos de stock y vencimientos (usado en dashboard y reportes)
ALTER TABLE `inventario` ADD INDEX `idx_inventario_producto_id` (`producto_id`);
ALTER TABLE `inventario` ADD INDEX `idx_inventario_fecha_vencimiento` (`fecha_vencimiento`);
ALTER TABLE `inventario` ADD INDEX `idx_inventario_stock_actual` (`stock_actual`);

-- ── TABLA: ventas ──
-- Optimización para el dashboard y reportes de ventas por fecha y estado
ALTER TABLE `ventas` ADD INDEX `idx_ventas_fecha` (`fecha`);
ALTER TABLE `ventas` ADD INDEX `idx_ventas_estado` (`estado`);
-- Optimización para filtrar ventas por cajero/usuario
ALTER TABLE `ventas` ADD INDEX `idx_ventas_usuario_id` (`usuario_id`);
-- Índice compuesto útil para el gráfico del dashboard (fecha + estado)
ALTER TABLE `ventas` ADD INDEX `idx_ventas_fecha_estado` (`fecha`, `estado`);

-- ── TABLA: detalle_ventas ──
-- Optimización para consultas JOIN desde ventas y reportes de productos más vendidos
ALTER TABLE `detalle_ventas` ADD INDEX `idx_detalle_ventas_venta_id` (`venta_id`);
ALTER TABLE `detalle_ventas` ADD INDEX `idx_detalle_ventas_producto_id` (`producto_id`);

-- ── TABLA: usuarios ──
-- Optimización para el login
ALTER TABLE `usuarios` ADD INDEX `idx_usuarios_email` (`email`);

-- ── OPTIMIZACIÓN FINAL (Opcional pero recomendada en producción) ──
-- Reorganiza las tablas para recuperar espacio y actualizar estadísticas
OPTIMIZE TABLE `productos`, `inventario`, `ventas`, `detalle_ventas`, `usuarios`;
