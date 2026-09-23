<?php
/**
 * PMO SOLUTIONS — Tests del Módulo de Habilidades Blandas (SoftSkillsTest.php)
 *
 * Tests unitarios y de integración para:
 *  - Lógica de scoring por competencia
 *  - Cálculo de score global y nivel de madurez
 *  - Generación de reportes estructurados
 *  - Validación de consultas DB (EXPLAIN para detectar full scans)
 *  - Integración del Circuit Breaker con la DB
 *
 * Ejecución:
 *   php tests/SoftSkillsTest.php
 *   php tests/SoftSkillsTest.php --verbose
 *
 * Requiere:
 *   - PHP 8.0+
 *   - Extensiones: PDO, json, gzcompress
 */

declare(strict_types=1);

// Bootstrap
define('PMO_APP_ACCESS', true);
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

// ─── Mini Framework de Testing ────────────────────────────────────────────────
class TestRunner {
    private static array $results = ['passed' => 0, 'failed' => 0, 'skipped' => 0];
    private static bool  $verbose = false;

    public static function setVerbose(bool $v): void {
        self::$verbose = $v;
    }

    public static function assertTrue(bool $condition, string $testName): void {
        self::assert($condition, $testName);
    }

    public static function assertEquals(mixed $expected, mixed $actual, string $testName): void {
        self::assert($expected === $actual, $testName, "Expected: " . print_r($expected, true) . " | Got: " . print_r($actual, true));
    }

    public static function assertGreaterThan(mixed $expected, mixed $actual, string $testName): void {
        self::assert($actual > $expected, $testName, "Expected > {$expected}, got: {$actual}");
    }

    public static function assertLessThanOrEqual(mixed $expected, mixed $actual, string $testName): void {
        self::assert($actual <= $expected, $testName, "Expected <= {$expected}, got: {$actual}");
    }

    public static function assertArrayHasKey(string $key, array $array, string $testName): void {
        self::assert(array_key_exists($key, $array), $testName, "Key '{$key}' not found in array");
    }

    public static function assertNotEmpty(mixed $value, string $testName): void {
        self::assert(!empty($value), $testName, "Expected non-empty value, got empty");
    }

    public static function assertInstanceOf(string $class, mixed $object, string $testName): void {
        self::assert($object instanceof $class, $testName, "Expected instance of {$class}");
    }

    public static function skip(string $testName, string $reason = ''): void {
        self::$results['skipped']++;
        echo "\e[33m  SKIP\e[0m {$testName}" . ($reason ? " — {$reason}" : '') . "\n";
    }

    private static function assert(bool $condition, string $testName, string $detail = ''): void {
        if ($condition) {
            self::$results['passed']++;
            if (self::$verbose) {
                echo "\e[32m  PASS\e[0m {$testName}\n";
            } else {
                echo '.';
            }
        } else {
            self::$results['failed']++;
            echo "\n\e[31m  FAIL\e[0m {$testName}";
            if ($detail) echo "\n       → {$detail}";
            echo "\n";
        }
    }

    public static function printSummary(): void {
        $total  = array_sum(self::$results);
        $passed = self::$results['passed'];
        $failed = self::$results['failed'];
        $skip   = self::$results['skipped'];

        echo "\n\n" . str_repeat('─', 60) . "\n";
        echo "Tests ejecutados: {$total} | ";
        echo "\e[32mPasados: {$passed}\e[0m | ";
        echo "\e[31mFallados: {$failed}\e[0m | ";
        echo "\e[33mOmitidos: {$skip}\e[0m\n";
        echo str_repeat('─', 60) . "\n";

        if ($failed > 0) {
            echo "\e[31m✗ Algunos tests fallaron.\e[0m\n";
            exit(1);
        } else {
            echo "\e[32m✓ Todos los tests pasaron.\e[0m\n";
        }
    }
}

$verbose = in_array('--verbose', $argv ?? [], true);
TestRunner::setVerbose($verbose);

// =============================================================================
// SUITE 1: SoftSkillsModel — Catálogo y Estructura
// =============================================================================
echo "\n📋 Suite 1: Catálogo de Competencias\n";
echo str_repeat('─', 40) . "\n";

$model   = new App\Models\SoftSkillsModel();
$catalog = $model->getCatalog();

