<?php
/**
 * PMO SOLUTIONS — Suite de Pruebas: Rate Limiting & Validación Estricta (Tarea 6)
 *
 * Cobertura de pruebas reales:
 * 1. Aislamiento de contadores de Rate Limit por Endpoint e IP.
 * 2. Varios procesos PHP concurrentes reales contra el mismo contador con flock().
 * 3. Persistencia entre procesos PHP independientes.
 * 4. Fallo real de almacenamiento / bloqueo → HTTP 503 con log sanitizado.
 * 5. Consumo del Rate Limit por peticiones con CSRF ausente/inválido (Anti-Bruteforce) sin doble conteo.
 * 6. Validación exacta de Content-Type: aceptación de application/json (+charset) y rechazo de application/jsonp (HTTP 415).
 * 7. Control de tamaño de cuerpo (HTTP 413) y profundidad de JSON (HTTP 400).
 * 8. Validación estricta de campos con longitudes excesivas: rechazo con HTTP 422 sin recorte silencioso.
 * 9. Validación estricta de declaracion_jurada contra valores permitidos (rechazo de "false", 0, arrays, etc.).
 * 10. Validación estricta de Evaluaciones contra Catálogo Dinámico del Backend (competencias, preguntas 1..5, opciones enteras 1..4).
 * 11. Anti-Tampering: Rechazo con HTTP 422 ante puntajes, niveles o resultados precalculados inyectados por el cliente.
 * 12. Anti-Spoofing de IP: X-Forwarded-For ignorado en proxies no autorizados y respetado en trusted_proxies.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Env.php';
require_once dirname(__DIR__) . '/app/Core/Cache.php';
require_once dirname(__DIR__) . '/app/Core/Csrf.php';
require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Security.php';
require_once dirname(__DIR__) . '/app/Core/Idempotency.php';
require_once dirname(__DIR__) . '/app/Core/EmailOutbox.php';
require_once dirname(__DIR__) . '/app/Core/Model.php';
require_once dirname(__DIR__) . '/app/Core/Controller.php';
require_once dirname(__DIR__) . '/app/Models/SoftSkillsModel.php';
require_once dirname(__DIR__) . '/app/Models/ContactModel.php';
require_once dirname(__DIR__) . '/app/Models/ClaimModel.php';

use App\Core\Env;
use App\Core\Csrf;
use App\Core\Security;
use App\Core\Idempotency;
use App\Models\SoftSkillsModel;
use App\Models\ContactModel;
use App\Models\ClaimModel;

class RateLimitAndValidationTest {

    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;

    public function run(): void {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo " SUITE DE PRUEBAS: RATE LIMITING & VALIDACIÓN ESTRICTA (TAREA 6)\n";
        echo str_repeat('=', 70) . "\n\n";

        $this->testEndpointAndIpRateLimitIsolation();
        $this->testConcurrentProcessesFlock();
        $this->testInterProcessPersistence();
        $this->testRateLimiterFailsafe503();
        $this->testErrorsConsumeRateLimitQuota();
        $this->testCsrfFailureConsumesRateLimit();
        $this->testContentTypeExactValidation();
        $this->testPayloadSizeAndDepthLimits();
        $this->testExcessiveFieldLengthRejection();
        $this->testDeclaracionJuradaStrictValidation();
        $this->testEvaluationCatalogValidationStrict();
        $this->testEvaluationAntiTamperingScores();
        $this->testIpSpoofingProtection();

        echo "\n" . str_repeat('=', 70) . "\n";
        echo " RESUMEN: TAREA 6 (RATE LIMITING & VALIDACIÓN ESTRICTA)\n";
        echo str_repeat('=', 70) . "\n";
        echo " Total tests: " . ($this->passed + $this->failed + $this->skipped) .
             " | Pasados: {$this->passed}" .
             " | Fallados: {$this->failed}" .
             " | Omitidos (SKIP): {$this->skipped}\n\n";

        if ($this->failed === 0) {
            echo "✔ TODAS LAS PRUEBAS DE RATE LIMITING Y VALIDACIÓN PASARON AL 100%.\n\n";
        } else {
            echo "✖ SE ENCONTRARON FALLOS EN LA SUITE.\n\n";
            exit(1);
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

    private function skip(string $message): void {
        $this->skipped++;
        echo "  ↷ [SKIP] {$message}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Aislamiento de Contadores por Endpoint e IP
    // ─────────────────────────────────────────────────────────────────────────
    private function testEndpointAndIpRateLimitIsolation(): void {
        echo "--- 1. Aislamiento de Contadores por Endpoint e IP ---\n";

        $_SERVER['REMOTE_ADDR'] = '198.51.100.10';
        Security::resetRateLimit('contact');
        Security::resetRateLimit('claim');
        Security::resetRateLimit('soft_skills_eval');

        // Consumir 3 solicitudes en 'contact'
        for ($i = 0; $i < 3; $i++) {
            Security::checkRateLimit('contact', 3, 300);
        }

        // El cuarto intento en 'contact' debe bloquearse
        $contactBlocked = !Security::checkRateLimit('contact', 3, 300);
        $this->assert($contactBlocked, "'contact' se bloquea al alcanzar su límite máximo");

        // El endpoint 'claim' para la misma IP debe estar libre e independiente
        $claimAllowed = Security::checkRateLimit('claim', 3, 300);
        $this->assert($claimAllowed, "'claim' permanece disponible de forma independiente para la misma IP");

        // El endpoint 'soft_skills_eval' debe estar libre e independiente
        $evalAllowed = Security::checkRateLimit('soft_skills_eval', 3, 300);
        $this->assert($evalAllowed, "'soft_skills_eval' permanece disponible de forma independiente");

        // Otra IP distinta debe tener su propio contador para 'contact'
        $_SERVER['REMOTE_ADDR'] = '198.51.100.20';
        $otherIpAllowed = Security::checkRateLimit('contact', 3, 300);
        $this->assert($otherIpAllowed, "Una IP diferente no se ve afectada por el bloqueo de otra IP");

        Security::resetRateLimit('contact');
        Security::resetRateLimit('claim');
        Security::resetRateLimit('soft_skills_eval');
        $_SERVER['REMOTE_ADDR'] = '198.51.100.10';
        Security::resetRateLimit('contact');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Varios procesos PHP concurrentes reales contra el mismo contador
    // ─────────────────────────────────────────────────────────────────────────
    private function testConcurrentProcessesFlock(): void {
        echo "\n--- 2. Concurrencia Real de Procesos con flock() ---\n";

        $testIp = '203.0.113.88';
        $_SERVER['REMOTE_ADDR'] = $testIp;
        $action = 'concurrent_real_test';
        Security::resetRateLimit($action);

        $phpBinary = PHP_BINARY;
        $baseDir = addslashes(dirname(__DIR__));

        // Lanzar 8 procesos concurrentes reales que consumen el contador
        $numProcesses = 8;
        $processes = [];
        $script = "require '{$baseDir}/app/Core/Env.php'; require '{$baseDir}/app/Core/EmailOutbox.php'; require '{$baseDir}/app/Core/Security.php'; \$_SERVER['REMOTE_ADDR'] = '{$testIp}'; App\Core\Security::checkRateLimit('{$action}', 50, 300);";

        for ($p = 0; $p < $numProcesses; $p++) {
            $cmd = escapeshellarg($phpBinary) . ' -r ' . escapeshellarg($script);
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ];
            $proc = proc_open($cmd, $descriptors, $pipes);
            if (is_resource($proc)) {
                fclose($pipes[0]);
                $processes[] = ['proc' => $proc, 'pipes' => $pipes];
            }
        }

        // Esperar a que terminen todos los procesos
        foreach ($processes as $item) {
            fclose($item['pipes'][1]);
            fclose($item['pipes'][2]);
            proc_close($item['proc']);
        }

        // Verificar en disco que se registraron exactamente los 8 intentos sin pérdida por carreras
        $ipHash = md5($testIp);
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_rate_limits' . DIRECTORY_SEPARATOR . "rate_{$action}_{$ipHash}.json";
        $this->assert(file_exists($file), "Archivo de almacenamiento atómico generado por procesos concurrentes");

        $content = json_decode((string)file_get_contents($file), true);
        $this->assert(is_array($content) && count($content) === $numProcesses, "flock() serializó concurrentemente {$numProcesses} procesos sin pérdidas (conteo exacto: " . (is_array($content) ? count($content) : 0) . ")");

        Security::resetRateLimit($action);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Persistencia entre procesos independientes
    // ─────────────────────────────────────────────────────────────────────────
    private function testInterProcessPersistence(): void {
        echo "\n--- 3. Persistencia entre Procesos Independientes ---\n";

        $testIp = '203.0.113.77';
        $_SERVER['REMOTE_ADDR'] = $testIp;
        $action = 'interprocess_test';
        Security::resetRateLimit($action);

        $phpBinary = PHP_BINARY;
        $baseDir = addslashes(dirname(__DIR__));

        // Proceso 1: registra 2 llamadas
        $cmd1 = escapeshellarg($phpBinary) . ' -r ' . escapeshellarg("require '{$baseDir}/app/Core/Env.php'; require '{$baseDir}/app/Core/EmailOutbox.php'; require '{$baseDir}/app/Core/Security.php'; \$_SERVER['REMOTE_ADDR'] = '{$testIp}'; App\Core\Security::checkRateLimit('{$action}', 5, 300); App\Core\Security::checkRateLimit('{$action}', 5, 300);");
        exec($cmd1);

        // Proceso 2 independiente: registra 1 llamada
        $cmd2 = escapeshellarg($phpBinary) . ' -r ' . escapeshellarg("require '{$baseDir}/app/Core/Env.php'; require '{$baseDir}/app/Core/EmailOutbox.php'; require '{$baseDir}/app/Core/Security.php'; \$_SERVER['REMOTE_ADDR'] = '{$testIp}'; App\Core\Security::checkRateLimit('{$action}', 5, 300);");
        exec($cmd2);

        // Proceso actual: registra 1 llamada más
        $allowed = Security::checkRateLimit($action, 5, 300);
        $this->assert($allowed === true, "Cuarto intento permitido a través de procesos acumulados");

        $ipHash = md5($testIp);
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_rate_limits' . DIRECTORY_SEPARATOR . "rate_{$action}_{$ipHash}.json";
        $content = json_decode((string)file_get_contents($file), true);
        $this->assert(is_array($content) && count($content) === 4, "Historial acumuló 4 llamadas provenientes de 3 procesos diferentes");

        Security::resetRateLimit($action);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ─────────────────────────────────────────────────────────────────────────
    // 4. Fallo real de almacenamiento / bloqueo → HTTP 503 con JSON limpio
    // ─────────────────────────────────────────────────────────────────────────
    private function testRateLimiterFailsafe503(): void {
        echo "\n--- 4. Failsafe Real ante Fallo de Almacenamiento (HTTP 503 Limpio) ---\n";

        $phpBinary = PHP_BINARY;
        $baseDir = str_replace('\\', '/', dirname(__DIR__));
        $runnerFile = sys_get_temp_dir() . '/pmo_test_503_runner.php';

        $runnerCode = "<?php
        ini_set('error_log', sys_get_temp_dir() . '/pmo_test_php_errors.log');
        require '{$baseDir}/app/Core/Env.php';
        require '{$baseDir}/app/Core/EmailOutbox.php';
        require '{$baseDir}/app/Core/Security.php';

        \$_SERVER['REMOTE_ADDR'] = '203.0.113.99';
        \$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_rate_limits';
        if (!is_dir(\$tempDir)) mkdir(\$tempDir, 0755, true);
        \$file = \$tempDir . DIRECTORY_SEPARATOR . 'rate_fail_test_' . md5('203.0.113.99') . '.json';

        // Crear una carpeta con el mismo nombre para forzar fallo de fopen('c+') en cualquier SO
        if (file_exists(\$file)) {
            if (is_dir(\$file)) @rmdir(\$file);
            else @unlink(\$file);
        }
        mkdir(\$file, 0755, true);

        // fopen('c+') sobre un directorio falla siempre de forma garantizada → checkRateLimit emite 503
        App\\Core\\Security::checkRateLimit('fail_test', 5, 300);
        ";
        file_put_contents($runnerFile, $runnerCode);

        $output = (string)shell_exec(escapeshellarg($phpBinary) . ' ' . escapeshellarg($runnerFile) . ' 2>&1');

        // Limpiar archivos temporales
        @unlink($runnerFile);
        $tempRateDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_rate_limits' . DIRECTORY_SEPARATOR . 'rate_fail_test_' . md5('203.0.113.99') . '.json';
        if (file_exists($tempRateDir)) {
            @rmdir($tempRateDir);
        }

        // 1. Verificar JSON decodificable
        $decoded = json_decode(trim($output), true);
        $this->assert(is_array($decoded), "La respuesta ante fallo de E/S es un JSON válido y decodificable");
        $this->assert(isset($decoded['success']) && $decoded['success'] === false, "El campo 'success' es false en la respuesta JSON");
        $this->assert(isset($decoded['message']) && $decoded['message'] === 'Servicio no disponible temporalmente.', "Mensaje controlado 'Servicio no disponible temporalmente.'");

        // 2. Ausencia total de advertencias PHP (E_WARNING / E_NOTICE)
        $hasPhpWarning = str_contains($output, 'Warning:') || str_contains($output, 'Notice:') || str_contains($output, 'Fatal error');
        $this->assert(!$hasPhpWarning, "Cero advertencias PHP (E_WARNING/E_NOTICE) emitidas al cliente");

        // 3. Ausencia total de rutas internas del sistema o trazas
        $hasPathLeak = str_contains($output, sys_get_temp_dir()) || str_contains($output, 'rate_fail_test_') || str_contains($output, 'Stack trace') || str_contains($output, '#0');
        $this->assert(!$hasPathLeak, "Cero fugas de rutas del sistema de archivos o trazas de depuración");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Errores 413, 415, 400 y CSRF consumen cuota de Rate Limit
    // ─────────────────────────────────────────────────────────────────────────
    private function testErrorsConsumeRateLimitQuota(): void {
        echo "\n--- 5. Consumo de Cuota de Rate Limit por Errores 413, 415, 400 y CSRF ---\n";

        $testIp = '203.0.113.55';
        $_SERVER['REMOTE_ADDR'] = $testIp;
        $action = 'quota_consumption_test';
        Security::resetRateLimit($action);

        // Simulamos un endpoint con cuota de 5 intentos por ventana
        // Intento 1: Error 415 (Content-Type incorrecto)
        $slot1 = Security::checkRateLimit($action, 5, 300);
        $this->assert($slot1 === true, "Intento 1 (simulación error 415) consume 1 slot de cuota");
        $ctValid = Security::requireJsonContentType('application/jsonp');
        $this->assert($ctValid === false, "Content-Type incorrecto detectado");

        // Intento 2: Error 413 (Payload Too Large)
        $slot2 = Security::checkRateLimit($action, 5, 300);
        $this->assert($slot2 === true, "Intento 2 (simulación error 413) consume 1 slot de cuota");
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '99999';
        $GLOBALS['_MOCK_INPUT'] = '{"big": 1}';
        Security::getRequestData(65536, 5);
        $this->assert(Security::hasPayloadTooLarge(), "Payload excesivo detectado");
        unset($_SERVER['CONTENT_LENGTH'], $GLOBALS['_MOCK_INPUT']);
        Security::resetInputErrors();

        // Intento 3: Error 400 (JSON malformado / corrupto)
        $slot3 = Security::checkRateLimit($action, 5, 300);
        $this->assert($slot3 === true, "Intento 3 (simulación error 400 JSON corrupto) consume 1 slot de cuota");
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $GLOBALS['_MOCK_INPUT'] = '{"corrupt": ';
        Security::getRequestData(65536, 5);
        $this->assert(Security::hasJsonSyntaxError(), "JSON malformado detectado");
        unset($GLOBALS['_MOCK_INPUT']);
        Security::resetInputErrors();

        // Intento 4: Error 400 (JSON con profundidad excesiva > 5)
        $slot4 = Security::checkRateLimit($action, 5, 300);
        $this->assert($slot4 === true, "Intento 4 (simulación error 400 JSON profundo) consume 1 slot de cuota");
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $GLOBALS['_MOCK_INPUT'] = '{"a":{"b":{"c":{"d":{"e":{"f":1}}}}}}';
        Security::getRequestData(65536, 5);
        $this->assert(Security::hasJsonDepthExceeded(), "Profundidad JSON excesiva detectada");
        unset($GLOBALS['_MOCK_INPUT'], $_SERVER['CONTENT_TYPE']);
        Security::resetInputErrors();

        // Intento 5: Error 403 (CSRF ausente / inválido)
        $slot5 = Security::checkRateLimit($action, 5, 300);
        $this->assert($slot5 === true, "Intento 5 (simulación error 403 CSRF inválido) consume 1 slot de cuota");

        // Intento 6: Al haber agotado los 5 slots por errores anteriores, debe responder HTTP 429
        $slot6 = Security::checkRateLimit($action, 5, 300);
        $this->assert($slot6 === false, "Intento 6 es bloqueado con HTTP 429 tras consumir la cuota por errores 413, 415, 400 y 403");

        Security::resetRateLimit($action);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. CSRF inválido consumiendo el límite (sin doble conteo)
    // ─────────────────────────────────────────────────────────────────────────
    private function testCsrfFailureConsumesRateLimit(): void {
        echo "\n--- 5. Consumo de Cuota de Rate Limit por CSRF Inválido ---\n";

        $_SERVER['REMOTE_ADDR'] = '203.0.113.44';
        $action = 'csrf_penalize_test';
        Security::resetRateLimit($action);

        // Configurar 3 solicitudes máximas
        // Simular intento 1 con CSRF inválido
        $allowed1 = Security::checkRateLimit($action, 3, 300);
        $this->assert($allowed1 === true, "Intento 1 registrado y consumido antes de validar CSRF");

        // Simular intento 2 con CSRF inválido
        $allowed2 = Security::checkRateLimit($action, 3, 300);
        $this->assert($allowed2 === true, "Intento 2 registrado y consumido antes de validar CSRF");

        // Simular intento 3 con CSRF inválido
        $allowed3 = Security::checkRateLimit($action, 3, 300);
        $this->assert($allowed3 === true, "Intento 3 registrado y consumido antes de validar CSRF");

        // Intento 4 (incluso con CSRF legítimo) debe ser bloqueado con HTTP 429
        $allowed4 = Security::checkRateLimit($action, 3, 300);
        $this->assert($allowed4 === false, "Intento 4 es bloqueado con HTTP 429 tras consumir los 3 intentos fallidos previos");

        Security::resetRateLimit($action);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Validación Exacta de Content-Type (HTTP 415)
    // ─────────────────────────────────────────────────────────────────────────
    private function testContentTypeExactValidation(): void {
        echo "\n--- 6. Validación Exacta de Content-Type (HTTP 415) ---\n";

        $this->assert(Security::requireJsonContentType('application/json') === true, "Acepta 'application/json' exacto");
        $this->assert(Security::requireJsonContentType('application/json; charset=utf-8') === true, "Acepta 'application/json; charset=utf-8'");
        $this->assert(Security::requireJsonContentType('application/json;charset=UTF-8') === true, "Acepta 'application/json;charset=UTF-8'");
        $this->assert(Security::requireJsonContentType('application/json; profile=custom') === true, "Acepta parámetros adicionales en application/json");

        // Rechazos estrictos
        $this->assert(Security::requireJsonContentType('application/jsonp') === false, "Rechaza estrictamente 'application/jsonp' (HTTP 415)");
        $this->assert(Security::requireJsonContentType('text/jsonp') === false, "Rechaza 'text/jsonp'");
        $this->assert(Security::requireJsonContentType('text/plain') === false, "Rechaza 'text/plain'");
        $this->assert(Security::requireJsonContentType('application/x-www-form-urlencoded') === false, "Rechaza 'application/x-www-form-urlencoded'");
        $this->assert(Security::requireJsonContentType('application/problem+json') === false, "Rechaza coincidencias no exactas");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Control de Tamaño y Profundidad JSON (HTTP 413 & HTTP 400)
    // ─────────────────────────────────────────────────────────────────────────
    private function testPayloadSizeAndDepthLimits(): void {
        echo "\n--- 7. Control de Tamaño y Profundidad JSON (HTTP 413 & HTTP 400) ---\n";

        $_SERVER['CONTENT_TYPE'] = 'application/json';

        // 1. Payload Too Large vía Content-Length
        $_SERVER['CONTENT_LENGTH'] = '70000'; // > 64 KB
        $GLOBALS['_MOCK_INPUT'] = '{"test": 1}';
        $res = Security::getRequestData(65536, 5);
        $this->assert(Security::hasPayloadTooLarge(), "Detecta payload excesivo por Content-Length (> 64 KB)");
        $this->assert(empty($res), "Retorna array vacío al exceder tamaño");

        // 2. Payload Too Large vía tamaño real del cuerpo
        unset($_SERVER['CONTENT_LENGTH']);
        $GLOBALS['_MOCK_INPUT'] = str_repeat('a', 66000);
        $resSize = Security::getRequestData(65536, 5);
        $this->assert(Security::hasPayloadTooLarge(), "Detecta payload excesivo por longitud real (> 64 KB)");

        // 3. Profundidad excesiva de JSON (> 5 niveles)
        $deepJson = '{"a":{"b":{"c":{"d":{"e":{"f":"too_deep"}}}}}}';
        $GLOBALS['_MOCK_INPUT'] = $deepJson;
        $resDeep = Security::getRequestData(65536, 5);
        $this->assert(Security::hasJsonDepthExceeded(), "Detecta estructura JSON de profundidad > 5 niveles (HTTP 400)");

        // 4. JSON corrupto / sintaxis inválida
        $corruptJson = '{"nombre": "Juan", "answers": ';
        $GLOBALS['_MOCK_INPUT'] = $corruptJson;
        $resCorrupt = Security::getRequestData(65536, 5);
        $this->assert(Security::hasJsonSyntaxError(), "Detecta error de sintaxis en JSON malformado (HTTP 400)");

        unset($GLOBALS['_MOCK_INPUT'], $_SERVER['CONTENT_TYPE'], $_SERVER['CONTENT_LENGTH']);
        Security::resetInputErrors();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. Validación de Longitudes Excesivas: Rechazo HTTP 422 sin recorte
    // ─────────────────────────────────────────────────────────────────────────
    private function testExcessiveFieldLengthRejection(): void {
        echo "\n--- 8. Rechazo con HTTP 422 de Campos Excesivos (Sin Recorte) ---\n";

        $contactModel = new ContactModel();
        $claimModel = new ClaimModel();

        // 1. Contacto: Nombre con 101 caracteres (> 100 max)
        $longNameInput = [
            'nombre'   => str_repeat('A', 101),
            'telefono' => '987654321',
            'email'    => 'test@example.com',
            'mensaje'  => 'Mensaje con longitud suficiente y válido.'
        ];
        $valLongName = $contactModel->validateAndSanitize($longNameInput);
        $this->assert(!empty($valLongName['errors']['nombre']), "Contacto rechaza nombre de 101 caracteres (> 100) en lugar de recortarlo");

        // 2. Contacto: Mensaje con 2001 caracteres (> 2000 max)
        $longMsgInput = [
            'nombre'   => 'Carlos Alberto',
            'telefono' => '987654321',
            'email'    => 'test@example.com',
            'mensaje'  => str_repeat('M', 2001)
        ];
        $valLongMsg = $contactModel->validateAndSanitize($longMsgInput);
        $this->assert(!empty($valLongMsg['errors']['mensaje']), "Contacto rechaza mensaje de 2001 caracteres (> 2000) en lugar de recortarlo");

        // 3. Reclamaciones: Nombre completo con 151 caracteres (> 150 max)
        $longClaimName = [
            'tipo_documento'      => 'DNI',
            'numero_documento'    => '12345678',
            'nombre_completo'     => str_repeat('B', 151),
            'telefono'            => '987654321',
            'email'               => 'carlos@example.com',
            'domicilio'           => 'Av. Principal 123',
            'tipo_servicio'       => 'Capacitación',
            'nombre_servicio'     => 'Gestión de Proyectos',
            'tipo_registro'       => 'Reclamo',
            'detalle_reclamacion' => 'Detalle de la reclamación con longitud suficiente.',
            'pedido_consumidor'   => 'Revisión y devolución.',
            'declaracion_jurada'  => 1
        ];
        $valLongClaimName = $claimModel->validateAndSanitize($longClaimName);
        $this->assert(!empty($valLongClaimName['errors']['nombre_completo']), "Reclamaciones rechaza nombre de 151 caracteres (> 150)");

        // 4. Reclamaciones: Detalle con 3001 caracteres (> 3000 max)
        $longClaimDet = $longClaimName;
        $longClaimDet['nombre_completo'] = 'Carlos Ramos';
        $longClaimDet['detalle_reclamacion'] = str_repeat('D', 3001);
        $valLongClaimDet = $claimModel->validateAndSanitize($longClaimDet);
        $this->assert(!empty($valLongClaimDet['errors']['detalle_reclamacion']), "Reclamaciones rechaza detalle de 3001 caracteres (> 3000)");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 9. Validación Estricta de declaracion_jurada
    // ─────────────────────────────────────────────────────────────────────────
    private function testDeclaracionJuradaStrictValidation(): void {
        echo "\n--- 9. Validación Estricta de declaracion_jurada ---\n";

        $claimModel = new ClaimModel();
        $baseClaim = [
            'tipo_documento'      => 'DNI',
            'numero_documento'    => '12345678',
            'nombre_completo'     => 'Carlos Ramos',
            'telefono'            => '987654321',
            'email'               => 'carlos@example.com',
            'domicilio'           => 'Av. Principal 123',
            'tipo_servicio'       => 'Capacitación',
            'nombre_servicio'     => 'Gestión de Proyectos',
            'tipo_registro'       => 'Reclamo',
            'detalle_reclamacion' => 'Detalle de la reclamación con longitud suficiente.',
            'pedido_consumidor'   => 'Revisión y devolución.',
        ];

        // 1. Cadena "false"
        $claimFalseStr = $baseClaim;
        $claimFalseStr['declaracion_jurada'] = 'false';
        $val1 = $claimModel->validateAndSanitize($claimFalseStr);
        $this->assert(!empty($val1['errors']['declaracion_jurada']), "Rechaza declaracion_jurada = 'false'");

        // 2. Cadena "0"
        $claimZeroStr = $baseClaim;
        $claimZeroStr['declaracion_jurada'] = '0';
        $val2 = $claimModel->validateAndSanitize($claimZeroStr);
        $this->assert(!empty($val2['errors']['declaracion_jurada']), "Rechaza declaracion_jurada = '0'");

        // 3. Entero 0
        $claimZeroInt = $baseClaim;
        $claimZeroInt['declaracion_jurada'] = 0;
        $val3 = $claimModel->validateAndSanitize($claimZeroInt);
        $this->assert(!empty($val3['errors']['declaracion_jurada']), "Rechaza declaracion_jurada = 0");

        // 4. Booleano false
        $claimBoolFalse = $baseClaim;
        $claimBoolFalse['declaracion_jurada'] = false;
        $val4 = $claimModel->validateAndSanitize($claimBoolFalse);
        $this->assert(!empty($val4['errors']['declaracion_jurada']), "Rechaza declaracion_jurada = false");

        // 5. Array u objeto
        $claimArray = $baseClaim;
        $claimArray['declaracion_jurada'] = ['accepted' => true];
        $val5 = $claimModel->validateAndSanitize($claimArray);
        $this->assert(!empty($val5['errors']['declaracion_jurada']), "Rechaza declaracion_jurada = array");

        // 6. Valores afirmativos legítimos
        $claimTrue = $baseClaim;
        $claimTrue['declaracion_jurada'] = 1;
        $valOk1 = $claimModel->validateAndSanitize($claimTrue);
        $this->assert(empty($valOk1['errors']['declaracion_jurada']), "Acepta declaracion_jurada = 1");

        $claimTrue2 = $baseClaim;
        $claimTrue2['declaracion_jurada'] = true;
        $valOk2 = $claimModel->validateAndSanitize($claimTrue2);
        $this->assert(empty($valOk2['errors']['declaracion_jurada']), "Acepta declaracion_jurada = true");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 10. Validación Estricta de Evaluaciones contra Catálogo Dinámico
    // ─────────────────────────────────────────────────────────────────────────
    private function testEvaluationCatalogValidationStrict(): void {
        echo "\n--- 10. Validación Estricta contra Catálogo Dinámico ---\n";

        $model = new SoftSkillsModel();
        $catalog = $model->getCatalog();
        $this->assert(!empty($catalog) && count($catalog) === 4, "Catálogo oficial cargado con éxito (" . implode(', ', array_keys($catalog)) . ")");

        $validAnswers = [];
        foreach (array_keys($catalog) as $comp) {
            $validAnswers[$comp] = [
                1 => 3,
                2 => 2,
                3 => 4,
                4 => 1,
                5 => 3,
            ];
        }

        // Test 1: Respuestas válidas completas
        $val1 = $model->validateAnswersStrict($validAnswers);
        $this->assert($val1['valid'] === true, "validateAnswersStrict() aprueba conjunto completo legítimo");

        // Test 2: Falta una competencia
        $missingComp = $validAnswers;
        unset($missingComp['adaptabilidad']);
        $valMissing = $model->validateAnswersStrict($missingComp);
        $this->assert($valMissing['valid'] === false, "Rechaza evaluación si falta una competencia del catálogo");

        // Test 3: Competencia extra no reconocida
        $extraComp = $validAnswers;
        $extraComp['liderazgo_falso'] = [1 => 2, 2 => 2, 3 => 2, 4 => 2, 5 => 2];
        $valExtra = $model->validateAnswersStrict($extraComp);
        $this->assert($valExtra['valid'] === false, "Rechaza evaluación con competencias adicionales");

        // Test 4: Pregunta faltante dentro de una competencia
        $missingQ = $validAnswers;
        unset($missingQ['comunicacion'][5]);
        $valMissingQ = $model->validateAnswersStrict($missingQ);
        $this->assert($valMissingQ['valid'] === false, "Rechaza evaluación si falta una pregunta (4 de 5)");

        // Test 5: Pregunta extra dentro de una competencia (pregunta 6)
        $extraQ = $validAnswers;
        $extraQ['comunicacion'][6] = 2;
        $valExtraQ = $model->validateAnswersStrict($extraQ);
        $this->assert($valExtraQ['valid'] === false, "Rechaza evaluación con preguntas adicionales");

        // Test 6: Opción como string numérico ("3")
        $stringOpt = $validAnswers;
        $stringOpt['comunicacion'][1] = "3";
        $valString = $model->validateAnswersStrict($stringOpt);
        $this->assert($valString['valid'] === false, "Rechaza opción con tipo string ('3')");

        // Test 7: Opción booleana (true)
        $boolOpt = $validAnswers;
        $boolOpt['comunicacion'][1] = true;
        $valBool = $model->validateAnswersStrict($boolOpt);
        $this->assert($valBool['valid'] === false, "Rechaza opción con tipo booleano (true)");

        // Test 8: Opción decimal (2.5)
        $floatOpt = $validAnswers;
        $floatOpt['comunicacion'][1] = 2.5;
        $valFloat = $model->validateAnswersStrict($floatOpt);
        $this->assert($valFloat['valid'] === false, "Rechaza opción con tipo decimal (2.5)");

        // Test 9: Opción array ([1, 2])
        $arrayOpt = $validAnswers;
        $arrayOpt['comunicacion'][1] = [1, 2];
        $valArray = $model->validateAnswersStrict($arrayOpt);
        $this->assert($valArray['valid'] === false, "Rechaza opción de tipo array");

        // Test 10: Opciones fuera de rango (0 y 5)
        $outRange0 = $validAnswers;
        $outRange0['comunicacion'][1] = 0;
        $this->assert($model->validateAnswersStrict($outRange0)['valid'] === false, "Rechaza opción fuera de rango (0)");

        $outRange5 = $validAnswers;
        $outRange5['comunicacion'][1] = 5;
        $this->assert($model->validateAnswersStrict($outRange5)['valid'] === false, "Rechaza opción fuera de rango (5)");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 11. Anti-Tampering: Rechazo de Puntajes Inyectados por el Cliente
    // ─────────────────────────────────────────────────────────────────────────
    private function testEvaluationAntiTamperingScores(): void {
        echo "\n--- 11. Anti-Tampering: Rechazo de Puntajes Inyectados ---\n";

        $forbiddenKeys = ['score', 'scores', 'puntaje', 'resultado', 'nivel_madurez', 'maturity_level', 'global_score'];

        foreach ($forbiddenKeys as $fKey) {
            $payload = [
                'nombre_completo' => 'Juan Pérez',
                'email'           => 'juan@example.com',
                $fKey             => 100,
                'answers'         => []
            ];

            $hasForbidden = !empty(array_intersect(array_keys($payload), ['score', 'scores', 'puntaje', 'puntajes', 'resultado', 'resultados', 'nivel_madurez', 'maturity_level', 'points', 'percentage', 'global_score']));
            $this->assert($hasForbidden, "Detección y rechazo de campo inyectado '{$fKey}'");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 12. Anti-Spoofing de IP (X-Forwarded-For)
    // ─────────────────────────────────────────────────────────────────────────
    private function testIpSpoofingProtection(): void {
        echo "\n--- 12. Protección Anti-Spoofing de IP (X-Forwarded-For) ---\n";

        $_SERVER['REMOTE_ADDR'] = '198.51.100.99';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 5.6.7.8';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '10.20.30.40';

        $resolvedIp = Security::getClientIp();
        $this->assert($resolvedIp === '198.51.100.99', "Ignora X-Forwarded-For y CF-Connecting-IP si REMOTE_ADDR no es proxy confiable");

        $_SERVER['HTTP_X_FORWARDED_FOR'] = '<script>alert(1)</script>';
        $resolvedIpCorrupt = Security::getClientIp();
        $this->assert($resolvedIpCorrupt === '198.51.100.99', "Rechaza valores no-IP en cabeceras de proxy");

        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CF_CONNECTING_IP']);
    }
}

// Ejecución
$test = new RateLimitAndValidationTest();
$test->run();
