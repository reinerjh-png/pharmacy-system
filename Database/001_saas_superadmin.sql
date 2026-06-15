-- =====================================================
-- MIGRACIÓN 001 — SaaS: Super Admin + Multi-Tenant
-- Archivo: Database/001_saas_superadmin.sql
-- Fecha: 2026-06-15
-- Instrucciones: Ejecutar en phpMyAdmin sobre la BD farmacia_saas
-- =====================================================

USE farmacia_saas;

-- =====================================================
-- PARTE 1: TABLA DE SUPER ADMINISTRADORES
-- El super admin es el dueño del SaaS. Vive FUERA
-- del sistema de roles de farmacia (tabla separada).
-- =====================================================

CREATE TABLE IF NOT EXISTS super_admins (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  nombre        VARCHAR(100)  NOT NULL,
  email         VARCHAR(150)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  activo        TINYINT(1)    DEFAULT 1,
  creado_en     DATETIME      DEFAULT CURRENT_TIMESTAMP,
  ultimo_login  DATETIME      DEFAULT NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Super administrador inicial
-- Contraseña: SuperAdmin2026 (CAMBIAR INMEDIATAMENTE)
-- Hash bcrypt generado con password_hash('SuperAdmin2026', PASSWORD_BCRYPT)
INSERT INTO super_admins (nombre, email, password_hash) VALUES
('Super Administrador', 'superadmin@saas.com',
 '$2y$10$LeYHinsY0GIFibU/FpTeteCtqhSmh4BVWnk8IhYemi8QLn2.kMJU.');

-- =====================================================
-- PARTE 2: TABLA DE FARMACIAS (TENANTS)
-- Cada fila representa un cliente/farmacia del SaaS.
-- =====================================================

CREATE TABLE IF NOT EXISTS farmacias (
  id              INT PRIMARY KEY AUTO_INCREMENT,
  nombre          VARCHAR(150)  NOT NULL,
  slug            VARCHAR(80)   UNIQUE,
  ruc             VARCHAR(20)   DEFAULT NULL,
  telefono        VARCHAR(30)   DEFAULT NULL,
  email_contacto  VARCHAR(150)  DEFAULT NULL,
  direccion       TEXT          DEFAULT NULL,
  activo          TINYINT(1)    DEFAULT 1,
  creado_en       DATETIME      DEFAULT CURRENT_TIMESTAMP,
  creado_por      INT           DEFAULT NULL,
  FOREIGN KEY (creado_por) REFERENCES super_admins(id) ON DELETE SET NULL
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Migrar la farmacia existente como Farmacia #1
-- Los datos se toman del branding actual del sistema
INSERT INTO farmacias (id, nombre, slug, creado_por)
SELECT 1, b.farmacia_nombre, 'farmacia-san-miguel', 1
FROM branding b
WHERE b.activo = 1
ORDER BY b.id ASC
LIMIT 1;

-- Si no hay branding, insertar con valores por defecto
INSERT IGNORE INTO farmacias (id, nombre, slug, creado_por)
VALUES (1, 'Farmacia San Miguel', 'farmacia-san-miguel', 1);

-- =====================================================
-- PARTE 3: AGREGAR farmacia_id A TABLAS EXISTENTES
-- Orden importante: de padre a hijo para respetar FKs.
-- =====================================================

-- ── TABLA: usuarios ──────────────────────────────────
ALTER TABLE usuarios
  ADD COLUMN farmacia_id INT NOT NULL DEFAULT 1
    AFTER rol_id,
  ADD INDEX idx_usuarios_farmacia_id (farmacia_id),
  ADD CONSTRAINT fk_usuarios_farmacia
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE;

-- Migrar todos los usuarios actuales a farmacia 1
UPDATE usuarios SET farmacia_id = 1 WHERE farmacia_id = 1;

-- ── TABLA: productos ─────────────────────────────────
ALTER TABLE productos
  ADD COLUMN farmacia_id INT NOT NULL DEFAULT 1
    AFTER activo,
  ADD INDEX idx_productos_farmacia_id (farmacia_id),
  ADD CONSTRAINT fk_productos_farmacia
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE;

UPDATE productos SET farmacia_id = 1;

-- ── TABLA: proveedores ───────────────────────────────
ALTER TABLE proveedores
  ADD COLUMN farmacia_id INT NOT NULL DEFAULT 1
    AFTER activo,
  ADD INDEX idx_proveedores_farmacia_id (farmacia_id),
  ADD CONSTRAINT fk_proveedores_farmacia
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE;

UPDATE proveedores SET farmacia_id = 1;

-- ── TABLA: compras ───────────────────────────────────
ALTER TABLE compras
  ADD COLUMN farmacia_id INT NOT NULL DEFAULT 1
    AFTER observaciones,
  ADD INDEX idx_compras_farmacia_id (farmacia_id),
  ADD CONSTRAINT fk_compras_farmacia
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE;

UPDATE compras SET farmacia_id = 1;

-- ── TABLA: ventas ────────────────────────────────────
ALTER TABLE ventas
  ADD COLUMN farmacia_id INT NOT NULL DEFAULT 1
    AFTER observaciones,
  ADD INDEX idx_ventas_farmacia_id (farmacia_id),
  ADD CONSTRAINT fk_ventas_farmacia
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE;

UPDATE ventas SET farmacia_id = 1;

-- ── TABLA: recetas ───────────────────────────────────
ALTER TABLE recetas
  ADD COLUMN farmacia_id INT NOT NULL DEFAULT 1
    AFTER observaciones,
  ADD INDEX idx_recetas_farmacia_id (farmacia_id),
  ADD CONSTRAINT fk_recetas_farmacia
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE;

UPDATE recetas SET farmacia_id = 1;

-- ── TABLA: ajustes_stock ─────────────────────────────
ALTER TABLE ajustes_stock
  ADD COLUMN farmacia_id INT NOT NULL DEFAULT 1
    AFTER motivo,
  ADD INDEX idx_ajustes_stock_farmacia_id (farmacia_id),
  ADD CONSTRAINT fk_ajustes_stock_farmacia
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE;

UPDATE ajustes_stock SET farmacia_id = 1;

-- ── TABLA: branding ──────────────────────────────────
ALTER TABLE branding
  ADD COLUMN farmacia_id INT NOT NULL DEFAULT 1
    AFTER id,
  ADD INDEX idx_branding_farmacia_id (farmacia_id),
  ADD CONSTRAINT fk_branding_farmacia
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE;

UPDATE branding SET farmacia_id = 1;

-- =====================================================
-- PARTE 4: LOG DE IMPERSONACIÓN (Super Admin accede
-- temporalmente a una farmacia para soporte técnico)
-- =====================================================

CREATE TABLE IF NOT EXISTS super_admin_impersonaciones (
  id              INT PRIMARY KEY AUTO_INCREMENT,
  super_admin_id  INT NOT NULL,
  farmacia_id     INT NOT NULL,
  inicio          DATETIME DEFAULT CURRENT_TIMESTAMP,
  fin             DATETIME DEFAULT NULL,
  ip_origen       VARCHAR(45) DEFAULT NULL,
  FOREIGN KEY (super_admin_id) REFERENCES super_admins(id),
  FOREIGN KEY (farmacia_id) REFERENCES farmacias(id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =====================================================
-- PARTE 5: ÍNDICES DE OPTIMIZACIÓN ADICIONALES
-- =====================================================

ALTER TABLE `farmacias` ADD INDEX `idx_farmacias_activo` (`activo`);
ALTER TABLE `farmacias` ADD INDEX `idx_farmacias_slug` (`slug`);
ALTER TABLE `super_admins` ADD INDEX `idx_super_admins_email` (`email`);

-- =====================================================
-- FIN DE MIGRACIÓN 001
-- Verificación rápida:
-- SELECT 'farmacias' as tabla, COUNT(*) as registros FROM farmacias
-- UNION ALL SELECT 'super_admins', COUNT(*) FROM super_admins
-- UNION ALL SELECT 'usuarios con farmacia_id', COUNT(*) FROM usuarios WHERE farmacia_id IS NOT NULL;
-- =====================================================
