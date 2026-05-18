-- =====================================================
-- Database/002_datos_iniciales.sql
-- Sistema de Farmacia SaaS — R.DEV
-- Ejecutar DESPUÉS de 001_crear_tablas_base.sql
-- Fecha: 2026-05-18
-- =====================================================

USE farmacia_saas;

-- Roles
INSERT INTO roles (id, nombre, descripcion) VALUES
(1, 'admin', 'Acceso total al sistema'),
(2, 'cajero', 'Gestión de ventas y caja'),
(3, 'almacenero', 'Gestión de inventario y compras');

-- Usuario administrador inicial
-- Contraseña: Admin1234 (cambiar en primer login)
INSERT INTO usuarios (nombre, email, password_hash, rol_id) VALUES
('Administrador', 'admin@farmacia.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Categorías de medicamentos
INSERT INTO categorias (nombre) VALUES
('Analgésicos'),
('Antibióticos'),
('Antihistamínicos'),
('Vitaminas y suplementos'),
('Antihipertensivos'),
('Antidiabéticos'),
('Antiinflamatorios'),
('Antiparasitarios');

-- Laboratorios
INSERT INTO laboratorios (nombre, pais) VALUES
('Laboratorio Chile', 'Chile'),
('Medifarma', 'Perú'),
('Roemmers', 'Argentina'),
('Genfar', 'Colombia'),
('Bayer', 'Alemania');

-- Proveedor de ejemplo
INSERT INTO proveedores (nombre, ruc, telefono) VALUES
('Distribuidora Farma Perú SAC', '20123456789', '01-4441234');

-- Productos de ejemplo con inventario inicial
INSERT INTO productos (nombre, nombre_generico, codigo_barras, categoria_id, laboratorio_id, unidad_medida, requiere_receta) VALUES
('Paracetamol 500mg', 'Paracetamol', '7750000001', 1, 2, 'caja', 0),
('Amoxicilina 500mg', 'Amoxicilina', '7750000002', 2, 2, 'caja', 1),
('Ibuprofeno 400mg', 'Ibuprofeno', '7750000003', 7, 3, 'caja', 0),
('Loratadina 10mg', 'Loratadina', '7750000004', 3, 4, 'caja', 0),
('Vitamina C 500mg', 'Ácido ascórbico', '7750000005', 4, 5, 'frasco', 0),
('Enalapril 10mg', 'Enalapril', '7750000006', 5, 1, 'caja', 1),
('Metformina 850mg', 'Metformina', '7750000007', 6, 2, 'caja', 1),
('Albendazol 200mg', 'Albendazol', '7750000008', 8, 3, 'caja', 0),
('Omeprazol 20mg', 'Omeprazol', '7750000009', 1, 4, 'caja', 0),
('Diclofenaco 50mg', 'Diclofenaco', '7750000010', 7, 5, 'caja', 0);

-- Inventario inicial (lotes con stock)
INSERT INTO inventario (producto_id, lote, fecha_vencimiento, stock_actual, stock_minimo, precio_compra, precio_venta, proveedor_id) VALUES
(1, 'LOT-2026-001', '2027-06-15', 120, 10, 3.50, 5.00, 1),
(2, 'LOT-2026-002', '2027-03-20', 80, 10, 8.00, 12.50, 1),
(3, 'LOT-2026-003', '2027-08-10', 95, 10, 4.00, 6.50, 1),
(4, 'LOT-2026-004', '2027-04-25', 60, 8, 2.50, 4.00, 1),
(5, 'LOT-2026-005', '2027-12-01', 45, 5, 12.00, 18.00, 1),
(6, 'LOT-2026-006', '2026-07-15', 30, 10, 5.00, 8.50, 1),
(7, 'LOT-2026-007', '2027-01-30', 55, 10, 6.50, 10.00, 1),
(8, 'LOT-2026-008', '2027-09-05', 40, 5, 3.00, 5.50, 1),
(9, 'LOT-2026-009', '2026-06-20', 3, 10, 4.50, 7.00, 1),
(10, 'LOT-2026-010', '2027-05-15', 70, 10, 3.80, 6.00, 1);
