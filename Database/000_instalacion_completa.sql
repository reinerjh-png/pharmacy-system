-- =====================================================
-- INSTALACIÓN COMPLETA — Sistema de Farmacia SaaS
-- Archivo unificado para phpMyAdmin
-- Fecha: 2026-06-15
-- =====================================================

-- -----------------------------------------------------
-- PARTE 1: CREACIÓN DE BASE DE DATOS Y TABLAS BASE
-- -----------------------------------------------------
CREATE DATABASE IF NOT EXISTS farmacia_saas
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE farmacia_saas;

-- Roles de usuario
CREATE TABLE IF NOT EXISTS roles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  descripcion TEXT
) ENGINE=InnoDB;

-- Usuarios del sistema
CREATE TABLE IF NOT EXISTS usuarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol_id INT NOT NULL,
  activo TINYINT(1) DEFAULT 1,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- Categorías de productos (antibióticos, analgésicos, etc.)
CREATE TABLE IF NOT EXISTS categorias (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL UNIQUE,
  descripcion TEXT
) ENGINE=InnoDB;

-- Laboratorios / fabricantes
CREATE TABLE IF NOT EXISTS laboratorios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(150) NOT NULL,
  pais VARCHAR(80),
  telefono VARCHAR(20),
  email VARCHAR(150)
) ENGINE=InnoDB;

-- Proveedores (distribuidoras)
CREATE TABLE IF NOT EXISTS proveedores (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(150) NOT NULL,
  ruc VARCHAR(20),
  telefono VARCHAR(20),
  email VARCHAR(150),
  direccion TEXT,
  activo TINYINT(1) DEFAULT 1,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Catálogo de productos
CREATE TABLE IF NOT EXISTS productos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(200) NOT NULL,
  nombre_generico VARCHAR(200),
  codigo_barras VARCHAR(100) UNIQUE,
  categoria_id INT,
  laboratorio_id INT,
  unidad_medida VARCHAR(50) DEFAULT 'unidad',
  requiere_receta TINYINT(1) DEFAULT 0,
  descripcion TEXT,
  activo TINYINT(1) DEFAULT 1,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id),
  FOREIGN KEY (laboratorio_id) REFERENCES laboratorios(id)
) ENGINE=InnoDB;

-- Inventario: lotes y stock por producto
-- Un producto puede tener múltiples lotes con distintas fechas de vencimiento
CREATE TABLE IF NOT EXISTS inventario (
  id INT PRIMARY KEY AUTO_INCREMENT,
  producto_id INT NOT NULL,
  lote VARCHAR(100),
  fecha_vencimiento DATE,
  stock_actual INT NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 5,
  precio_compra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  precio_venta DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  proveedor_id INT,
  creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id),
  FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
) ENGINE=InnoDB;