TestRunner::assertNotEmpty($catalog, 'Catálogo no debe estar vacío');
TestRunner::assertEquals(4, count($catalog), 'Debe tener exactamente 4 competencias');

$expectedCompetencies = ['comunicacion', 'trabajo_equipo', 'resolucion_problemas', 'adaptabilidad'];
foreach ($expectedCompetencies as $comp) {
    TestRunner::assertArrayHasKey($comp, $catalog, "Competencia '{$comp}' debe existir");
}

// Verificar estructura de cada competencia
foreach ($catalog as $compKey => $compData) {
    TestRunner::assertArrayHasKey('label',       $compData, "{$compKey}: debe tener 'label'");
    TestRunner::assertArrayHasKey('questions',   $compData, "{$compKey}: debe tener 'questions'");
    TestRunner::assertEquals(5, count($compData['questions']), "{$compKey}: debe tener exactamente 5 preguntas");

    foreach ($compData['questions'] as $qi => $question) {
        TestRunner::assertArrayHasKey('scenario', $question, "{$compKey}[{$qi}]: debe tener 'scenario'");
        TestRunner::assertArrayHasKey('options',  $question, "{$compKey}[{$qi}]: debe tener 'options'");
        TestRunner::assertEquals(4, count($question['options']), "{$compKey}[{$qi}]: debe tener 4 opciones");

        // Verificar que la primera opción siempre tenga el puntaje máximo
        TestRunner::assertEquals(25, $question['options'][0]['points'], "{$compKey}[{$qi}]: opción A debe valer 25 pts");
        // Verificar que haya una opción con 0 puntos (la peor)
        $minPoints = min(array_column($question['options'], 'points'));
        TestRunner::assertEquals(0, $minPoints, "{$compKey}[{$qi}]: debe haber una opción con 0 pts");
    }
}

// =============================================================================
// SUITE 2: Lógica de Scoring
// =============================================================================
echo "\n📊 Suite 2: Lógica de Scoring\n";
echo str_repeat('─', 40) . "\n";

// Test: respuestas perfectas (siempre la opción 1 = 25 pts cada una)
$perfectAnswers = ['1' => 1, '2' => 1, '3' => 1, '4' => 1, '5' => 1];
$perfectResult  = $model->calculateScore('comunicacion', $perfectAnswers);

TestRunner::assertEquals(100, $perfectResult['score'],  'Respuestas perfectas = 100 pts');
TestRunner::assertEquals(5,   count($perfectResult['details']), 'Resultado debe tener 5 detalles');
TestRunner::assertEquals('comunicacion', $perfectResult['competency'], 'Competencia debe ser comunicacion');

// Test: respuestas mínimas (siempre la opción 4 = 0 pts)
$worstAnswers = ['1' => 4, '2' => 4, '3' => 4, '4' => 4, '5' => 4];
$worstResult  = $model->calculateScore('adaptabilidad', $worstAnswers);

TestRunner::assertEquals(0, $worstResult['score'], 'Respuestas peores = 0 pts');

// Test: respuestas mixtas (opción 2 = 18 pts × 5 = 90 pts)
$mixedAnswers = ['1' => 2, '2' => 2, '3' => 2, '4' => 2, '5' => 2];
$mixedResult  = $model->calculateScore('trabajo_equipo', $mixedAnswers);

TestRunner::assertEquals(90, $mixedResult['score'], 'Opción 2 × 5 = 90 pts');

// Test: respuestas con opción inválida (se normaliza a 1)
$invalidAnswers = ['1' => 99, '2' => -1, '3' => 0, '4' => 5, '5' => 1];
$invalidResult  = $model->calculateScore('resolucion_problemas', $invalidAnswers);

TestRunner::assertGreaterThan(-1, $invalidResult['score'], 'Score con opciones inválidas no debe ser negativo');
TestRunner::assertLessThanOrEqual(100, $invalidResult['score'], 'Score con opciones inválidas no debe superar 100');

// =============================================================================
// SUITE 3: Score Global y Nivel de Madurez
// =============================================================================
echo "\n🏆 Suite 3: Score Global y Madurez\n";
echo str_repeat('─', 40) . "\n";

