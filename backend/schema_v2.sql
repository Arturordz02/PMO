-- =============================================================================
-- PMO SOLUTIONS — Esquema de Base de Datos MySQL v2.0 (Canónico para Instalaciones Nuevas)
-- Versión: 2.0 (Única fuente canónica para despliegues e instalaciones desde cero)
-- Motor: InnoDB | Codificación: utf8mb4_unicode_ci
--
-- GUÍA DE USO:
--   * INSTALACIONES NUEVAS: Ejecutar ÚNICAMENTE este archivo (`backend/schema_v2.sql`).
--     Crea la estructura consolidada completa v2.0 en un solo paso.
--   * ACTUALIZACIONES DESDE v1.0: NO ejecutar este archivo. Aplicar las migraciones:
--       1. `backend/migrations/001_add_report_access_token_hash.sql`
--       2. `backend/migrations/002_add_idempotency_and_outbox.sql`
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. TABLA: reclamaciones (Libro de Reclamaciones Virtual)
-- Conforme al Código de Protección y Defensa del Consumidor (Ley N° 29571 / D.S. 011-2011-PCM)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reclamaciones` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo_reclamacion`  VARCHAR(30) NOT NULL COMMENT 'Código único del reclamo (ej. REC-2026-A1B2C)',
  `idempotency_key`     VARCHAR(64) NULL COMMENT 'Clave de idempotencia para prevenir duplicados',
  `request_fingerprint` CHAR(64) NULL COMMENT 'Hash SHA-256 del payload normalizado para detección de conflictos (Anti-IDOR/Anti-Conflict)',
  
  -- Sección 1: Identificación del Reclamante
  `tipo_documento`      ENUM('DNI', 'Carnet de Extranjería', 'Pasaporte', 'RUC') NOT NULL DEFAULT 'DNI',
  `numero_documento`    VARCHAR(25) NOT NULL,
  `nombre_completo`     VARCHAR(200) NOT NULL COMMENT 'Nombres y Apellidos o Razón Social',
  `telefono`            VARCHAR(30) NOT NULL,
  `email`               VARCHAR(150) NOT NULL,
  `domicilio`           VARCHAR(255) NOT NULL,

  -- Sección 2: Identificación del Bien / Servicio Contratado
  `tipo_servicio`       VARCHAR(100) NOT NULL COMMENT 'Servicio de Capacitación / Consultoría Corporativa',
  `nombre_servicio`     VARCHAR(255) NOT NULL COMMENT 'Nombre del Curso, Taller o Asesoría',
  `detalle_servicio`    TEXT NULL COMMENT 'Código de matrícula o comprobante opcional',

  -- Sección 3: Detalle de la Reclamación
  `tipo_registro`       ENUM('Reclamo', 'Queja') NOT NULL DEFAULT 'Reclamo',
  `detalle_reclamacion` TEXT NOT NULL COMMENT 'Descripción fundamentada de los hechos',
  `pedido_consumidor`   TEXT NOT NULL COMMENT 'Solución o medida correctiva solicitada',
  
  -- Sección 4: Declaración Jurada y Auditoría
  `declaracion_jurada`  TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Aceptada conformidad',
  `ip_address`          VARCHAR(45) NOT NULL DEFAULT '0.0.0.0',
  `user_agent`          VARCHAR(255) NULL,
  `estado`              ENUM('Pendiente', 'En proceso', 'Atendido', 'Rechazado') NOT NULL DEFAULT 'Pendiente',
  `respuesta_administracion` TEXT NULL COMMENT 'Respuesta formal brindada al cliente',
  `fecha_respuesta`     DATETIME NULL,
  `fecha_registro`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo_reclamacion` (`codigo_reclamacion`),
  UNIQUE KEY `uk_rec_idempotency`    (`idempotency_key`),
  KEY `idx_rec_email`                (`email`),
  KEY `idx_rec_numero_doc`           (`numero_documento`),
  KEY `idx_rec_fecha_registro`       (`fecha_registro`),
  KEY `idx_rec_estado`               (`estado`),
  KEY `idx_rec_estado_fecha`         (`estado`, `fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro oficial del Libro de Reclamaciones Virtual';

