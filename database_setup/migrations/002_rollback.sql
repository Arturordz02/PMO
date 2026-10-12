-- =============================================================================
-- PMO SOLUTIONS — Rollback Migración 002: Idempotencia y Outbox de Correos
-- =============================================================================

-- 1. Eliminar tabla email_outbox
DROP TABLE IF EXISTS `email_outbox`;

-- 2. Eliminar índice uk_rec_idempotency, request_fingerprint e idempotency_key de reclamaciones
SET @exist_rec_uk := (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'reclamaciones' 
      AND INDEX_NAME = 'uk_rec_idempotency'
);

SET @sql_rec_uk := IF(@exist_rec_uk > 0,
    'ALTER TABLE `reclamaciones` DROP INDEX `uk_rec_idempotency`',
    'SELECT "Índice uk_rec_idempotency no existe"'
);
PREPARE stmt_rec_uk FROM @sql_rec_uk;
EXECUTE stmt_rec_uk;
DEALLOCATE PREPARE stmt_rec_uk;

SET @exist_rec_fp := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'reclamaciones' 
      AND COLUMN_NAME = 'request_fingerprint'
);

SET @sql_rec_fp := IF(@exist_rec_fp > 0,
    'ALTER TABLE `reclamaciones` DROP COLUMN `request_fingerprint`',
    'SELECT "Columna request_fingerprint no existe en reclamaciones"'
);
PREPARE stmt_rec_fp FROM @sql_rec_fp;
EXECUTE stmt_rec_fp;
DEALLOCATE PREPARE stmt_rec_fp;

SET @exist_rec_col := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'reclamaciones' 
      AND COLUMN_NAME = 'idempotency_key'
);

SET @sql_rec_col := IF(@exist_rec_col > 0,
    'ALTER TABLE `reclamaciones` DROP COLUMN `idempotency_key`',
    'SELECT "Columna idempotency_key no existe en reclamaciones"'
);
PREPARE stmt_rec_col FROM @sql_rec_col;
EXECUTE stmt_rec_col;
DEALLOCATE PREPARE stmt_rec_col;

-- 3. Eliminar índice uk_cnt_idempotency, request_fingerprint e idempotency_key de contactos
SET @exist_cnt_uk := (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'contactos' 
      AND INDEX_NAME = 'uk_cnt_idempotency'
);

SET @sql_cnt_uk := IF(@exist_cnt_uk > 0,
    'ALTER TABLE `contactos` DROP INDEX `uk_cnt_idempotency`',
    'SELECT "Índice uk_cnt_idempotency no existe"'
);
PREPARE stmt_cnt_uk FROM @sql_cnt_uk;
EXECUTE stmt_cnt_uk;
DEALLOCATE PREPARE stmt_cnt_uk;

SET @exist_cnt_fp := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'contactos' 
      AND COLUMN_NAME = 'request_fingerprint'
);

SET @sql_cnt_fp := IF(@exist_cnt_fp > 0,
    'ALTER TABLE `contactos` DROP COLUMN `request_fingerprint`',
    'SELECT "Columna request_fingerprint no existe en contactos"'
);
PREPARE stmt_cnt_fp FROM @sql_cnt_fp;
EXECUTE stmt_cnt_fp;
DEALLOCATE PREPARE stmt_cnt_fp;

SET @exist_cnt_col := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'contactos' 
      AND COLUMN_NAME = 'idempotency_key'
);

SET @sql_cnt_col := IF(@exist_cnt_col > 0,
    'ALTER TABLE `contactos` DROP COLUMN `idempotency_key`',
    'SELECT "Columna idempotency_key no existe en contactos"'
);
PREPARE stmt_cnt_col FROM @sql_cnt_col;
EXECUTE stmt_cnt_col;
DEALLOCATE PREPARE stmt_cnt_col;