// Test: nivel Sobresaliente (score >= 80)
$highScores  = ['comunicacion' => 90, 'trabajo_equipo' => 85, 'resolucion_problemas' => 88, 'adaptabilidad' => 82];
$highGlobal  = $model->calculateGlobalScore($highScores);

TestRunner::assertEquals(86, $highGlobal['global_score'],  'Promedio de altos scores = 86');
TestRunner::assertEquals('Sobresaliente', $highGlobal['maturity_level'], 'Score >= 80 = Sobresaliente');
TestRunner::assertArrayHasKey('strengths',        $highGlobal, 'Debe incluir fortalezas');
TestRunner::assertArrayHasKey('areas_to_improve', $highGlobal, 'Debe incluir áreas de mejora');
TestRunner::assertArrayHasKey('radar_data',       $highGlobal, 'Debe incluir datos radar');
TestRunner::assertEquals(2, count($highGlobal['strengths']),        'Debe identificar 2 fortalezas');
TestRunner::assertEquals(2, count($highGlobal['areas_to_improve']), 'Debe identificar 2 áreas de mejora');

// Test: nivel Competente (60-79)
$midScores   = ['comunicacion' => 70, 'trabajo_equipo' => 65, 'resolucion_problemas' => 72, 'adaptabilidad' => 68];
$midGlobal   = $model->calculateGlobalScore($midScores);
TestRunner::assertEquals('Competente', $midGlobal['maturity_level'], 'Score 60-79 = Competente');

// Test: nivel En Desarrollo (40-59)
$devScores   = ['comunicacion' => 50, 'trabajo_equipo' => 45, 'resolucion_problemas' => 55, 'adaptabilidad' => 48];
$devGlobal   = $model->calculateGlobalScore($devScores);
TestRunner::assertEquals('En Desarrollo', $devGlobal['maturity_level'], 'Score 40-59 = En Desarrollo');

// Test: nivel Inicial (0-39)
$lowScores   = ['comunicacion' => 20, 'trabajo_equipo' => 30, 'resolucion_problemas' => 25, 'adaptabilidad' => 35];
$lowGlobal   = $model->calculateGlobalScore($lowScores);
TestRunner::assertEquals('Inicial', $lowGlobal['maturity_level'], 'Score 0-39 = Inicial');

// Test: fortalezas y áreas son coherentes
$allAnswers  = [];
foreach ($catalog as $compKey => $compData) {
    $allAnswers[$compKey] = ['1' => 1, '2' => 1, '3' => 1, '4' => 1, '5' => 1];
}
$report = $model->generateReport('EVA-2026-TEST1', [
    'nombre_completo' => 'Test User',
    'email'           => 'test@pmo.com',
    'tipo_evaluacion' => 'Individual',
], $allAnswers);

TestRunner::assertArrayHasKey('meta',         $report, 'Reporte debe tener meta');
TestRunner::assertArrayHasKey('participant',  $report, 'Reporte debe tener participant');
TestRunner::assertArrayHasKey('summary',      $report, 'Reporte debe tener summary');
TestRunner::assertArrayHasKey('competencies', $report, 'Reporte debe tener competencies');
TestRunner::assertEquals('EVA-2026-TEST1', $report['meta']['codigo_evaluacion'], 'Código debe coincidir');
TestRunner::assertEquals(100, $report['summary']['global_score'], 'Score perfecto debe ser 100');

// =============================================================================
// SUITE 4: Generación de Reporte — Serialización/Compresión
// =============================================================================
echo "\n📄 Suite 4: Serialización del Reporte\n";
echo str_repeat('─', 40) . "\n";

// Test: serialización JSON es válida
$jsonReport = json_encode($report);
TestRunner::assertTrue($jsonReport !== false, 'Reporte debe ser serializable a JSON');

// Test: compresión y descompresión
$compressed   = base64_encode(gzcompress(json_encode($report), 6));
TestRunner::assertNotEmpty($compressed, 'Reporte comprimido no debe estar vacío');

$decompressed = json_decode(gzuncompress(base64_decode($compressed)), true);
TestRunner::assertEquals($report['meta']['codigo_evaluacion'], $decompressed['meta']['codigo_evaluacion'],
    'Reporte descomprimido debe conservar el código');

