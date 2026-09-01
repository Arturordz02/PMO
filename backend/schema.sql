-- =============================================================================
-- PMO SOLUTIONS - Esquema de Base de Datos MySQL
-- Versión: 1.0
-- Motor: InnoDB | Codificación: utf8mb4_unicode_ci
-- =============================================================================

-- Opcional: Crear la base de datos si no existe (descomentar si dispone de permisos)
-- CREATE DATABASE IF NOT EXISTS `pmo_solutions_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `pmo_solutions_db`;

-- -----------------------------------------------------------------------------
-- 1. TABLA: reclamaciones (Libro de Reclamaciones Virtual)
-- Conforme al Código de Protección y Defensa del Consumidor (Ley N° 29571 / D.S. 011-2011-PCM)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reclamaciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo_reclamacion` VARCHAR(30) NOT NULL COMMENT 'Código único del reclamo (ej. REC-2026-A1B2C)',
  
  -- Sección 1: Identificación del Reclamante
  `tipo_documento` ENUM('DNI', 'Carnet de Extranjería', 'Pasaporte', 'RUC') NOT NULL DEFAULT 'DNI',
  `numero_documento` VARCHAR(25) NOT NULL,
  `nombre_completo` VARCHAR(200) NOT NULL COMMENT 'Nombres y Apellidos o Razón Social',
  `telefono` VARCHAR(30) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `domicilio` VARCHAR(255) NOT NULL,

  -- Sección 2: Identificación del Bien / Servicio Contratado
  `tipo_servicio` VARCHAR(100) NOT NULL COMMENT 'Servicio de Capacitación / Consultoría Corporativa',
  `nombre_servicio` VARCHAR(255) NOT NULL COMMENT 'Nombre del Curso, Taller o Asesoría',
  `detalle_servicio` TEXT NULL COMMENT 'Código de matrícula o comprobante opcional',

  -- Sección 3: Detalle de la Reclamación
  `tipo_registro` ENUM('Reclamo', 'Queja') NOT NULL DEFAULT 'Reclamo',
  `detalle_reclamacion` TEXT NOT NULL COMMENT 'Descripción fundamentada de los hechos',
  `pedido_consumidor` TEXT NOT NULL COMMENT 'Solución o medida correctiva solicitada',
  
  -- Sección 4: Declaración Jurada y Auditoría
  `declaracion_jurada` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Aceptada conformidad',
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '0.0.0.0',
  `user_agent` VARCHAR(255) NULL,
  `estado` ENUM('Pendiente', 'En proceso', 'Atendido', 'Rechazado') NOT NULL DEFAULT 'Pendiente',
  `respuesta_administracion` TEXT NULL COMMENT 'Respuesta formal brindada al cliente',
  `fecha_respuesta` DATETIME NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo_reclamacion` (`codigo_reclamacion`),
  KEY `idx_email` (`email`),
  KEY `idx_numero_documento` (`numero_documento`),
  KEY `idx_fecha_registro` (`fecha_registro`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro oficial del Libro de Reclamaciones Virtual';

-- -----------------------------------------------------------------------------
-- 2. TABLA: contactos (Mensajes del Formulario de Contacto)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contactos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(30) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `servicio` VARCHAR(120) NOT NULL DEFAULT 'Capacitación Profesional',
  `mensaje` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '0.0.0.0',
  `user_agent` VARCHAR(255) NULL,
  `estado` ENUM('Nuevo', 'Contactado', 'Cotizado', 'Cerrado') NOT NULL DEFAULT 'Nuevo',
  `notas_internas` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_contacto_email` (`email`),
  KEY `idx_contacto_fecha` (`fecha_registro`),
  KEY `idx_contacto_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Consultas recibidas desde el formulario de contacto web';

