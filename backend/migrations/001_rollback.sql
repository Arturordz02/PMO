-- =============================================================================
-- PMO SOLUTIONS — Rollback 001: Revertir Token de Acceso Seguro para Reportes
-- Compatible con MySQL 5.7+, MySQL 8.0+ y MariaDB 10.2+ en hosting compartido (cPanel)
-- Idempotente: puede ejecutarse múltiples veces consecutivas sin error.
-- =============================================================================

-- 1. Eliminar restricción UNIQUE si existe
SET @idx_exists := (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'evaluaciones_habilidades_blandas' 
      AND INDEX_NAME = 'uk_eval_token_hash'
);

SET @sql := IF(
    @idx_exists > 0,
    'ALTER TABLE `evaluaciones_habilidades_blandas` DROP INDEX `uk_eval_token_hash`',
    'SELECT "Índice uk_eval_token_hash no existe" AS status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Eliminar columna report_access_token_hash si existe
SET @col_exists := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'evaluaciones_habilidades_blandas' 
      AND COLUMN_NAME = 'report_access_token_hash'
);

SET @sql := IF(
    @col_exists > 0,
    'ALTER TABLE `evaluaciones_habilidades_blandas` DROP COLUMN `report_access_token_hash`',
    'SELECT "Columna report_access_token_hash no existe" AS status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

