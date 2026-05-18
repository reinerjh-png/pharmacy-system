<div align="center">
  
  # 💊 NexaPharm — Sistema de Gestión Farmacéutica SaaS
  
  **La evolución del control farmacéutico.** <br>
  Una plataforma de grado empresarial, diseñada con una arquitectura de alta disponibilidad, seguridad avanzada y una interfaz de usuario hiper-optimizada.

  [![Versión](https://img.shields.io/badge/Versión-1.0.0-059669?style=for-the-badge&logo=appveyor)](https://github.com/)
  [![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
  [![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com/)
  [![UI/UX](https://img.shields.io/badge/UI/UX-Premium-10b981?style=for-the-badge)](https://github.com/)
  
</div>

---

## 🚀 Visión General

**NexaPharm** (Farmacia SaaS) no es solo un sistema de punto de venta, es una suite completa de control clínico y comercial. Construida desde cero con **PHP Vanilla y PDO**, evita la sobrecarga de frameworks pesados garantizando tiempos de respuesta en milisegundos, ideal para entornos de alta concurrencia. Su interfaz *Mobile-First* con diseño *Glassmorphism* y tipografía *Inter* ofrece una experiencia de usuario fluida, intuitiva y visualmente impactante.

---

## ⚡ Características Principales

### 🛒 Punto de Venta (POS) Ultrarrápido
* **Búsqueda Dinámica AJAX:** Encuentra productos al instante por nombre, código de barras o principio activo sin recargar la página.
* **Integración con Lector de Barras:** Añade productos al carrito con un solo escaneo.
* **Lógica FIFO (First In, First Out):** El sistema despacha automáticamente los lotes que vencen primero, optimizando la rotación del inventario y reduciendo pérdidas.
* **Cálculo de Vueltos y Multi-pago:** Soporta pagos en efectivo, tarjeta o mixtos.

### 📦 Gestión Inteligente de Inventario y Lotes
* **Trazabilidad por Lotes:** Control absoluto sobre cada lote ingresado, con fechas de vencimiento y precios de compra individuales.
* **Semáforo de Vencimientos:** Algoritmo predictivo que clasifica los productos en la interfaz (Verde, Amarillo, Rojo) según su proximidad de caducidad.
* **Alertas de Stock Crítico:** Notificaciones automáticas cuando el inventario de un producto desciende por debajo de su margen de seguridad.
* **Historial de Ajustes:** Registro inmutable de entradas, salidas y correcciones manuales para auditorías.

### 📝 Control de Recetas Médicas
* **Trazabilidad Clínica:** Vinculación estricta de recetas médicas a las ventas.
* **Registro de Facultativos:** Control del médico prescriptor, nombre del paciente y despachador.
* **Medicamentos Restringidos:** Bloqueo de venta para medicamentos que requieren receta obligatoria hasta que se registre la información clínica.

### 📊 Dashboard y Analítica Avanzada
* **Métricas en Tiempo Real:** Ingresos del día, número de transacciones, alertas de stock y caducidad instantáneas.
* **Gráficos Dinámicos:** Visualización de la curva de ventas de los últimos 7 días.
* **Reportes Especializados:**
  * Ventas diarias y mensuales.
  * Rotación de productos.
  * Productos más vendidos (Top Sellers).
  * Reporte de stock crítico exportable y optimizado para impresión térmica/A4.

### 🔐 Seguridad de Grado Bancario
* **RBAC (Control de Acceso Basado en Roles):** Tres niveles de acceso estrictos (`Admin`, `Cajero`, `Almacenero`) con ruteo protegido.
* **Protección CSRF y XSS:** Tokens criptográficos de un solo uso en cada formulario y renderizado escapado de datos.
* **Prevención de Inyecciones SQL:** 100% de consultas implementadas usando *Prepared Statements* (PDO).
* **Anti-Session Fixation:** Regeneración automática del ID de sesión tras validación exitosa.
* **Políticas de Seguridad HTTP:** Cabeceras estrictas (`X-Frame-Options`, `X-XSS-Protection`, `nosniff`).

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología |
| :--- | :--- |
| **Backend** | PHP 8.2+ (Vanilla, Arquitectura Modular) |
| **Base de Datos** | MySQL 8.0+ / MariaDB (Relacional, Índices Optimizados) |
| **Frontend UI** | HTML5 Semántico, CSS3 Custom Properties (Variables) |
| **Interactividad** | Vanilla JavaScript (ES6+), Fetch API (AJAX) |
| **Iconografía** | Heroicons SVG |
| **Tipografía** | Inter (Google Fonts) |

---

## 📦 Instalación y Despliegue

El sistema está preparado para ser desplegado en servidores compartidos (Apache/CPanel) o VPS (Nginx/Apache).

### Requisitos Previos
* Servidor Web (Apache recomendado para aprovechar `.htaccess`).
* PHP 8.1 o superior (con extensión PDO_MySQL habilitada).
* MySQL 5.7+ o MariaDB 10.3+.

### Pasos de Instalación

1. **Clonar o subir los archivos** a la carpeta pública del servidor (ej: `htdocs/sys-farmacia` o `public_html/`).
2. **Base de Datos:**
   * Abre phpMyAdmin o tu cliente de base de datos.
   * Crea una base de datos.
   * Importa el archivo maestro **`Database/000_instalacion_completa.sql`**. Este único archivo creará las tablas, insertará datos de prueba, usuarios y optimizará los índices automáticamente.
3. **Configuración:**
   * El sistema está configurado para leer variables de entorno. Puedes establecerlas en tu servidor o directamente en el archivo `config/db.php`:
     ```php
     define('DB_HOST', 'tu_host');
     define('DB_NAME', 'tu_base_de_datos');
     define('DB_USER', 'tu_usuario');
     define('DB_PASS', 'tu_contraseña');
     ```
4. **Acceso Inicial:**
   * **URL:** `http://tu-dominio.com/sys-farmacia/`
   * **Email:** `admin@farmacia.com`
   * **Contraseña:** `Admin1234`

> [!WARNING]
> Recuerda cambiar la contraseña del usuario administrador inmediatamente después del primer inicio de sesión.

---

## 💎 Diseño de Interfaz (UI/UX)

La plataforma utiliza una filosofía de diseño utilitario-premium:
- **Esquema de Colores:** Basado en una paleta monocromática de verdes profesionales (`#064e3b` a `#d1fae5`) que transmiten salud, seguridad y limpieza.
- **Micro-interacciones:** Retroalimentación instantánea en botones, *hover states* y validación de formularios mediante animaciones suaves.
- **Totalmente Responsivo:** Navegación inferior tipo App (*Bottom Navigation*) en smartphones, y menú lateral completo (*Sidebar*) en pantallas de escritorio. Menú hamburguesa elegante para dispositivos móviles.

---

<div align="center">
  <i>Construido con precisión y pasión para el rubro farmacéutico moderno.</i>
  <br><br>
  <b>© 2026 NexaPharm Solutions</b>
</div>