// Test: relación de compresión
$originalSize   = strlen(json_encode($report));
$compressedSize = strlen($compressed);
TestRunner::assertGreaterThan(0, $originalSize - $compressedSize, 'La compresión debe reducir el tamaño');

if ($verbose) {
    echo "  ℹ Compresión: " . $originalSize . " → " . $compressedSize . " bytes ("
        . round((1 - $compressedSize / $originalSize) * 100, 1) . "% reducción)\n";
}

// =============================================================================
// SUITE 5: Cache Layer
// =============================================================================
echo "\n🗄 Suite 5: Capa de Caché\n";
echo str_repeat('─', 40) . "\n";

$cacheKey = 'test_softskills_' . md5(microtime());

// Test: set y get básico
$testValue = ['data' => 'PMO Test', 'score' => 85];
$setResult = App\Core\Cache::set($cacheKey, $testValue, 60);
TestRunner::assertTrue($setResult, 'Cache::set debe retornar true');

$getResult = App\Core\Cache::get($cacheKey);
TestRunner::assertEquals($testValue, $getResult, 'Cache::get debe retornar el valor guardado');

// Test: remember (get-or-set)
$rememberKey    = 'test_remember_' . md5(microtime());
$callbackCalled = false;
$rememberResult = App\Core\Cache::remember($rememberKey, 60, function () use (&$callbackCalled) {
    $callbackCalled = true;
    return ['cached' => true];
});

TestRunner::assertTrue($callbackCalled, 'Callback debe ejecutarse en cache miss');
TestRunner::assertEquals(['cached' => true], $rememberResult, 'remember debe retornar el valor del callback');

// Segunda llamada no debe ejecutar el callback
$callbackCalled = false;
App\Core\Cache::remember($rememberKey, 60, function () use (&$callbackCalled) {
    $callbackCalled = true;
    return ['cached' => true];
});
TestRunner::assertTrue(!$callbackCalled, 'Callback NO debe ejecutarse en cache hit');

// Test: delete
$deleteResult = App\Core\Cache::delete($cacheKey);
TestRunner::assertTrue($deleteResult, 'Cache::delete debe retornar true');
TestRunner::assertTrue(App\Core\Cache::get($cacheKey) === null, 'Valor eliminado debe retornar null');

// Test: driver usado
$driver = App\Core\Cache::getDriver();
TestRunner::assertTrue(in_array($driver, ['redis', 'apcu', 'file']), "Driver debe ser redis, apcu o file. Es: {$driver}");

if ($verbose) {
    echo "  ℹ Driver de caché activo: " . $driver . "\n";
}

// =============================================================================
// SUITE 6: Circuit Breaker
// =============================================================================
echo "\n⚡ Suite 6: Circuit Breaker\n";
echo str_repeat('─', 40) . "\n";

$cbName = 'test_service_' . substr(md5(microtime()), 0, 6);
$cb = new App\Core\CircuitBreaker($cbName, 3, 5, 2);

// Test: estado inicial = CLOSED
TestRunner::assertEquals(App\Core\CircuitBreaker::STATE_CLOSED, $cb->getState(), 'Estado inicial debe ser CLOSED');
TestRunner::assertTrue($cb->isAvailable(), 'CircuitBreaker debe estar disponible en CLOSED');

// Test: operación exitosa en CLOSED
$result = $cb->call(fn() => 'success');
TestRunner::assertEquals('success', $result, 'Debe retornar el resultado de la operación');

// Test: fallas abren el circuito
for ($i = 0; $i < 3; $i++) {
    try {
        $cb->call(fn() => throw new \RuntimeException("Simulated failure #{$i}"));
    } catch (\Throwable $e) {
        // Esperado
    }
}

TestRunner::assertEquals(App\Core\CircuitBreaker::STATE_OPEN, $cb->getState(), 'Después de 3 fallos debe estar OPEN');
TestRunner::assertTrue(!$cb->isAvailable(), 'CircuitBreaker no debe estar disponible en OPEN');

// Test: llamada en OPEN lanza CircuitOpenException
try {
    $cb->call(fn() => 'should_not_reach');
    TestRunner::assertTrue(false, 'Debe lanzar CircuitOpenException en OPEN');
} catch (\App\Core\CircuitOpenException $e) {
    TestRunner::assertTrue(true, 'Debe lanzar CircuitOpenException en OPEN');
}