-- -----------------------------------------------------------------------------
-- 2. TABLA: contactos (Mensajes del Formulario de Contacto)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contactos` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idempotency_key`     VARCHAR(64) NULL COMMENT 'Clave de idempotencia para prevenir duplicados',
  `request_fingerprint` CHAR(64) NULL COMMENT 'Hash SHA-256 del payload normalizado para detección de conflictos',
  `nombre`              VARCHAR(150) NOT NULL,
  `telefono`            VARCHAR(30) NOT NULL,
  `email`               VARCHAR(150) NOT NULL,
  `servicio`            VARCHAR(120) NOT NULL DEFAULT 'Capacitación Profesional',
  `mensaje`             TEXT NOT NULL,
  `ip_address`          VARCHAR(45) NOT NULL DEFAULT '0.0.0.0',
  `user_agent`          VARCHAR(255) NULL,
  `estado`              ENUM('Nuevo', 'Contactado', 'Cotizado', 'Cerrado') NOT NULL DEFAULT 'Nuevo',
  `notas_internas`      TEXT NULL,
  `fecha_registro`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cnt_idempotency`    (`idempotency_key`),
  KEY `idx_contacto_email`           (`email`),
  KEY `idx_contacto_fecha`           (`fecha_registro`),
  KEY `idx_contacto_estado`          (`estado`),
  KEY `idx_contacto_estado_fecha`    (`estado`, `fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Consultas recibidas desde el formulario de contacto web';

