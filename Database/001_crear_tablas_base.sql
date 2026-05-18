-- =====================================================
-- Database/001_crear_tablas_base.sql
-- Sistema de Farmacia SaaS — R.DEV
-- Ejecutar manualmente en phpMyAdmin
-- Fecha: 2026-05-18
-- =====================================================

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