// Test: stats del circuit breaker
$stats = $cb->getStats();
TestRunner::assertArrayHasKey('state',          $stats, 'Stats debe tener state');
TestRunner::assertArrayHasKey('failures',       $stats, 'Stats debe tener failures');
TestRunner::assertArrayHasKey('last_failure_at',$stats, 'Stats debe tener last_failure_at');
TestRunner::assertEquals(3, $stats['failures'], 'Debe registrar 3 fallos');

// Test: reset manual
$cb->reset();
TestRunner::assertEquals(App\Core\CircuitBreaker::STATE_CLOSED, $cb->getState(), 'Después de reset debe ser CLOSED');

// =============================================================================
// SUITE 7: RetryHandler con Exponential Backoff
// =============================================================================
echo "\n🔄 Suite 7: RetryHandler\n";
echo str_repeat('─', 40) . "\n";

// Test: éxito en primer intento
$attempts    = 0;
$retryResult = App\Core\RetryHandler::execute(function () use (&$attempts) {
    $attempts++;
    return 'ok';
}, 3);
TestRunner::assertEquals('ok', $retryResult, 'Debe retornar resultado exitoso');
TestRunner::assertEquals(1, $attempts, 'Solo debe llamarse 1 vez si tiene éxito');

// Test: reintento en fallo recuperable
$attempts      = 0;
$retryResult2  = App\Core\RetryHandler::execute(function () use (&$attempts) {
    $attempts++;
    if ($attempts < 3) throw new \RuntimeException("Fallo temporal #{$attempts}");
    return 'recovered';
}, 3, 1, 1.5, 10); // Delays muy pequeños para el test
TestRunner::assertEquals('recovered', $retryResult2, 'Debe recuperarse en el 3er intento');
TestRunner::assertEquals(3, $attempts, 'Debe intentar 3 veces');

// Test: lanza excepción si no recuperable
try {
    App\Core\RetryHandler::execute(
        fn() => throw new \InvalidArgumentException('No retryable'),
        3, 1, 1.0, 10,
        [\PDOException::class] // Solo reintentar en PDOException
    );
    TestRunner::assertTrue(false, 'Debe propagar excepción no recuperable inmediatamente');
} catch (\InvalidArgumentException $e) {
    TestRunner::assertTrue(true, 'Excepción no recuperable debe propagarse sin reintentos');
}

// Test: máx intentos agotados lanza última excepción
try {
    App\Core\RetryHandler::execute(
        fn() => throw new \RuntimeException('Always fails'),
        2, 1, 1.0, 5
    );
    TestRunner::assertTrue(false, 'Debe lanzar excepción después de agotar reintentos');
} catch (\RuntimeException $e) {
    TestRunner::assertEquals('Always fails', $e->getMessage(), 'Debe propagar la última excepción');
}

// =============================================================================
// SUITE 8: Tests de BD (EXPLAIN para validar índices) — Solo si DB disponible
// =============================================================================
echo "\n🗃 Suite 8: Optimización de Consultas DB\n";
echo str_repeat('─', 40) . "\n";

$pdo = App\Core\Database::getConnection();

if ($pdo === null) {
    TestRunner::skip('EXPLAIN evaluaciones — estado', 'DB deshabilitada o no disponible');
    TestRunner::skip('EXPLAIN evaluaciones — email', 'DB deshabilitada o no disponible');
    TestRunner::skip('EXPLAIN respuestas — join', 'DB deshabilitada o no disponible');
} else {
    // Test: consulta por estado usa índice
    try {
        $explain = $pdo->query(
            "EXPLAIN SELECT id, codigo_evaluacion, nombre_completo, score_global
             FROM evaluaciones_habilidades_blandas
             WHERE estado = 'Completada'
             ORDER BY fecha_registro DESC
             LIMIT 20"
        )->fetch(\PDO::FETCH_ASSOC);

        $usesIndex = $explain && !empty($explain['key']) && $explain['key'] !== null;
        TestRunner::assertTrue($usesIndex, "Consulta por estado debe usar índice (key: " . ($explain['key'] ?? 'none') . ")");
    } catch (\Exception $e) {
        TestRunner::skip('EXPLAIN evaluaciones — estado', 'Tabla no existe todavía: ' . $e->getMessage());
    }

    // Test: consulta por email usa índice
    try {
        $explain2 = $pdo->query(
            "EXPLAIN SELECT id, codigo_evaluacion, score_global, nivel_madurez
             FROM evaluaciones_habilidades_blandas
             WHERE email = 'test@pmo.com'
             LIMIT 5"
        )->fetch(\PDO::FETCH_ASSOC);

        $usesIndex2 = $explain2 && !empty($explain2['key']) && $explain2['key'] !== null;
        TestRunner::assertTrue($usesIndex2, "Consulta por email debe usar idx_eval_email");
    } catch (\Exception $e) {
        TestRunner::skip('EXPLAIN evaluaciones — email', 'Tabla no existe todavía: ' . $e->getMessage());
    }
}

