-- =============================================================================
-- PMO SOLUTIONS — Migración 001: Token de Acceso Seguro para Reportes (Anti-IDOR)
-- Compatible con MySQL 5.7+, MySQL 8.0+ y MariaDB 10.2+ en hosting compartido (cPanel)
-- Idempotente: puede ejecutarse múltiples veces consecutivas sin error.
-- =============================================================================

-- 1. Agregar columna report_access_token_hash si no existe
SET @col_exists := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'evaluaciones_habilidades_blandas' 
      AND COLUMN_NAME = 'report_access_token_hash'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `evaluaciones_habilidades_blandas` ADD COLUMN `report_access_token_hash` CHAR(64) NULL COMMENT \'Hash SHA-256 del token de acceso para consulta del reporte\' AFTER `nivel_madurez`',
    'SELECT "Columna report_access_token_hash ya existe" AS status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Agregar restricción UNIQUE uk_eval_token_hash si no existe
SET @idx_exists := (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'evaluaciones_habilidades_blandas' 
      AND INDEX_NAME = 'uk_eval_token_hash'
);

SET @sql := IF(
    @idx_exists = 0,
    'ALTER TABLE `evaluaciones_habilidades_blandas` ADD UNIQUE KEY `uk_eval_token_hash` (`report_access_token_hash`)',
    'SELECT "Índice uk_eval_token_hash ya existe" AS status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