-- -----------------------------------------------------------------------------
-- 3. TABLA: email_outbox (Cola Transaccional de Correos Salientes)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_outbox` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idempotency_key`   VARCHAR(64) NULL,
  `type`              VARCHAR(50) NOT NULL COMMENT 'claim_admin, claim_user, contact_admin',
  `reference_id`      INT UNSIGNED NULL COMMENT 'ID de reclamación o contacto',
  `recipient_email`   VARCHAR(150) NOT NULL,
  `recipient_name`    VARCHAR(150) NOT NULL,
  `subject`           VARCHAR(255) NOT NULL,
  `html_body`         MEDIUMTEXT NOT NULL,
  `text_body`         MEDIUMTEXT NULL,
  `attempts`          TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts`      TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `status`            ENUM('pending', 'processing', 'sent', 'failed') NOT NULL DEFAULT 'pending',
  `last_error`        TEXT NULL,
  `next_retry_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at`           DATETIME NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_outbox_idemp_type` (`idempotency_key`, `type`),
  KEY `idx_outbox_status_retry`     (`status`, `next_retry_at`),
  KEY `idx_outbox_ref`              (`type`, `reference_id`),
  KEY `idx_outbox_created`          (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cola resiliente Outbox de correos transaccionales con reintento automático';

-- -----------------------------------------------------------------------------
-- 4. TABLA: evaluaciones_habilidades_blandas
-- Registro de sesiones de evaluación por participante
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `evaluaciones_habilidades_blandas` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo_evaluacion`   VARCHAR(30)  NOT NULL COMMENT 'Código único EVA-2026-XXXXX',

  -- Datos del evaluado
  `nombre_completo`     VARCHAR(200) NOT NULL,
  `email`               VARCHAR(150) NOT NULL,
  `cargo`               VARCHAR(100) NULL     COMMENT 'Cargo o puesto laboral',
  `empresa`             VARCHAR(150) NULL     COMMENT 'Empresa u organización',
  `tipo_evaluacion`     ENUM('Individual', 'Grupal', 'Pre-Capacitación', 'Post-Capacitación')
                                     NOT NULL DEFAULT 'Individual',

  -- Resultados globales (0–100)
  `score_comunicacion`       TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Puntuación Comunicación (0-100)',
  `score_trabajo_equipo`     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Puntuación Trabajo en Equipo (0-100)',
  `score_resolucion`         TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Puntuación Resolución de Problemas (0-100)',
  `score_adaptabilidad`      TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Puntuación Adaptabilidad (0-100)',
  `score_global`             TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Promedio ponderado global (0-100)',

  -- Clasificación de madurez
  `nivel_madurez`       ENUM('Inicial', 'En Desarrollo', 'Competente', 'Sobresaliente')
                                     NOT NULL DEFAULT 'Inicial',

  -- Token de acceso seguro (Anti-IDOR)
  `report_access_token_hash` CHAR(64) NULL COMMENT 'Hash SHA-256 del token de acceso para consulta del reporte',

  -- Reporte y metadatos
  `reporte_json`        MEDIUMTEXT   NULL COMMENT 'Reporte detallado en JSON (comprimido base64)',
  `tiempo_respuesta_seg` SMALLINT UNSIGNED NULL COMMENT 'Tiempo total de la evaluación en segundos',
  `ip_address`          VARCHAR(45)  NOT NULL DEFAULT '0.0.0.0',
  `user_agent`          VARCHAR(255) NULL,
  `estado`              ENUM('Completada', 'Abandonada', 'En Proceso') NOT NULL DEFAULT 'En Proceso',
  `fecha_registro`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo_evaluacion` (`codigo_evaluacion`),
  UNIQUE KEY `uk_eval_token_hash`   (`report_access_token_hash`),

  -- Índices individuales
  KEY `idx_eval_email`        (`email`),
  KEY `idx_eval_fecha`        (`fecha_registro`),
  KEY `idx_eval_estado`       (`estado`),
  KEY `idx_eval_nivel`        (`nivel_madurez`),

  -- Índices compuestos para consultas de reportes
  KEY `idx_eval_estado_fecha` (`estado`, `fecha_registro`),
  KEY `idx_eval_score_global` (`score_global`, `nivel_madurez`),
  KEY `idx_eval_empresa`      (`empresa`, `fecha_registro`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro de evaluaciones de habilidades blandas por participante';

-- -----------------------------------------------------------------------------
-- 5. TABLA: respuestas_evaluacion
-- Respuestas individuales por pregunta (granularidad máxima para análisis)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `respuestas_evaluacion` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `evaluacion_id`     INT UNSIGNED NOT NULL COMMENT 'FK a evaluaciones_habilidades_blandas',
  `competencia`       ENUM('comunicacion', 'trabajo_equipo', 'resolucion_problemas', 'adaptabilidad')
                                   NOT NULL,
  `pregunta_id`       TINYINT UNSIGNED NOT NULL COMMENT 'ID de la pregunta dentro de la competencia (1-5)',
  `opcion_elegida`    TINYINT UNSIGNED NOT NULL COMMENT 'Opción seleccionada (1-4)',
  `puntaje_obtenido`  TINYINT UNSIGNED NOT NULL COMMENT 'Puntaje de la opción (0-25)',
  `tiempo_respuesta_seg` SMALLINT UNSIGNED NULL COMMENT 'Tiempo en responder esta pregunta',
  `fecha_registro`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_resp_evaluacion`           (`evaluacion_id`),
  KEY `idx_resp_competencia`          (`competencia`, `puntaje_obtenido`),
  KEY `idx_resp_pregunta`             (`pregunta_id`, `competencia`),

  CONSTRAINT `fk_respuesta_evaluacion`
    FOREIGN KEY (`evaluacion_id`)
    REFERENCES `evaluaciones_habilidades_blandas` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Respuestas individuales por pregunta de cada evaluación';

-- -----------------------------------------------------------------------------
-- 6. TABLA: cache_db
-- Caché de base de datos como fallback (cuando Redis/APCu no disponibles)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cache_db` (
  `cache_key`     VARCHAR(128) NOT NULL COMMENT 'Clave MD5 del identificador',
  `cache_value`   MEDIUMBLOB   NOT NULL COMMENT 'Valor serializado (base64+gzip)',
  `expires_at`    DATETIME     NOT NULL COMMENT 'Timestamp de expiración',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`cache_key`),
  KEY `idx_cache_expires` (`expires_at`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Caché de base de datos — fallback si Redis/APCu no disponibles';

-- -----------------------------------------------------------------------------
-- 7. TABLA: rate_limits
-- Rate limiting persistente en DB
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier`    VARCHAR(64)  NOT NULL COMMENT 'Hash MD5 de IP o user_id',
  `endpoint`      VARCHAR(100) NOT NULL DEFAULT 'global' COMMENT 'Endpoint o acción limitada',
  `hits`          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `window_start`  DATETIME     NOT NULL COMMENT 'Inicio de la ventana de tiempo',
  `window_end`    DATETIME     NOT NULL COMMENT 'Fin de la ventana de tiempo',
  `blocked_until` DATETIME     NULL     COMMENT 'Bloqueado hasta (NULL = no bloqueado)',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rate_identifier_endpoint` (`identifier`, `endpoint`, `window_start`),
  KEY `idx_rate_window`     (`window_end`),
  KEY `idx_rate_blocked`    (`blocked_until`),
  KEY `idx_rate_identifier` (`identifier`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Rate limiting persistente — alternativa robusta a archivos temporales';

-- -----------------------------------------------------------------------------
-- 8. TABLA: audit_logs
-- Logs de auditoría estructurados para cumplimiento y análisis
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level`         ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL') NOT NULL DEFAULT 'INFO',
  `channel`       VARCHAR(50)  NOT NULL DEFAULT 'app' COMMENT 'Canal: app, security, db, cache',
  `message`       TEXT         NOT NULL,
  `context`       JSON         NULL COMMENT 'Contexto estructurado en JSON',
  `ip_address`    VARCHAR(45)  NULL,
  `user_agent`    VARCHAR(255) NULL,
  `request_uri`   VARCHAR(500) NULL,
  `fecha_registro` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_log_level`    (`level`, `fecha_registro`),
  KEY `idx_log_channel`  (`channel`, `fecha_registro`),
  KEY `idx_log_fecha`    (`fecha_registro`),
  KEY `idx_log_purge`    (`fecha_registro`, `level`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Logs de auditoría estructurados — retención 90 días vía LogArchiver';

-- =============================================================================
-- VISTAS DE ANÁLISIS
-- =============================================================================

-- Vista: Ranking de participantes por score global
CREATE OR REPLACE VIEW `v_ranking_evaluaciones` AS
  SELECT
    e.codigo_evaluacion,
    e.nombre_completo,
    e.empresa,
    e.score_comunicacion,
    e.score_trabajo_equipo,
    e.score_resolucion,
    e.score_adaptabilidad,
    e.score_global,
    e.nivel_madurez,
    e.fecha_registro,
    RANK() OVER (ORDER BY e.score_global DESC) AS ranking_global
  FROM `evaluaciones_habilidades_blandas` e
  WHERE e.estado = 'Completada'
  ORDER BY e.score_global DESC;

-- Vista: Promedios por competencia (para dashboard de analytics)
CREATE OR REPLACE VIEW `v_promedios_competencias` AS
  SELECT
    DATE_FORMAT(fecha_registro, '%Y-%m') AS periodo,
    ROUND(AVG(score_comunicacion), 2)    AS avg_comunicacion,
    ROUND(AVG(score_trabajo_equipo), 2)  AS avg_trabajo_equipo,
    ROUND(AVG(score_resolucion), 2)      AS avg_resolucion,
    ROUND(AVG(score_adaptabilidad), 2)   AS avg_adaptabilidad,
    ROUND(AVG(score_global), 2)          AS avg_global,
    COUNT(*)                              AS total_evaluaciones
  FROM `evaluaciones_habilidades_blandas`
  WHERE estado = 'Completada'
  GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m')
  ORDER BY periodo DESC;
