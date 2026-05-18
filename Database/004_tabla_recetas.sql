-- =====================================================
-- Database/004_tabla_recetas.sql
-- Sistema de Farmacia SaaS — R.DEV
-- Módulo de Recetas Despachadas
-- Ejecutar manualmente en phpMyAdmin
-- =====================================================

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