// =============================================================================
// SUITE 9: LogArchiver
// =============================================================================
echo "\n📁 Suite 9: LogArchiver\n";
echo str_repeat('─', 40) . "\n";

// Test: quickClean no lanza excepciones
try {
    App\Core\LogArchiver::quickClean();
    TestRunner::assertTrue(true, 'quickClean no debe lanzar excepciones');
} catch (\Throwable $e) {
    TestRunner::assertTrue(false, 'quickClean no debe lanzar excepciones: ' . $e->getMessage());
}

// Test: run retorna estructura correcta
$archiveReport = App\Core\LogArchiver::run(false);
TestRunner::assertArrayHasKey('compressed',   $archiveReport, 'Reporte debe tener compressed');
TestRunner::assertArrayHasKey('archived',     $archiveReport, 'Reporte debe tener archived');
TestRunner::assertArrayHasKey('deleted',      $archiveReport, 'Reporte debe tener deleted');
TestRunner::assertArrayHasKey('bytes_freed',  $archiveReport, 'Reporte debe tener bytes_freed');
TestRunner::assertArrayHasKey('errors',       $archiveReport, 'Reporte debe tener errors');

// =============================================================================
// SUITE 10: QueryBuilder con Paginación
// =============================================================================
echo "\n🔍 Suite 10: QueryBuilder\n";
echo str_repeat('─', 40) . "\n";

if ($pdo === null) {
    TestRunner::skip('QueryBuilder paginate', 'DB deshabilitada');
    TestRunner::skip('QueryBuilder invalid column', 'DB deshabilitada');
} else {
    // Test: columna inválida lanza excepción
    try {
        $qb = new App\Core\QueryBuilder($pdo, 'contactos');
        $qb->select('nombre; DROP TABLE contactos; --')->get();
        TestRunner::assertTrue(false, 'Columna con SQL injection debe lanzar excepción');
    } catch (\InvalidArgumentException $e) {
        TestRunner::assertTrue(true, 'SQL injection en columna debe ser rechazado');
    }

    // Test: paginación retorna estructura correcta
    try {
        $qb     = new App\Core\QueryBuilder($pdo, 'contactos');
        $result = $qb->select('id', 'nombre', 'email')->paginate(1, 10);

        TestRunner::assertArrayHasKey('data', $result, 'Paginación debe retornar data');
        TestRunner::assertArrayHasKey('meta', $result, 'Paginación debe retornar meta');
        TestRunner::assertArrayHasKey('current_page', $result['meta'], 'Meta debe tener current_page');
        TestRunner::assertArrayHasKey('per_page',     $result['meta'], 'Meta debe tener per_page');
        TestRunner::assertArrayHasKey('total',        $result['meta'], 'Meta debe tener total');
        TestRunner::assertArrayHasKey('last_page',    $result['meta'], 'Meta debe tener last_page');
        TestRunner::assertEquals(1,  $result['meta']['current_page'], 'current_page debe ser 1');
        TestRunner::assertEquals(10, $result['meta']['per_page'],     'per_page debe ser 10');
    } catch (\Exception $e) {
        TestRunner::skip('QueryBuilder paginate', 'Error de DB: ' . $e->getMessage());
    }
}

// =============================================================================
// RESUMEN FINAL
// =============================================================================
TestRunner::printSummary();
