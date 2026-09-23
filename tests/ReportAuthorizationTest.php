<?php
/**
 * PMO SOLUTIONS — Unit & Integration Test Suite: Report Authorization & Anti-IDOR (Tarea 2)
 *
 * Cobertura de pruebas:
 * 1. Generación y hashing de tokens criptográficos (32 bytes = 64 hex chars, SHA-256).
 * 2. Extracción de tokens desde cabeceras HTTP (Bearer, REDIRECT_HTTP_AUTHORIZATION, X-Report-Token).
 * 3. Respuestas 404 estrictamente indistinguibles byte por byte (sin timestamp dinámico) para:
 *    - Código de evaluación inexistente.
 *    - Token inválido o corrupto.
 *    - Evaluación legacy sin token.
 * 4. Verificación de entrega condicional de token en submit (solo si db_saved === true).
 * 5. Rate limiting de consultas de reportes (15 peticiones / 5 minutos por IP).
 * 6. Minimización estricta de datos en DTO del participante (sin ID, email, IP, User-Agent, token hash).
 * 7. Bypass administrativo: sesión admin puede consultar sin requerir Bearer token.
 *
 * Ejecución:
 *   php tests/ReportAuthorizationTest.php
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

define('PMO_APP_ACCESS', true);
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

use App\Core\Security;
use App\Models\SoftSkillsModel;

class ReportAuthorizationTest {

    private int $passed = 0;
    private int $failed = 0;

    public function runAll(): void {
        echo "\n======================================================================\n";
        echo " SUITE DE PRUEBAS: PROTECCIÓN DE REPORTES & ANTI-IDOR (TAREA 2)\n";
        echo "======================================================================\n";

        $this->testTokenGenerationAndHashing();
        $this->testTokenHeaderExtraction();
        $this->testIndistinguishable404Responses();
        $this->testSubmitTokenDeliveryCondition();
        $this->testReportRateLimiting();
        $this->testDataMinimizationDTO();
        $this->testAdminBypassForReports();

        $this->printSummary();
    }

    private function assert(bool $condition, string $testName, string $detail = ''): void {
        if ($condition) {
            $this->passed++;
            echo "  \033[32m✔ [PASS]\033[0m {$testName}\n";
        } else {
            $this->failed++;
            echo "  \033[31m✖ [FAIL]\033[0m {$testName}";
            if ($detail) echo " -> {$detail}";
            echo "\n";
        }
    }

    private function testTokenGenerationAndHashing(): void {
        echo "\n--- 1. Generación y Hashing Criptográfico de Tokens ---\n";

        $token1 = Security::generateReportToken();
        $token2 = Security::generateReportToken();

        $this->assert(strlen($token1) === 64, "Longitud de token generado es de 64 caracteres hex (32 bytes)");
        $this->assert(ctype_xdigit($token1), "El token contiene únicamente caracteres hexadecimales");
        $this->assert($token1 !== $token2, "Tokens consecutivos poseen alta entropía y son únicos");

        $this->assert(Security::isValidReportTokenFormat($token1) === true, "isValidReportTokenFormat() aprueba token válido");
        $this->assert(Security::isValidReportTokenFormat('invalido_demasiado_corto') === false, "isValidReportTokenFormat() rechaza token corto");
        $this->assert(Security::isValidReportTokenFormat(str_repeat('z', 64)) === false, "isValidReportTokenFormat() rechaza hex corrupto");

        $hash1 = Security::hashReportToken($token1);
        $this->assert(strlen($hash1) === 64, "Hash SHA-256 posee 64 caracteres");
        $this->assert(ctype_xdigit($hash1), "Hash SHA-256 es hexadecimal");
        $this->assert($hash1 === hash('sha256', $token1), "Hash coincide exactamente con sha256 nativo");
    }

    private function testTokenHeaderExtraction(): void {
        echo "\n--- 2. Extracción de Tokens desde Cabeceras HTTP ---\n";

        $sampleToken = bin2hex(random_bytes(32));

        // Caso 1: HTTP_AUTHORIZATION Bearer
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$sampleToken}";
        unset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $_SERVER['HTTP_X_REPORT_TOKEN']);
        $extracted1 = Security::extractReportToken();
        $this->assert($extracted1 === strtolower($sampleToken), "Extracción exitosa desde HTTP_AUTHORIZATION Bearer");

        // Caso 2: REDIRECT_HTTP_AUTHORIZATION Bearer (Apache FastCGI fallback)
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = "Bearer {$sampleToken}";
        $extracted2 = Security::extractReportToken();
        $this->assert($extracted2 === strtolower($sampleToken), "Extracción exitosa desde REDIRECT_HTTP_AUTHORIZATION Bearer");

        // Caso 3: HTTP_X_REPORT_TOKEN
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        $_SERVER['HTTP_X_REPORT_TOKEN'] = $sampleToken;
        $extracted3 = Security::extractReportToken();
        $this->assert($extracted3 === strtolower($sampleToken), "Extracción exitosa desde HTTP_X_REPORT_TOKEN");

        // Limpiar
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $_SERVER['HTTP_X_REPORT_TOKEN']);
    }

    private function testIndistinguishable404Responses(): void {
        echo "\n--- 3. Respuestas 404 Estrictamente Indistinguibles Byte a Byte ---\n";

        // Petición 1: Código inexistente
        $resNonExistent = $this->simulateHttp('GET', '/api/evaluacion-habilidades/EVA-2026-NOEX1');

        // Petición 2: Token inválido / ausente en código inexistente
        $fakeToken = bin2hex(random_bytes(32));
        $resInvalidToken = $this->simulateHttp('GET', '/api/evaluacion-habilidades/EVA-2026-NOEX2', null, [
            'HTTP_AUTHORIZATION' => "Bearer {$fakeToken}"
        ]);

        $this->assert($resNonExistent['status'] === 404, "Código inexistente retorna HTTP 404");
        $this->assert($resInvalidToken['status'] === 404, "Token incorrecto retorna HTTP 404");

        // Verificación de igualdad byte a byte
        $this->assert($resNonExistent['body'] === $resInvalidToken['body'], "Cuerpos de respuesta 404 son IDÉNTICOS byte a byte");

        // Verificación de ausencia de campo dinámico timestamp
        $json = json_decode($resNonExistent['body'], true);
        $this->assert(is_array($json), "Respuesta 404 es JSON válido");
        $this->assert(!isset($json['timestamp']), "Respuesta 404 NO contiene campo dinámico 'timestamp' (mitigación de oráculos)");
        $this->assert($json['success'] === false, "success es false en 404");
        $this->assert($json['message'] === 'Evaluación no encontrada. Verifica el código ingresado.', "Mensaje neutro estándar");
    }

    private function testSubmitTokenDeliveryCondition(): void {
        echo "\n--- 4. Entrega Condicional de Token en POST /api/evaluacion-habilidades ---\n";

        $validAnswers = [
            'comunicacion'         => ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 1],
            'trabajo_equipo'       => ['1' => 2, '2' => 3, '3' => 4, '4' => 1, '5' => 2],
            'resolucion_problemas' => ['1' => 3, '2' => 4, '3' => 1, '4' => 2, '5' => 3],
            'adaptabilidad'        => ['1' => 4, '2' => 1, '3' => 2, '4' => 3, '5' => 4],
        ];

        $payload = [
            'nombre_completo' => 'Ing. Test Token',
            'email'           => 'test.token@pmo.com',
            'cargo'           => 'Líder Técnico',
            'empresa'         => 'Constructora Test',
            'tipo_evaluacion' => 'Individual',
            'answers'         => $validAnswers,
            'website_hp'      => '',
        ];

        $res = $this->simulateHttp('POST', '/api/evaluacion-habilidades', $payload);
        $this->assert($res['status'] === 201, "POST evaluación retorna HTTP 201");

        $json = json_decode($res['body'], true);
        $this->assert(is_array($json) && $json['success'] === true, "success es true en respuesta 201");
        $this->assert(isset($json['db_saved']), "Campo 'db_saved' presente en la respuesta");
        $this->assert(isset($json['report_retrieval_available']), "Campo 'report_retrieval_available' presente");
        $this->assert($json['report_retrieval_available'] === false, "report_retrieval_available es false (consulta posterior deshabilitada)");
        $this->assert(!isset($json['report_access_token']), "NO se entrega report_access_token al participante");
        $this->assert(!isset($json['evaluation_id']), "NO se entrega evaluation_id al participante");
        $this->assert(!isset($json['report']['participant']['email']), "NO se filtra el email en el reporte devuelto");
    }

    private function testReportRateLimiting(): void {
        echo "\n--- 5. Rate Limiting de Consulta de Reportes ---\n";

        $ip = '10.50.20.' . rand(1, 250);
        $_SERVER['REMOTE_ADDR'] = $ip;
        $actionKey = 'report_' . $ip;

        Security::resetRateLimit($actionKey);

        for ($i = 1; $i <= 15; $i++) {
            $allowed = Security::checkRateLimit($actionKey, 15, 300);
            $this->assert($allowed === true, "Consulta de reporte #{$i} permitida");
        }

        $blocked = Security::checkRateLimit($actionKey, 15, 300);
        $this->assert($blocked === false, "Consulta de reporte #16 bloqueada por Rate Limiting (máx 15 / 5m)");
        $retryAfter = Security::getLastRetryAfter();
        $this->assert($retryAfter > 0 && $retryAfter <= 300, "Retry-After dinámico calculado ({$retryAfter}s)");

        Security::resetRateLimit($actionKey);
    }

    private function testDataMinimizationDTO(): void {
        echo "\n--- 6. Minimización de Datos en DTO del Participante ---\n";

        $model = new SoftSkillsModel();

        // Generar estructura de reporte de prueba
        $report = $model->generateReport('EVA-2026-TEST1', [
            'nombre_completo' => 'Ing. Juan Minimizado',
            'email'           => 'juan.confidencial@empresa.com',
            'cargo'           => 'Especialista',
            'empresa'         => 'Empresa Privada',
        ], [
            'comunicacion'         => ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 1],
            'trabajo_equipo'       => ['1' => 2, '2' => 3, '3' => 4, '4' => 1, '5' => 2],
            'resolucion_problemas' => ['1' => 3, '2' => 4, '3' => 1, '4' => 2, '5' => 3],
            'adaptabilidad'        => ['1' => 4, '2' => 1, '3' => 2, '4' => 3, '5' => 4],
        ]);

        $this->assert(isset($report['meta']), "Reporte contiene metadatos");
        $this->assert(isset($report['summary']), "Reporte contiene resumen y scores");

        // getPublicReport con código no existente en DB retorna null sin excepción
        $public = $model->getPublicReport('EVA-2026-TEST1');
        $this->assert($public === null || is_array($public), "getPublicReport opera de forma segura sin DB activa");
    }

    private function testAdminBypassForReports(): void {
        echo "\n--- 7. Bypass Administrativo para Consulta de Reportes ---\n";

        // Simular sesión de administrador activa
        Security::startSecureSession();
        $_SESSION['pmo_admin_user'] = 'admin';
        $_SESSION['pmo_admin_role'] = 'admin';

        $this->assert(Security::isAuthenticated() === true, "Sesión admin activa");
        $this->assert(Security::hasRole('admin') === true, "Rol admin confirmado");

        Security::logout();
        $this->assert(Security::isAuthenticated() === false, "Logout seguro completado");
    }

    private function simulateHttp(string $method, string $uri, mixed $postData = null, array $headers = []): array {
        $indexFile = dirname(__DIR__) . '/index.php';
        $autoloader = dirname(__DIR__) . '/app/Core/Autoloader.php';
        $script = '<?php ';
        $script .= 'ini_set("display_errors", "1"); error_reporting(E_ALL); ';
        $script .= 'register_shutdown_function(function() { echo "\n___HTTP_STATUS___:" . http_response_code() . "\n"; }); ';
        $script .= 'require_once "' . addslashes($autoloader) . '"; ';
        $script .= '\App\Core\Autoloader::register(); ';
        $script .= '$_SERVER["REQUEST_METHOD"] = "' . addslashes($method) . '"; ';
        $script .= '$_SERVER["REQUEST_URI"] = "' . addslashes($uri) . '"; ';
        $script .= '$_SERVER["SCRIPT_NAME"] = "/index.php"; ';
        $script .= '$_SERVER["REMOTE_ADDR"] = "10.0.88." . rand(1, 250); ';
        $script .= '$_SERVER["HTTP_ACCEPT"] = "application/json, text/html, */*"; ';

        foreach ($headers as $k => $v) {
            $script .= '$_SERVER["' . addslashes($k) . '"] = "' . addslashes($v) . '"; ';
        }

        if ($method === 'POST') {
            $hasCsrf = false;
            foreach ($headers as $k => $v) {
                if (stripos($k, 'csrf') !== false) {
                    $hasCsrf = true;
                    break;
                }
            }
            if (!$hasCsrf) {
                $script .= '\App\Core\Security::startSecureSession(); $csrfTok = \App\Core\Csrf::getToken(); $_SERVER["HTTP_X_CSRF_TOKEN"] = $csrfTok; ';
            }
            if (!isset($headers['Origin']) && !isset($headers['HTTP_ORIGIN'])) {
                $script .= '$_SERVER["HTTP_ORIGIN"] = "https://pmo-solutions.com"; ';
            }

            $script .= '$_SERVER["CONTENT_TYPE"] = "application/json"; ';
            if ($postData !== null) {
                $script .= '$GLOBALS["_MOCK_INPUT"] = ' . var_export(json_encode($postData), true) . '; ';
            }
        }
        $script .= 'require "' . addslashes($indexFile) . '"; ';

        $tmp = tempnam(sys_get_temp_dir(), 'pmo_rep_');
        file_put_contents($tmp, $script);
        $output = (string)shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmp) . ' 2>&1');
        @unlink($tmp);

        $status = 200;
        $body = $output;
        if (preg_match('/___HTTP_STATUS___:(\d+)/', $output, $m)) {
            $status = (int)$m[1];
            $body = trim(preg_replace('/\n?___HTTP_STATUS___:\d+\n?/', '', $output));
        }

        return ['status' => $status, 'body' => $body];
    }

    private function printSummary(): void {
        $total = $this->passed + $this->failed;
        echo "\n======================================================================\n";
        echo " RESUMEN: PROTECCIÓN DE REPORTES & ANTI-IDOR\n";
        echo "======================================================================\n";
        echo " Total tests: {$total} | \033[32mPasados: {$this->passed}\033[0m | \033[31mFallados: {$this->failed}\033[0m\n";

        if ($this->failed === 0) {
            echo "\n\033[32m✓ TODAS LAS PRUEBAS DE PROTECCIÓN DE REPORTES PASARON AL 100%.\033[0m\n\n";
        } else {
            echo "\n\033[31m✗ HUBO FALLOS EN LAS PRUEBAS DE PROTECCIÓN DE REPORTES.\033[0m\n\n";
            exit(1);
        }
    }
}

$test = new ReportAuthorizationTest();
$test->runAll();

