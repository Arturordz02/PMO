<?php
/**
 * PMO SOLUTIONS — Suite de Pruebas: Esquema de Base de Datos y Migraciones MySQL (Tarea 7)
 *
 * Cobertura de pruebas:
 * 1. Detección de conectividad MySQL/MariaDB (con degradación estricta a SKIP si no está disponible).
 * 2. Creación de dos entornos aislados:
 *    - Camino A: baseline `backend/schema.sql` (v1.0) + migración 001 + migración 002.
 *    - Camino B: instalación nueva con `backend/schema_v2.sql` (v2.0).
 * 3. Comparación bidireccional exhaustiva vía information_schema:
 *    - Tablas: detección bidireccional de tablas faltantes y adicionales.
 *    - Motor y Collation: ENGINE = InnoDB y TABLE_COLLATION = utf8mb4_unicode_ci en todas las tablas de ambos caminos.
 *    - Columnas: tipo, orden/posición, nulabilidad, defaults, EXTRA y comentarios.
 *    - Índices: nombre, columnas ordenadas (SEQ_IN_INDEX) y unicidad (NON_UNIQUE).
 *    - Claves foráneas: restricciones, columnas origen/referenciadas y reglas ON DELETE / ON UPDATE.
 * 4. Validación previa de columnas de prueba contra information_schema.
 * 5. Inserción real de Hoja de Reclamación con 'Carnet de Extranjería'.
 * 6. Restricciones UNIQUE reales contra duplicados (reclamaciones, contactos, outbox, evaluaciones).
 * 7. Idempotencia de migraciones (doble ejecución de 001 y 002 sin errores).
 * 8. Idempotencia de rollbacks (doble ejecución de 002_rollback y 001_rollback sin errores).
 * 9. Re-migración exitosa tras rollback.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Env.php';
require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Security.php';
require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Core/EmailOutbox.php';
require_once dirname(__DIR__) . '/app/Core/SmtpMailer.php';
require_once dirname(__DIR__) . '/app/Models/ClaimModel.php';

use App\Core\Database;
use App\Core\EmailOutbox;
use App\Models\ClaimModel;

class DatabaseSchemaTest {

    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;
    private ?PDO $pdo = null;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function run(): void {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo " SUITE DE PRUEBAS: ESQUEMA Y MIGRACIONES MYSQL (TAREA 7)\n";
        echo str_repeat('=', 70) . "\n\n";

        if ($this->pdo === null) {
            $this->runSkippedSuite("Base de datos MySQL/MariaDB no disponible en el entorno local");
        } else {
            $this->runLiveDatabaseSuite();
        }

        echo "\n" . str_repeat('=', 70) . "\n";
        echo " RESUMEN: TAREA 7 (ESQUEMA Y MIGRACIONES MYSQL)\n";
        echo str_repeat('=', 70) . "\n";
        echo " Total tests: " . ($this->passed + $this->failed + $this->skipped) .
             " | Pasados: {$this->passed}" .
             " | Fallados: {$this->failed}" .
             " | Omitidos (SKIP): {$this->skipped}\n\n";

        if ($this->failed > 0) {
            echo "✖ SE ENCONTRARON FALLOS EN LAS PRUEBAS DE BASE DE DATOS.\n\n";
            exit(1);
        } elseif ($this->skipped > 0 && $this->passed === 0) {
            echo "↷ TODAS LAS PRUEBAS DE BASE DE DATOS FUERON OMITIDAS (SKIP) POR AUSENCIA DE SERVICIO MYSQL.\n";
            echo "  (No se emiten falsos positivos PASS conforme a las especificaciones del proyecto).\n\n";
        } else {
            echo "✔ TODAS LAS PRUEBAS DE BASE DE DATOS Y MIGRACIONES PASARON AL 100%.\n\n";
        }
    }

    private function assert(bool $condition, string $message): void {
        if ($condition) {
            $this->passed++;
            echo "  ✔ [PASS] {$message}\n";
        } else {
            $this->failed++;
            echo "  ✖ [FAIL] {$message}\n";
        }
    }

    private function skip(string $testName, string $reason): void {
        $this->skipped++;
        echo "  ↷ [SKIP] {$testName} -> {$reason}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Modo SKIP: Cuando MySQL no está disponible
    // ─────────────────────────────────────────────────────────────────────────
    private function runSkippedSuite(string $reason): void {
        echo "--- 1. Verificación de Conectividad a Base de Datos ---\n";
        $this->skip("Conectividad MySQL activa", $reason);

        echo "\n--- 2. Comparación Bidireccional: schema_v2.sql vs (schema.sql + 001 + 002) ---\n";
        $this->skip("Equivalencia bidireccional de conjunto de tablas en ambos esquemas", $reason);
        $this->skip("Verificación explícita de ENGINE = InnoDB y TABLE_COLLATION = utf8mb4_unicode_ci en todas las tablas", $reason);
        $this->skip("Equivalencia bidireccional de columnas, posiciones, tipos, nulabilidad, defaults, EXTRA y comentarios", $reason);
        $this->skip("Equivalencia bidireccional de índices, unicidad y orden secuencial de columnas", $reason);
        $this->skip("Equivalencia bidireccional de claves foráneas con reglas ON DELETE / ON UPDATE", $reason);

        echo "\n--- 3. Validación Previa de Columnas de Prueba contra information_schema ---\n";
        $this->skip("Validación de columnas de inserción contra information_schema", $reason);

        echo "\n--- 4. Inserción Real con Carnet de Extranjería ---\n";
        $this->skip("Inserción y consulta de reclamo con 'Carnet de Extranjería'", $reason);

        echo "\n--- 5. Restricciones UNIQUE Reales contra Duplicados ---\n";
        $this->skip("Restricción UNIQUE uk_rec_idempotency en reclamaciones", $reason);
        $this->skip("Restricción UNIQUE uk_cnt_idempotency en contactos (incluyendo telefono obligatorio)", $reason);
        $this->skip("Restricción UNIQUE uk_outbox_idemp_type en email_outbox (compuesta)", $reason);
        $this->skip("Restricción UNIQUE uk_eval_token_hash en evaluaciones (esquema v2 actual)", $reason);

        echo "\n--- 6. Idempotencia de Migraciones (Doble Ejecución 001 y 002) ---\n";
        $this->skip("Migración 001 idempotente (2x sin error)", $reason);
        $this->skip("Migración 002 idempotente (2x sin error)", $reason);

        echo "\n--- 7. Idempotencia de Rollbacks (Doble Ejecución 002_rollback y 001_rollback) ---\n";
        $this->skip("Rollback 002 idempotente (2x sin error)", $reason);
        $this->skip("Rollback 001 idempotente (2x sin error)", $reason);

        echo "\n--- 8. Re-migración Exitosa tras Rollback ---\n";
        $this->skip("Re-aplicación de migraciones tras rollback", $reason);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Modo LIVE: Cuando MySQL está conectado y disponible
    // ─────────────────────────────────────────────────────────────────────────
    private function runLiveDatabaseSuite(): void {
        echo "--- 1. Verificación de Conectividad a Base de Datos ---\n";
        $this->assert($this->pdo instanceof PDO, "Conexión a MySQL/MariaDB establecida con éxito");

        $prefixA = 'pmo_t7_a_' . substr(md5(uniqid('', true)), 0, 6) . '_';
        $prefixB = 'pmo_t7_b_' . substr(md5(uniqid('', true)), 0, 6) . '_';

        $baseDir = dirname(__DIR__);
        $schemaV1 = (string)file_get_contents($baseDir . '/backend/schema.sql');
        $mig001 = (string)file_get_contents($baseDir . '/backend/migrations/001_add_report_access_token_hash.sql');
        $mig002 = (string)file_get_contents($baseDir . '/backend/migrations/002_add_idempotency_and_outbox.sql');
        $roll002 = (string)file_get_contents($baseDir . '/backend/migrations/002_rollback.sql');
        $roll001 = (string)file_get_contents($baseDir . '/backend/migrations/001_rollback.sql');
        $schemaV2 = (string)file_get_contents($baseDir . '/backend/schema_v2.sql');

        // Tablas estándar evaluadas
        $tableList = [
            'reclamaciones',
            'contactos',
            'email_outbox',
            'evaluaciones_habilidades_blandas',
            'respuestas_evaluacion',
            'cache_db',
            'rate_limits',
            'audit_logs'
        ];

        try {
            // Preparar Camino A con prefijo
            $this->executeSqlScript($schemaV1, $prefixA);
            $this->executeSqlScript($mig001, $prefixA);
            $this->executeSqlScript($mig002, $prefixA);

            // Preparar Camino B con prefijo
            $this->executeSqlScript($schemaV2, $prefixB);

            echo "\n--- 2. Comparación Bidireccional: schema_v2.sql vs (schema.sql + 001 + 002) ---\n";
            $schemaA = $this->inspectSchema($prefixA, $tableList);
            $schemaB = $this->inspectSchema($prefixB, $tableList);

            // 2.1 Aserción bidireccional de tablas
            $tablesA = array_keys($schemaA['tables']);
            $tablesB = array_keys($schemaB['tables']);
            sort($tablesA);
            sort($tablesB);

            $missingInB = array_diff($tablesA, $tablesB);
            $missingInA = array_diff($tablesB, $tablesA);
            $tablesMatch = empty($missingInA) && empty($missingInB) && count($tablesA) === count($tableList);
            $this->assert($tablesMatch, "Conjunto idéntico de tablas (" . count($tablesA) . ") en ambos esquemas");

            // 2.2 Verificación explícita de ENGINE = InnoDB y TABLE_COLLATION = utf8mb4_unicode_ci
            $enginesAndCollationsMatch = true;
            $diffEngColl = [];
            foreach ($tableList as $tbl) {
                $engineA = $schemaA['tables'][$tbl]['engine'] ?? '';
                $engineB = $schemaB['tables'][$tbl]['engine'] ?? '';
                $collA   = $schemaA['tables'][$tbl]['collation'] ?? '';
                $collB   = $schemaB['tables'][$tbl]['collation'] ?? '';

                if ($engineA !== 'InnoDB' || $engineB !== 'InnoDB') {
                    $enginesAndCollationsMatch = false;
                    $diffEngColl[] = "Motor no es InnoDB en {$tbl} (Camino A: {$engineA}, Camino B: {$engineB})";
                }
                if ($collA !== 'utf8mb4_unicode_ci' || $collB !== 'utf8mb4_unicode_ci') {
                    $enginesAndCollationsMatch = false;
                    $diffEngColl[] = "Collation no es utf8mb4_unicode_ci en {$tbl} (Camino A: {$collA}, Camino B: {$collB})";
                }
            }
            $this->assert($enginesAndCollationsMatch, "Todas las tablas de ambos caminos tienen explícitamente ENGINE = InnoDB y TABLE_COLLATION = utf8mb4_unicode_ci" . (!empty($diffEngColl) ? " (Diffs: " . implode('; ', $diffEngColl) . ")" : ""));

            // 2.3 Aserción bidireccional de columnas (tipo, posición, nulabilidad, defaults, EXTRA, comentarios)
            $colsMatch = true;
            $diffsCols = [];

            // A -> B
            foreach ($schemaA['columns'] as $tName => $cols) {
                if (!isset($schemaB['columns'][$tName])) {
                    $colsMatch = false;
                    $diffsCols[] = "Tabla {$tName} presente en A pero ausente en B";
                    continue;
                }
                foreach ($cols as $cName => $defA) {
                    if (!isset($schemaB['columns'][$tName][$cName])) {
                        $colsMatch = false;
                        $diffsCols[] = "Columna {$tName}.{$cName} presente en A pero ausente en B";
                        continue;
                    }
                    $defB = $schemaB['columns'][$tName][$cName];
                    if ($defA != $defB) {
                        $colsMatch = false;
                        $diffsCols[] = "Diferencia en {$tName}.{$cName}: A=" . json_encode($defA) . " vs B=" . json_encode($defB);
                    }
                }
            }
            // B -> A
            foreach ($schemaB['columns'] as $tName => $cols) {
                if (!isset($schemaA['columns'][$tName])) {
                    $colsMatch = false;
                    $diffsCols[] = "Tabla {$tName} presente en B pero ausente en A";
                    continue;
                }
                foreach ($cols as $cName => $defB) {
                    if (!isset($schemaA['columns'][$tName][$cName])) {
                        $colsMatch = false;
                        $diffsCols[] = "Columna {$tName}.{$cName} presente en B pero ausente en A";
                    }
                }
            }

            $this->assert($colsMatch, "Equivalencia bidireccional de columnas, tipos, orden, nulabilidad, defaults, extra y comentarios" . (!empty($diffsCols) ? " (Diffs: " . implode('; ', array_slice($diffsCols, 0, 3)) . ")" : ""));

            // 2.4 Aserción bidireccional de índices y restricciones UNIQUE
            $idxsMatch = true;
            $diffsIdx = [];
            foreach ($tableList as $tName) {
                $idxA = $schemaA['indexes'][$tName] ?? [];
                $idxB = $schemaB['indexes'][$tName] ?? [];
                if ($idxA != $idxB) {
                    $idxsMatch = false;
                    $diffsIdx[] = "Diferencia en índices de tabla {$tName}: A=" . json_encode($idxA) . " vs B=" . json_encode($idxB);
                }
            }
            $this->assert($idxsMatch, "Equivalencia bidireccional de índices, unicidad y orden de columnas" . (!empty($diffsIdx) ? " (Diffs: " . implode('; ', $diffsIdx) . ")" : ""));

            // 2.5 Aserción bidireccional de claves foráneas con reglas de acción
            $fksMatch = true;
            $diffsFk = [];
            foreach ($tableList as $tName) {
                $fkA = $schemaA['fks'][$tName] ?? [];
                $fkB = $schemaB['fks'][$tName] ?? [];
                if ($fkA != $fkB) {
                    $fksMatch = false;
                    $diffsFk[] = "Diferencia en foreign keys de {$tName}: A=" . json_encode($fkA) . " vs B=" . json_encode($fkB);
                }
            }
            $this->assert($fksMatch, "Equivalencia bidireccional de llaves foráneas y reglas ON DELETE / ON UPDATE");

            echo "\n--- 3. Validación Previa de Columnas de Prueba contra information_schema ---\n";
            $this->validateTestColumnsAgainstSchema($prefixB);

            echo "\n--- 4. Inserción Real con Carnet de Extranjería ---\n";
            $this->testCarnetExtranjeriaInsertion($prefixB);

            echo "\n--- 5. Restricciones UNIQUE Reales contra Duplicados ---\n";
            $this->testAllUniqueConstraints($prefixB);

            echo "\n--- 6. Idempotencia de Migraciones (Doble Ejecución 001 y 002) ---\n";
            $mig001Second = $this->tryExecuteSqlScript($mig001, $prefixA);
            $this->assert($mig001Second, "Migración 001 ejecutada por segunda vez sin lanzar errores (Idempotente)");

            $mig002Second = $this->tryExecuteSqlScript($mig002, $prefixA);
            $this->assert($mig002Second, "Migración 002 ejecutada por segunda vez sin lanzar errores (Idempotente)");

            echo "\n--- 7. Idempotencia de Rollbacks (Doble Ejecución 002_rollback y 001_rollback) ---\n";
            $roll002First = $this->tryExecuteSqlScript($roll002, $prefixA);
            $this->assert($roll002First, "Rollback 002 ejecutado con éxito");
            $roll002Second = $this->tryExecuteSqlScript($roll002, $prefixA);
            $this->assert($roll002Second, "Rollback 002 ejecutado por segunda vez sin errores (Idempotente)");

            $roll001First = $this->tryExecuteSqlScript($roll001, $prefixA);
            $this->assert($roll001First, "Rollback 001 ejecutado con éxito");
            $roll001Second = $this->tryExecuteSqlScript($roll001, $prefixA);
            $this->assert($roll001Second, "Rollback 001 ejecutado por segunda vez sin errores (Idempotente)");

            echo "\n--- 8. Re-migración Exitosa tras Rollback ---\n";
            $reMig001 = $this->tryExecuteSqlScript($mig001, $prefixA);
            $reMig002 = $this->tryExecuteSqlScript($mig002, $prefixA);
            $this->assert($reMig001 && $reMig002, "Re-migración exitosa de 001 y 002 tras rollback completo");

        } finally {
            // Limpieza de tablas de prueba
            $this->dropPrefixedTables($prefixA, $tableList);
            $this->dropPrefixedTables($prefixB, $tableList);
        }
    }

    /**
     * Valida que las columnas empleadas por las pruebas de inserción existan en information_schema
     * y que cubran todos los campos obligatorios (NOT NULL sin DEFAULT y no AUTO_INCREMENT)
     */
    private function validateTestColumnsAgainstSchema(string $prefix): void {
        $dbName = $this->pdo->query("SELECT DATABASE()")->fetchColumn();

        $testUsage = [
            'reclamaciones' => [
                'codigo_reclamacion', 'idempotency_key', 'tipo_documento', 'numero_documento',
                'nombre_completo', 'telefono', 'email', 'domicilio', 'tipo_servicio', 'nombre_servicio',
                'tipo_registro', 'detalle_reclamacion', 'pedido_consumidor', 'declaracion_jurada'
            ],
            'contactos' => [
                'idempotency_key', 'nombre', 'telefono', 'email', 'servicio', 'mensaje'
            ],
            'email_outbox' => [
                'idempotency_key', 'type', 'recipient_email', 'recipient_name', 'subject', 'html_body'
            ],
            'evaluaciones_habilidades_blandas' => [
                'codigo_evaluacion', 'nombre_completo', 'email', 'report_access_token_hash'
            ]
        ];

        $isValid = true;
        $validationErrors = [];

        foreach ($testUsage as $table => $usedCols) {
            $realTable = $prefix . $table;
            $stmt = $this->pdo->prepare("
                SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl
            ");
            $stmt->execute([':db' => $dbName, ':tbl' => $realTable]);
            $dbCols = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $existingCols = [];
            $requiredCols = [];
            foreach ($dbCols as $row) {
                $cName = $row['COLUMN_NAME'];
                $existingCols[] = $cName;
                $isAutoInc = (strpos(strtolower($row['EXTRA']), 'auto_increment') !== false);
                $isNotNullNoDefault = ($row['IS_NULLABLE'] === 'NO' && $row['COLUMN_DEFAULT'] === null && !$isAutoInc);
                if ($isNotNullNoDefault) {
                    $requiredCols[] = $cName;
                }
            }

            // 1. Validar que todas las columnas usadas en el test existen realmente
            $nonExistent = array_diff($usedCols, $existingCols);
            if (!empty($nonExistent)) {
                $isValid = false;
                $validationErrors[] = "Columnas inexistentes en {$table}: " . implode(', ', $nonExistent);
            }

            // 2. Validar que todas las columnas obligatorias sin default estén cubiertas
            $missingRequired = array_diff($requiredCols, $usedCols);
            if (!empty($missingRequired)) {
                $isValid = false;
                $validationErrors[] = "Columnas obligatorias faltantes en prueba de {$table}: " . implode(', ', $missingRequired);
            }
        }

        $this->assert($isValid, "Validación previa confirma que los INSERT usan columnas vigentes y completan todos los campos obligatorios" . (!empty($validationErrors) ? " (" . implode('; ', $validationErrors) . ")" : ""));
    }

    private function testCarnetExtranjeriaInsertion(string $prefix): void {
        $tblRec = $prefix . 'reclamaciones';
        $idempKey = 'test_ce_' . bin2hex(random_bytes(8));

        $stmt = $this->pdo->prepare("
            INSERT INTO `{$tblRec}` (
                codigo_reclamacion, idempotency_key, tipo_documento, numero_documento,
                nombre_completo, telefono, email, domicilio, tipo_servicio, nombre_servicio,
                tipo_registro, detalle_reclamacion, pedido_consumidor, declaracion_jurada
            ) VALUES (
                'REC-CE-001', :k, 'Carnet de Extranjería', '001987654-B',
                'Pierre Dupont', '987112233', 'pierre@example.com', 'Av. Larco 500',
                'Capacitación Profesional', 'Gestión de Proyectos', 'Reclamo',
                'Detalle de reclamación con carnet', 'Solución solicitada', 1
            )
        ");
        $success = $stmt->execute([':k' => $idempKey]);
        $this->assert($success, "Inserción exitosa de registro con 'Carnet de Extranjería' en base de datos");

        $stmtCheck = $this->pdo->prepare("SELECT tipo_documento, numero_documento FROM `{$tblRec}` WHERE idempotency_key = :k");
        $stmtCheck->execute([':k' => $idempKey]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        $this->assert($row && $row['tipo_documento'] === 'Carnet de Extranjería' && $row['numero_documento'] === '001987654-B', "Consulta confirma valor canónico 'Carnet de Extranjería' almacenado correctamente");
    }

    private function testAllUniqueConstraints(string $prefix): void {
        $idempKey = 'test_idemp_' . bin2hex(random_bytes(8));

        // 1. uk_rec_idempotency en reclamaciones
        $tblRec = $prefix . 'reclamaciones';
        $stmtRec1 = $this->pdo->prepare("
            INSERT INTO `{$tblRec}` (
                codigo_reclamacion, idempotency_key, tipo_documento, numero_documento,
                nombre_completo, telefono, email, domicilio, tipo_servicio, nombre_servicio,
                tipo_registro, detalle_reclamacion, pedido_consumidor, declaracion_jurada
            ) VALUES (
                'REC-UK-1', :k, 'DNI', '12345678',
                'User 1', '999999999', 'u1@test.com', 'Av 1',
                'Capacitación Profesional', 'Curso', 'Reclamo',
                'Detalle 1', 'Pedido 1', 1
            )
        ");
        $stmtRec1->execute([':k' => $idempKey]);

        $recDupFailed = false;
        try {
            $stmtRec2 = $this->pdo->prepare("
                INSERT INTO `{$tblRec}` (
                    codigo_reclamacion, idempotency_key, tipo_documento, numero_documento,
                    nombre_completo, telefono, email, domicilio, tipo_servicio, nombre_servicio,
                    tipo_registro, detalle_reclamacion, pedido_consumidor, declaracion_jurada
                ) VALUES (
                    'REC-UK-2', :k, 'DNI', '87654321',
                    'User 2', '988888888', 'u2@test.com', 'Av 2',
                    'Capacitación Profesional', 'Curso 2', 'Reclamo',
                    'Detalle 2', 'Pedido 2', 1
                )
            ");
            $stmtRec2->execute([':k' => $idempKey]);
        } catch (\PDOException $e) {
            $recDupFailed = ((int)($e->errorInfo[1] ?? 0) === 1062 || $e->getCode() === '23000');
        }
        $this->assert($recDupFailed, "Restricción UNIQUE uk_rec_idempotency en reclamaciones bloquea duplicado (1062)");

        // 2. uk_cnt_idempotency en contactos (incluyendo todas las columnas obligatorias reales, especialmente telefono)
        $tblCnt = $prefix . 'contactos';
        $stmtCnt1 = $this->pdo->prepare("
            INSERT INTO `{$tblCnt}` (
                idempotency_key, nombre, telefono, email, servicio, mensaje
            ) VALUES (
                :k, 'User Contact 1', '999888777', 'c1@test.com', 'Capacitación Profesional', 'Mensaje de contacto 1'
            )
        ");
        $stmtCnt1->execute([':k' => $idempKey]);

        $cntDupFailed = false;
        try {
            $stmtCnt2 = $this->pdo->prepare("
                INSERT INTO `{$tblCnt}` (
                    idempotency_key, nombre, telefono, email, servicio, mensaje
                ) VALUES (
                    :k, 'User Contact 2', '999888666', 'c2@test.com', 'Capacitación Profesional', 'Mensaje de contacto 2'
                )
            ");
            $stmtCnt2->execute([':k' => $idempKey]);
        } catch (\PDOException $e) {
            $cntDupFailed = ((int)($e->errorInfo[1] ?? 0) === 1062 || $e->getCode() === '23000');
        }
        $this->assert($cntDupFailed, "Restricción UNIQUE uk_cnt_idempotency en contactos bloquea duplicado (1062)");

        // 3. uk_outbox_idemp_type en email_outbox (compuesta: idempotency_key + type)
        $tblOutbox = $prefix . 'email_outbox';
        $stmtOut1 = $this->pdo->prepare("
            INSERT INTO `{$tblOutbox}` (
                idempotency_key, type, recipient_email, recipient_name, subject, html_body
            ) VALUES (
                :k, 'claim_admin', 'admin@example.com', 'Admin 1', 'Asunto 1', '<p>Cuerpo 1</p>'
            )
        ");
        $stmtOut1->execute([':k' => $idempKey]);

        $outboxDupFailed = false;
        try {
            $stmtOut2 = $this->pdo->prepare("
                INSERT INTO `{$tblOutbox}` (
                    idempotency_key, type, recipient_email, recipient_name, subject, html_body
                ) VALUES (
                    :k, 'claim_admin', 'admin2@example.com', 'Admin 2', 'Asunto 2', '<p>Cuerpo 2</p>'
                )
            ");
            $stmtOut2->execute([':k' => $idempKey]);
        } catch (\PDOException $e) {
            $outboxDupFailed = ((int)($e->errorInfo[1] ?? 0) === 1062 || $e->getCode() === '23000');
        }
        $this->assert($outboxDupFailed, "Restricción UNIQUE uk_outbox_idemp_type en outbox bloquea duplicado compuesto (1062)");

        // 4. uk_eval_token_hash en evaluaciones_habilidades_blandas (esquema v2 actual: codigo_evaluacion, nombre_completo, email, report_access_token_hash)
        $tokenHash = hash('sha256', 'test_secret_token_' . bin2hex(random_bytes(8)));
        $tblEval = $prefix . 'evaluaciones_habilidades_blandas';

        $stmtEval1 = $this->pdo->prepare("
            INSERT INTO `{$tblEval}` (
                codigo_evaluacion, nombre_completo, email, report_access_token_hash
            ) VALUES (
                'EVA-TEST-001', 'Participante Uno', 'eval1@example.com', :th
            )
        ");
        $stmtEval1->execute([':th' => $tokenHash]);

        $evalDupFailed = false;
        try {
            // Fila con codigo_evaluacion diferente ('EVA-TEST-002') y datos distintos, pero repitiendo el mismo token hash (:th)
            $stmtEval2 = $this->pdo->prepare("
                INSERT INTO `{$tblEval}` (
                    codigo_evaluacion, nombre_completo, email, report_access_token_hash
                ) VALUES (
                    'EVA-TEST-002', 'Participante Dos', 'eval2@example.com', :th
                )
            ");
            $stmtEval2->execute([':th' => $tokenHash]);
        } catch (\PDOException $e) {
            $evalDupFailed = ((int)($e->errorInfo[1] ?? 0) === 1062 || $e->getCode() === '23000');
        }
        $this->assert($evalDupFailed, "Restricción UNIQUE uk_eval_token_hash en evaluaciones bloquea colisión de hash (1062)");
    }

    private function executeSqlScript(string $sql, string $prefix): void {
        $statements = $this->splitSqlStatements($sql, $prefix);
        foreach ($statements as $stmtSql) {
            $stmtSql = trim($stmtSql);
            if (!empty($stmtSql)) {
                $this->pdo->exec($stmtSql);
            }
        }
    }

    private function tryExecuteSqlScript(string $sql, string $prefix): bool {
        try {
            $this->executeSqlScript($sql, $prefix);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function splitSqlStatements(string $sql, string $prefix): array {
        $tables = [
            'reclamaciones', 'contactos', 'email_outbox',
            'evaluaciones_habilidades_blandas', 'respuestas_evaluacion',
            'cache_db', 'rate_limits', 'audit_logs',
            'v_ranking_evaluaciones', 'v_promedios_competencias'
        ];

        foreach ($tables as $tbl) {
            $sql = preg_replace('/`' . $tbl . '`/i', '`' . $prefix . $tbl . '`', $sql);
            $sql = preg_replace('/TABLE_NAME = \'' . $tbl . '\'/i', 'TABLE_NAME = \'' . $prefix . $tbl . '\'', $sql);
            $sql = preg_replace('/TABLE `' . $tbl . '`/i', 'TABLE `' . $prefix . $tbl . '`', $sql);
            $sql = preg_replace('/REFERENCES `' . $tbl . '`/i', 'REFERENCES `' . $prefix . $tbl . '`', $sql);
            $sql = preg_replace('/FROM `' . $tbl . '`/i', 'FROM `' . $prefix . $tbl . '`', $sql);
            $sql = preg_replace('/JOIN `' . $tbl . '`/i', 'JOIN `' . $prefix . $tbl . '`', $sql);
            $sql = preg_replace('/VIEW `' . $tbl . '`/i', 'VIEW `' . $prefix . $tbl . '`', $sql);
        }

        // Eliminar comentarios
        $lines = explode("\n", $sql);
        $cleanLines = [];
        foreach ($lines as $line) {
            $tLine = trim($line);
            if (str_starts_with($tLine, '--') || str_starts_with($tLine, '/*')) {
                continue;
            }
            $cleanLines[] = $line;
        }

        $cleanSql = implode("\n", $cleanLines);
        $rawStatements = explode(';', $cleanSql);
        return array_filter(array_map('trim', $rawStatements));
    }

    private function inspectSchema(string $prefix, array $baseTables): array {
        $result = [
            'tables'  => [],
            'columns' => [],
            'indexes' => [],
            'fks'     => []
        ];

        $dbName = $this->pdo->query("SELECT DATABASE()")->fetchColumn();

        foreach ($baseTables as $bTable) {
            $tbl = $prefix . $bTable;

            // 1. Motor y Collation de Tabla
            $stmtTbl = $this->pdo->prepare(
                "SELECT ENGINE, TABLE_COLLATION
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl"
            );
            $stmtTbl->execute([':db' => $dbName, ':tbl' => $tbl]);
            $tblRow = $stmtTbl->fetch(\PDO::FETCH_ASSOC);
            if (!$tblRow) {
                continue;
            }
            $result['tables'][$bTable] = [
                'engine'    => $tblRow['ENGINE'],
                'collation' => $tblRow['TABLE_COLLATION']
            ];

            // 2. Columnas (tipo, orden, nulabilidad, defaults, EXTRA, comentarios)
            $stmtCols = $this->pdo->prepare(
                "SELECT COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl
                 ORDER BY ORDINAL_POSITION"
            );
            $stmtCols->execute([':db' => $dbName, ':tbl' => $tbl]);
            $cols = $stmtCols->fetchAll(\PDO::FETCH_ASSOC);

            $result['columns'][$bTable] = [];
            foreach ($cols as $c) {
                $result['columns'][$bTable][$c['COLUMN_NAME']] = [
                    'position' => (int)$c['ORDINAL_POSITION'],
                    'type'     => strtolower($c['COLUMN_TYPE']),
                    'null'     => $c['IS_NULLABLE'],
                    'default'  => $c['COLUMN_DEFAULT'],
                    'extra'    => strtolower($c['EXTRA']),
                    'comment'  => $c['COLUMN_COMMENT']
                ];
            }

            // 3. Índices (nombre, unicidad, columnas en orden de secuencia)
            $stmtIdx = $this->pdo->prepare(
                "SELECT INDEX_NAME, NON_UNIQUE, COLUMN_NAME, SEQ_IN_INDEX
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl
                 ORDER BY INDEX_NAME, SEQ_IN_INDEX"
            );
            $stmtIdx->execute([':db' => $dbName, ':tbl' => $tbl]);
            $idxs = $stmtIdx->fetchAll(\PDO::FETCH_ASSOC);
            $result['indexes'][$bTable] = [];
            foreach ($idxs as $idx) {
                $iName = $idx['INDEX_NAME'];
                if (!isset($result['indexes'][$bTable][$iName])) {
                    $result['indexes'][$bTable][$iName] = [
                        'non_unique' => (int)$idx['NON_UNIQUE'],
                        'columns'    => []
                    ];
                }
                $result['indexes'][$bTable][$iName]['columns'][] = $idx['COLUMN_NAME'];
            }

            // 4. Foreign Keys (restricciones, columnas referenciadas y reglas ON DELETE / ON UPDATE)
            $stmtFk = $this->pdo->prepare(
                "SELECT kcu.CONSTRAINT_NAME, kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME,
                        rc.DELETE_RULE, rc.UPDATE_RULE
                 FROM information_schema.KEY_COLUMN_USAGE kcu
                 JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                   ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
                  AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                 WHERE kcu.TABLE_SCHEMA = :db AND kcu.TABLE_NAME = :tbl AND kcu.REFERENCED_TABLE_NAME IS NOT NULL"
            );
            $stmtFk->execute([':db' => $dbName, ':tbl' => $tbl]);
            $fks = $stmtFk->fetchAll(\PDO::FETCH_ASSOC);
            $result['fks'][$bTable] = [];
            foreach ($fks as $fk) {
                $refTableBase = str_replace($prefix, '', $fk['REFERENCED_TABLE_NAME']);
                $result['fks'][$bTable][$fk['COLUMN_NAME']] = [
                    'ref_table'   => $refTableBase,
                    'ref_col'     => $fk['REFERENCED_COLUMN_NAME'],
                    'delete_rule' => $fk['DELETE_RULE'],
                    'update_rule' => $fk['UPDATE_RULE']
                ];
            }
        }

        return $result;
    }

    private function dropPrefixedTables(string $prefix, array $baseTables): void {
        try {
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            foreach ($baseTables as $bTable) {
                $tbl = $prefix . $bTable;
                $this->pdo->exec("DROP TABLE IF EXISTS `{$tbl}`;");
            }
            $this->pdo->exec("DROP VIEW IF EXISTS `{$prefix}v_ranking_evaluaciones`;");
            $this->pdo->exec("DROP VIEW IF EXISTS `{$prefix}v_promedios_competencias`;");
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        } catch (\Throwable $e) {
            // Ignorar errores en limpieza de entorno de prueba
        }
    }
}

// Ejecución
$test = new DatabaseSchemaTest();
$test->run();