-- Compras a proveedores (entrada de stock)
CREATE TABLE IF NOT EXISTS compras (
  id INT PRIMARY KEY AUTO_INCREMENT,
  proveedor_id INT NOT NULL,
  usuario_id INT NOT NULL,
  numero_factura VARCHAR(100),
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  observaciones TEXT,
  FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- Detalle de cada compra
CREATE TABLE IF NOT EXISTS detalle_compras (
  id INT PRIMARY KEY AUTO_INCREMENT,
  compra_id INT NOT NULL,
  producto_id INT NOT NULL,
  inventario_id INT,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (compra_id) REFERENCES compras(id),
  FOREIGN KEY (producto_id) REFERENCES productos(id),
  FOREIGN KEY (inventario_id) REFERENCES inventario(id)
) ENGINE=InnoDB;

-- Ventas (cada transacción en caja)
CREATE TABLE IF NOT EXISTS ventas (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  descuento DECIMAL(10,2) DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tipo_pago ENUM('efectivo','tarjeta','mixto') DEFAULT 'efectivo',
  monto_efectivo DECIMAL(10,2) DEFAULT 0.00,
  monto_tarjeta DECIMAL(10,2) DEFAULT 0.00,
  vuelto DECIMAL(10,2) DEFAULT 0.00,
  estado ENUM('completada','anulada') DEFAULT 'completada',
  observaciones TEXT,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- Detalle de cada venta (productos vendidos)
CREATE TABLE IF NOT EXISTS detalle_ventas (
  id INT PRIMARY KEY AUTO_INCREMENT,
  venta_id INT NOT NULL,
  producto_id INT NOT NULL,
  inventario_id INT NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (venta_id) REFERENCES ventas(id),
  FOREIGN KEY (producto_id) REFERENCES productos(id),
  FOREIGN KEY (inventario_id) REFERENCES inventario(id)
) ENGINE=InnoDB;

-- Log de ajustes de stock (pérdidas, devoluciones, correcciones)
CREATE TABLE IF NOT EXISTS ajustes_stock (
  id INT PRIMARY KEY AUTO_INCREMENT,
  inventario_id INT NOT NULL,
  usuario_id INT NOT NULL,
  tipo ENUM('entrada','salida','correccion') NOT NULL,
  cantidad INT NOT NULL,
  motivo TEXT,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (inventario_id) REFERENCES inventario(id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- PARTE 2: CREACIÓN DE TABLAS DE MÓDULOS (Recetas)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS recetas (
  id INT PRIMARY KEY AUTO_INCREMENT,
  numero_receta VARCHAR(100),
  nombre_paciente VARCHAR(200) NOT NULL,
  nombre_medico VARCHAR(200),
  venta_id INT,                          -- vinculada a una venta existente (opcional)
  usuario_id INT NOT NULL,               -- quien despachó
  observaciones TEXT,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (venta_id) REFERENCES ventas(id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS detalle_recetas (
  id INT PRIMARY KEY AUTO_INCREMENT,
  receta_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  FOREIGN KEY (receta_id) REFERENCES recetas(id),
  FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- PARTE 3: DATOS INICIALES Y DE EJEMPLO
-- -----------------------------------------------------
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

-- -----------------------------------------------------
-- PARTE 4: OPTIMIZACIÓN DE ÍNDICES DE BASE DE DATOS
-- -----------------------------------------------------
-- ── TABLA: productos ──
ALTER TABLE `productos` ADD INDEX `idx_productos_nombre` (`nombre`);
ALTER TABLE `productos` ADD INDEX `idx_productos_codigo_barras` (`codigo_barras`);
ALTER TABLE `productos` ADD INDEX `idx_productos_categoria_id` (`categoria_id`);
ALTER TABLE `productos` ADD INDEX `idx_productos_activo` (`activo`);

-- ── TABLA: inventario ──
ALTER TABLE `inventario` ADD INDEX `idx_inventario_producto_id` (`producto_id`);
ALTER TABLE `inventario` ADD INDEX `idx_inventario_fecha_vencimiento` (`fecha_vencimiento`);
ALTER TABLE `inventario` ADD INDEX `idx_inventario_stock_actual` (`stock_actual`);

-- ── TABLA: ventas ──
ALTER TABLE `ventas` ADD INDEX `idx_ventas_fecha` (`fecha`);
ALTER TABLE `ventas` ADD INDEX `idx_ventas_estado` (`estado`);
ALTER TABLE `ventas` ADD INDEX `idx_ventas_usuario_id` (`usuario_id`);
ALTER TABLE `ventas` ADD INDEX `idx_ventas_fecha_estado` (`fecha`, `estado`);

-- ── TABLA: detalle_ventas ──
ALTER TABLE `detalle_ventas` ADD INDEX `idx_detalle_ventas_venta_id` (`venta_id`);
ALTER TABLE `detalle_ventas` ADD INDEX `idx_detalle_ventas_producto_id` (`producto_id`);

-- ── TABLA: usuarios ──
ALTER TABLE `usuarios` ADD INDEX `idx_usuarios_email` (`email`);

-- ── OPTIMIZACIÓN FINAL ──
OPTIMIZE TABLE `productos`, `inventario`, `ventas`, `detalle_ventas`, `usuarios`;

-- =====================================================
-- MÓDULO DE BRANDING E IDENTIDAD (Multi-Tenant)
-- =====================================================
-- Tabla de configuración de branding por farmacia.
-- Cada farmacia (tenant) tiene su propia fila en esta tabla.
-- La relación con `farmacias` (farmacia_id) se establece
-- en la migración 001_saas_superadmin.sql. Aquí se crea
-- la estructura base para que el ALTER TABLE funcione.

CREATE TABLE IF NOT EXISTS branding (
  id                        INT          PRIMARY KEY AUTO_INCREMENT,
  farmacia_id               INT          NOT NULL DEFAULT 1,
  farmacia_nombre           VARCHAR(150) NOT NULL DEFAULT 'Mi Farmacia',
  farmacia_slogan           VARCHAR(255)          DEFAULT 'Sistema de Gestión',
  farmacia_color_primario   VARCHAR(7)   NOT NULL DEFAULT '#059669',
  farmacia_color_secundario VARCHAR(7)   NOT NULL DEFAULT '#10b981',
  farmacia_logo_url         VARCHAR(500)          DEFAULT NULL,
  farmacia_direccion        VARCHAR(300)          DEFAULT NULL,
  farmacia_telefono         VARCHAR(30)           DEFAULT NULL,
  farmacia_ruc              VARCHAR(20)           DEFAULT NULL,
  activo                    TINYINT(1)            DEFAULT 1,
  actualizado_en            DATETIME              DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  actualizado_por           INT                   DEFAULT NULL,
  INDEX idx_branding_farmacia_id (farmacia_id),
  FOREIGN KEY (actualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
  -- NOTA: La FK a farmacias(id) se añade en 001_saas_superadmin.sql
  -- para mantener el orden de creación de tablas.
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- NOTA: El INSERT inicial del branding se realiza en
-- 001_saas_superadmin.sql, una vez que la farmacia #1
-- ha sido creada y se conoce su nombre real.
