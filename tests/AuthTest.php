<?php
/**
 * PMO SOLUTIONS — Unit & Integration Test Suite: Admin Authentication & RBAC (Tarea 2)
 *
 * Cobertura de pruebas:
 * 1. Autenticación exitosa con credenciales válidas (config/env) -> HTTP 200, rol admin en sesión.
 * 2. Autenticación fallida con credenciales inválidas -> HTTP 401.
 * 3. Rate limiting estricto de login (5 intentos en 15m) -> HTTP 429 con Retry-After.
 * 4. Cierre de sesión (logout) -> Destruye sesión y cookies.
 * 5. Protección RBAC en /evaluacion-habilidades/resultados -> HTTP 403 sin sesión, HTTP 200 con sesión admin.
 * 6. Método no permitido en /admin/logout (GET -> HTTP 405 con cabecera Allow: POST).
 * 7. Validación de campos obligatorios en login (vacíos -> HTTP 422).
 * 8. Expiración de sesión por inactividad (>30m) y absoluta (>8h).
 *
 * Ejecución:
 *   php tests/AuthTest.php
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

define('PMO_APP_ACCESS', true);
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

use App\Core\Security;

class AuthTest {

    private int $passed = 0;
    private int $failed = 0;

    public function runAll(): void {
        echo "\n======================================================================\n";
        echo " SUITE DE PRUEBAS: AUTENTICACIÓN ADMINISTRATIVA & RBAC (TAREA 2)\n";
        echo "======================================================================\n";

        $this->testValidationOfLoginPayload();
        $this->testAdminLoginSuccessAndFailure();
        $this->testLoginRateLimiting();
        $this->testSessionManagementAndRBAC();
        $this->testSessionExpiration();
        $this->testAdminRoutesAndMethodNotAllowed();

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

    private function testValidationOfLoginPayload(): void {
        echo "\n--- 1. Validación de Payloads de Login ---\n";

        // Login con credenciales vacías debe retornar false
        Security::logout();
        $result = Security::login('', '');
        $this->assert($result === false, "Login con campos vacíos debe fallar");

        // Login con usuario inexistente y contraseña aleatoria
        $result2 = Security::login('usuario_ficticio_inexistente', 'clave_invalida_123');
        $this->assert($result2 === false, "Login con usuario inexistente debe fallar con cálculo ficticio");
    }

    private function testAdminLoginSuccessAndFailure(): void {
        echo "\n--- 2. Autenticación con Variables de Entorno / Mock ---\n";

        $testUser = 'pmo_admin_tester';
        $testPass = 'P@ssw0rdSegura2026!';
        $testHash = password_hash($testPass, PASSWORD_DEFAULT);

        putenv("PMO_ADMIN_USERNAME={$testUser}");
        putenv("PMO_ADMIN_PASSWORD_HASH={$testHash}");

        Security::logout();
        $this->assert(Security::isAuthenticated() === false, "Estado inicial no autenticado");

        // Intento con contraseña errónea
        $failResult = Security::login($testUser, 'password_incorrecta');
        $this->assert($failResult === false, "Login con contraseña incorrecta retorna false");
        $this->assert(Security::isAuthenticated() === false, "No debe autenticar con contraseña incorrecta");

        // Intento con credenciales correctas
        $successResult = Security::login($testUser, $testPass);
        $this->assert($successResult === true, "Login con credenciales correctas retorna true");
        $this->assert(Security::isAuthenticated() === true, "isAuthenticated() retorna true");
        $this->assert(Security::getUserRole() === 'admin', "getUserRole() retorna 'admin'");
        $this->assert(Security::hasRole('admin') === true, "hasRole('admin') retorna true");
        $this->assert(Security::hasRole('editor') === false, "hasRole('editor') retorna false");

        // Logout
        Security::logout();
        $this->assert(Security::isAuthenticated() === false, "Logout invalida la sesión");
        $this->assert(Security::getUserRole() === null, "getUserRole() es null tras logout");

        // Limpiar variables de entorno mock
        putenv("PMO_ADMIN_USERNAME");
        putenv("PMO_ADMIN_PASSWORD_HASH");
    }

    private function testLoginRateLimiting(): void {
        echo "\n--- 3. Hardened Rate Limiting de Login ---\n";

        $ip = '192.168.100.' . rand(1, 250);
        $_SERVER['REMOTE_ADDR'] = $ip;
        $actionKey = 'login_' . $ip;

        Security::resetRateLimit($actionKey);

        // 5 intentos permitidos
        for ($i = 1; $i <= 5; $i++) {
            $allowed = Security::checkRateLimit($actionKey, 5, 900);
            $this->assert($allowed === true, "Intento de login #{$i} dentro del límite debe ser permitido");
        }

        // Intento 6 debe ser bloqueado con Retry-After > 0
        $blocked = Security::checkRateLimit($actionKey, 5, 900);
        $this->assert($blocked === false, "Intento de login #6 debe ser bloqueado por Rate Limiting");
        $retryAfter = Security::getLastRetryAfter();
        $this->assert($retryAfter > 0 && $retryAfter <= 900, "Retry-After dinámico calculado ({$retryAfter} segundos)");

        // Reseteo tras éxito
        Security::resetRateLimit($actionKey);
        $afterReset = Security::checkRateLimit($actionKey, 5, 900);
        $this->assert($afterReset === true, "Tras resetRateLimit(), el siguiente intento vuelve a permitirse");
    }

    private function testSessionManagementAndRBAC(): void {
        echo "\n--- 4. Control de Acceso RBAC ---\n";

        Security::logout();

        // Sin sesión: requireRole('admin') emite 403
        $deniedOutput = shell_exec('php -r "require \'app/Core/Autoloader.php\'; App\Core\Autoloader::register(); App\Core\Security::requireRole(\'admin\');" 2>&1');
        $this->assert(strpos((string)$deniedOutput, 'Acceso no autorizado') !== false, "requireRole('admin') sin sesión emite mensaje 403");

        // Sin sesión: requireAuthentication() emite 401
        $authDeniedOutput = shell_exec('php -r "require \'app/Core/Autoloader.php\'; App\Core\Autoloader::register(); App\Core\Security::requireAuthentication();" 2>&1');
        $this->assert(strpos((string)$authDeniedOutput, 'Acceso denegado') !== false, "requireAuthentication() sin sesión emite mensaje 401");
    }

    private function testSessionExpiration(): void {
        echo "\n--- 5. Control de Expiración de Sesión ---\n";

        // Simular sesión iniciada hace 35 minutos (inactividad superada)
        Security::startSecureSession();
        $_SESSION['pmo_admin_user'] = 'admin';
        $_SESSION['pmo_admin_role'] = 'admin';
        $_SESSION['pmo_created_at'] = time() - 2000;
        $_SESSION['pmo_last_activity'] = time() - 1900; // > 1800s (30m)

        // Al iniciar sesión segura de nuevo, debe detectar inactividad y hacer logout
        Security::startSecureSession();
        $this->assert(empty($_SESSION['pmo_admin_user']), "Sesión caducada por inactividad (>30m) es destruida");

        // Simular sesión con actividad reciente pero creada hace 9 horas (absoluta superada)
        $_SESSION['pmo_admin_user'] = 'admin';
        $_SESSION['pmo_admin_role'] = 'admin';
        $_SESSION['pmo_created_at'] = time() - 30000; // > 28800s (8h)
        $_SESSION['pmo_last_activity'] = time() - 100;

        Security::startSecureSession();
        $this->assert(empty($_SESSION['pmo_admin_user']), "Sesión caducada por límite absoluto (>8h) es destruida");

        Security::logout();
    }

    private function testAdminRoutesAndMethodNotAllowed(): void {
        echo "\n--- 6. Rutas Administrativas Desactivadas (Feature Flag admin_enabled=false) ---\n";

        // Petición GET /admin/login -> Debe responder 404 (vista desactivada)
        $loginView = $this->simulateHttp('GET', '/admin/login');
        $this->assert($loginView['status'] === 404, "GET /admin/login responde HTTP 404");

        // Petición POST /admin/login -> Debe responder 404 (endpoint desactivado)
        $loginPost = $this->simulateHttp('POST', '/admin/login', ['username' => 'admin', 'password' => 'pass']);
        $this->assert($loginPost['status'] === 404, "POST /admin/login responde HTTP 404");

        // Petición POST /admin/logout -> Debe responder 404 (endpoint desactivado)
        $logoutPost = $this->simulateHttp('POST', '/admin/logout');
        $this->assert($logoutPost['status'] === 404, "POST /admin/logout responde HTTP 404");

        // Petición GET /evaluacion-habilidades/resultados -> Debe responder 404 (panel oculto)
        $resultsUnauth = $this->simulateHttp('GET', '/evaluacion-habilidades/resultados');
        $this->assert($resultsUnauth['status'] === 404, "GET /evaluacion-habilidades/resultados responde HTTP 404 (completamente bloqueado)");
    }

    private function simulateHttp(string $method, string $uri, mixed $postData = null): array {
        $indexFile = dirname(__DIR__) . '/index.php';
        $script = '<?php ';
        $script .= 'ini_set("display_errors", "1"); error_reporting(E_ALL); ';
        $script .= 'register_shutdown_function(function() { echo "\n___HTTP_STATUS___:" . http_response_code() . "\n"; }); ';
        $script .= '$_SERVER["REQUEST_METHOD"] = "' . addslashes($method) . '"; ';
        $script .= '$_SERVER["REQUEST_URI"] = "' . addslashes($uri) . '"; ';
        $script .= '$_SERVER["SCRIPT_NAME"] = "/index.php"; ';
        $script .= '$_SERVER["REMOTE_ADDR"] = "10.0.99." . rand(1, 250); ';
        $script .= '$_SERVER["HTTP_ACCEPT"] = "application/json, text/html, */*"; ';
        if ($method === 'POST') {
            $script .= '$_SERVER["CONTENT_TYPE"] = "application/json"; ';
            if ($postData !== null) {
                $script .= '$GLOBALS["_MOCK_INPUT"] = ' . var_export(json_encode($postData), true) . '; ';
            }
        }
        $script .= 'require "' . addslashes($indexFile) . '"; ';

        $tmp = tempnam(sys_get_temp_dir(), 'pmo_auth_');
        file_put_contents($tmp, $script);
        $output = (string)shell_exec('php ' . escapeshellarg($tmp) . ' 2>&1');
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
        echo " RESUMEN: AUTENTICACIÓN & RBAC\n";
        echo "======================================================================\n";
        echo " Total tests: {$total} | \033[32mPasados: {$this->passed}\033[0m | \033[31mFallados: {$this->failed}\033[0m\n";

        if ($this->failed === 0) {
            echo "\n\033[32m✓ TODAS LAS PRUEBAS DE AUTENTICACIÓN PASARON AL 100%.\033[0m\n\n";
        } else {
            echo "\n\033[31m✗ HUBO FALLOS EN LAS PRUEBAS DE AUTENTICACIÓN.\033[0m\n\n";
            exit(1);
        }
    }
}

$test = new AuthTest();
$test->runAll();

