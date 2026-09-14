-- =============================================================================
-- PMO SOLUTIONS — Migración 002: Idempotencia y Outbox de Correos
-- Compatible con MySQL 5.7+ / MariaDB 10.2+ en hosting compartido cPanel
-- =============================================================================

-- 1. Añadir columna idempotency_key a la tabla reclamaciones si no existe
SET @exist_rec_col := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'reclamaciones' 
      AND COLUMN_NAME = 'idempotency_key'
);

SET @sql_rec_col := IF(@exist_rec_col = 0,
    'ALTER TABLE `reclamaciones` ADD COLUMN `idempotency_key` VARCHAR(64) NULL COMMENT \'Clave de idempotencia para prevenir duplicados\' AFTER `codigo_reclamacion`',
    'SELECT "Columna idempotency_key ya existe en reclamaciones"'
);
PREPARE stmt_rec_col FROM @sql_rec_col;
EXECUTE stmt_rec_col;
DEALLOCATE PREPARE stmt_rec_col;

-- 2. Añadir columna request_fingerprint a la tabla reclamaciones si no existe
SET @exist_rec_fp := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'reclamaciones' 
      AND COLUMN_NAME = 'request_fingerprint'
);

SET @sql_rec_fp := IF(@exist_rec_fp = 0,
    'ALTER TABLE `reclamaciones` ADD COLUMN `request_fingerprint` CHAR(64) NULL COMMENT \'Hash SHA-256 del payload normalizado para detección de conflictos (Anti-IDOR/Anti-Conflict)\' AFTER `idempotency_key`',
    'SELECT "Columna request_fingerprint ya existe en reclamaciones"'
);
PREPARE stmt_rec_fp FROM @sql_rec_fp;
EXECUTE stmt_rec_fp;
DEALLOCATE PREPARE stmt_rec_fp;

-- 3. Añadir restricción UNIQUE sobre idempotency_key en reclamaciones
SET @exist_rec_uk := (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'reclamaciones' 
      AND INDEX_NAME = 'uk_rec_idempotency'
);

SET @sql_rec_uk := IF(@exist_rec_uk = 0,
    'ALTER TABLE `reclamaciones` ADD UNIQUE KEY `uk_rec_idempotency` (`idempotency_key`)',
    'SELECT "Índice uk_rec_idempotency ya existe en reclamaciones"'
);
PREPARE stmt_rec_uk FROM @sql_rec_uk;
EXECUTE stmt_rec_uk;
DEALLOCATE PREPARE stmt_rec_uk;

-- 4. Añadir columna idempotency_key a la tabla contactos si no existe
SET @exist_cnt_col := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'contactos' 
      AND COLUMN_NAME = 'idempotency_key'
);

SET @sql_cnt_col := IF(@exist_cnt_col = 0,
    'ALTER TABLE `contactos` ADD COLUMN `idempotency_key` VARCHAR(64) NULL COMMENT \'Clave de idempotencia para prevenir duplicados\' AFTER `id`',
    'SELECT "Columna idempotency_key ya existe en contactos"'
);
PREPARE stmt_cnt_col FROM @sql_cnt_col;
EXECUTE stmt_cnt_col;
DEALLOCATE PREPARE stmt_cnt_col;

-- 5. Añadir columna request_fingerprint a la tabla contactos si no existe
SET @exist_cnt_fp := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'contactos' 
      AND COLUMN_NAME = 'request_fingerprint'
);

SET @sql_cnt_fp := IF(@exist_cnt_fp = 0,
    'ALTER TABLE `contactos` ADD COLUMN `request_fingerprint` CHAR(64) NULL COMMENT \'Hash SHA-256 del payload normalizado para detección de conflictos\' AFTER `idempotency_key`',
    'SELECT "Columna request_fingerprint ya existe en contactos"'
);
PREPARE stmt_cnt_fp FROM @sql_cnt_fp;
EXECUTE stmt_cnt_fp;
DEALLOCATE PREPARE stmt_cnt_fp;

-- 6. Añadir restricción UNIQUE sobre idempotency_key en contactos
SET @exist_cnt_uk := (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'contactos' 
      AND INDEX_NAME = 'uk_cnt_idempotency'
);

SET @sql_cnt_uk := IF(@exist_cnt_uk = 0,
    'ALTER TABLE `contactos` ADD UNIQUE KEY `uk_cnt_idempotency` (`idempotency_key`)',
    'SELECT "Índice uk_cnt_idempotency ya existe en contactos"'
);
PREPARE stmt_cnt_uk FROM @sql_cnt_uk;
EXECUTE stmt_cnt_uk;
DEALLOCATE PREPARE stmt_cnt_uk;

-- 7. Crear tabla email_outbox para encolamiento y reintento resiliente de correos
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cola resiliente Outbox de correos transaccionales con reintento automático';
