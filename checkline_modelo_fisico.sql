-- ====================================================================
-- CHECK-LINE — Modelo Físico de Datos (MySQL / MariaDB)
-- Trabajo Práctico — Entornos Gráficos — UTN 2026 — Comisión 203
-- Integrantes: Carloni, Nahuel — Mierez, Joaquín
-- ====================================================================


-- ====================================================================
-- ROLES — Admin / CEO / Pasajero
-- ====================================================================
CREATE TABLE roles (
  id_rol      INT AUTO_INCREMENT PRIMARY KEY,
  nombre_rol  VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ====================================================================
-- USUARIOS — Cuenta única para los 3 roles del sistema
-- ====================================================================
CREATE TABLE usuarios (
  id_usuario        INT AUTO_INCREMENT PRIMARY KEY,
  nombre            VARCHAR(60)  NOT NULL,
  apellido          VARCHAR(60)  NOT NULL,
  email             VARCHAR(120) NOT NULL UNIQUE,
  password_hash     VARCHAR(255) NOT NULL,
  id_rol            INT NOT NULL,
  activo            TINYINT(1) NOT NULL DEFAULT 0,   -- 0=pend. validación mail, 1=activo
  token_validacion  VARCHAR(100) NULL,
  fecha_registro    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
) ENGINE=InnoDB;

-- ====================================================================
-- AEROLINEAS — Alta exclusiva del Administrador (ABMC #1)
-- ====================================================================
CREATE TABLE aerolineas (
  id_aerolinea  INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  codigo        VARCHAR(3)   NOT NULL UNIQUE,
  pais          VARCHAR(60)  NOT NULL,
  id_ceo        INT NOT NULL,
  fecha_alta    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_aerolinea_ceo FOREIGN KEY (id_ceo) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- ====================================================================
-- VUELOS — Alta del CEO de cada aerolínea (ABMC #2)
-- ====================================================================
CREATE TABLE vuelos (
  id_vuelo              INT AUTO_INCREMENT PRIMARY KEY,
  id_aerolinea          INT NOT NULL,
  codigo_vuelo          VARCHAR(10) NOT NULL,
  origen                VARCHAR(60) NOT NULL,
  destino               VARCHAR(60) NOT NULL,
  fecha_salida          DATE NOT NULL,
  hora_salida           TIME NOT NULL,
  fecha_llegada         DATE NOT NULL,
  hora_llegada          TIME NOT NULL,
  precio                DECIMAL(10,2) NOT NULL,
  asientos_totales      INT NOT NULL,
  asientos_disponibles  INT NOT NULL,
  estado                ENUM('activo','cancelado','finalizado') NOT NULL DEFAULT 'activo',
  CONSTRAINT fk_vuelo_aerolinea FOREIGN KEY (id_aerolinea) REFERENCES aerolineas(id_aerolinea),
  INDEX idx_busqueda (origen, destino, fecha_salida)
) ENGINE=InnoDB;

-- ====================================================================
-- PROMOCIONES — Creada por CEO, aprobada/denegada por Admin (Punto 5c)
-- ====================================================================
CREATE TABLE promociones (
  id_promocion          INT AUTO_INCREMENT PRIMARY KEY,
  id_vuelo              INT NOT NULL,
  descuento_porcentaje  DECIMAL(5,2) NOT NULL,
  fecha_inicio          DATE NOT NULL,
  fecha_fin             DATE NOT NULL,
  estado                ENUM('Pendiente','Aprobada','Denegada') NOT NULL DEFAULT 'Pendiente',
  destacada             TINYINT(1) NOT NULL DEFAULT 0,  -- monetización: Punto 10
  id_creador            INT NOT NULL,   -- CEO
  id_aprobador          INT NULL,       -- Admin
  fecha_creacion        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_resolucion      DATETIME NULL,
  CONSTRAINT fk_promo_vuelo     FOREIGN KEY (id_vuelo)     REFERENCES vuelos(id_vuelo),
  CONSTRAINT fk_promo_creador   FOREIGN KEY (id_creador)   REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_promo_aprobador FOREIGN KEY (id_aprobador) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- ====================================================================
-- RESERVAS — Flujo del Pasajero (Punto 5c)
-- ====================================================================
CREATE TABLE reservas (
  id_reserva        INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario        INT NOT NULL,  -- pasajero
  id_vuelo          INT NOT NULL,
  id_promocion      INT NULL,
  precio_final      DECIMAL(10,2) NOT NULL,
  estado            ENUM('pendiente_pago','Confirmada','cancelada') NOT NULL DEFAULT 'pendiente_pago',
  fecha_reserva     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_pago        DATETIME NULL,
  fecha_cancelacion DATETIME NULL,
  CONSTRAINT fk_reserva_usuario   FOREIGN KEY (id_usuario)   REFERENCES usuarios(id_usuario),
  CONSTRAINT fk_reserva_vuelo     FOREIGN KEY (id_vuelo)     REFERENCES vuelos(id_vuelo),
  CONSTRAINT fk_reserva_promocion FOREIGN KEY (id_promocion) REFERENCES promociones(id_promocion)
) ENGINE=InnoDB;

-- ====================================================================
-- NOVEDADES — ABMC del Administrador
-- ====================================================================
CREATE TABLE novedades (
  id_novedad      INT AUTO_INCREMENT PRIMARY KEY,
  titulo          VARCHAR(150) NOT NULL,
  contenido       TEXT NOT NULL,
  fecha_inicio    DATE NOT NULL,
  fecha_fin       DATE NOT NULL,
  id_admin        INT NOT NULL,
  fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_novedad_admin FOREIGN KEY (id_admin) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- ====================================================================
-- DATOS INICIALES OBLIGATORIOS
-- ====================================================================
INSERT INTO roles (nombre_rol) VALUES ('admin'), ('ceo'), ('pasajero');
