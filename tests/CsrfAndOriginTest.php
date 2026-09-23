<?php
/**
 * PMO SOLUTIONS - Suite de Pruebas Automatizadas: Tarea 5
 * Protección CSRF y Validación Estricta de Origen
 */

define('PMO_APP_ACCESS', true);
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Core\Csrf;
use App\Core\Env;
use App\Core\Security;

class CsrfAndOriginTest {
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void {
        echo "\n======================================================================\n";
        echo " SUITE DE PRUEBAS: PROTECCIÓN CSRF & VALIDACIÓN DE ORIGEN (TAREA 5)\n";
        echo "======================================================================\n\n";

        $this->testCsrfTokenGenerationAndPersistence();
        $this->testCsrfTokenValidationLogic();
        $this->testCsrfTokenExtraction();
        $this->testOriginNormalization();
        $this->testEnvironmentOriginRestrictions();
        $this->testOriginHeaderValidation();
        $this->testRefererHeaderValidation();
        $this->testMissingOriginAndRefererFallback();
        $this->testCsrfMandatoryEvenWithValidOrigin();
        $this->testIdempotentRetriesWithSameCsrfToken();
        $this->testRealSessionCookieHttpRoundtrip();

        echo "\n======================================================================\n";
        echo " RESUMEN: TAREA 5 (CSRF & VALIDACIÓN DE ORIGEN)\n";
        echo "======================================================================\n";
        echo " Total tests: " . ($this->passed + $this->failed) . " | ";
        echo "Pasados: {$this->passed} | Fallados: {$this->failed}\n\n";

        if ($this->failed > 0) {
            echo "❌ EXISTEN FALLOS EN LA SUITE DE CSRF Y ORIGEN.\n";
            exit(1);
        } else {
            echo "✔ TODAS LAS PRUEBAS DE CSRF Y ORIGEN PASARON AL 100%.\n\n";
        }
    }

    private function assert(string $desc, bool $condition): void {
        if ($condition) {
            $this->passed++;
            echo "  ✔ [PASS] {$desc}\n";
        } else {
            $this->failed++;
            echo "  ✖ [FAIL] {$desc}\n";
        }
    }

    private function resetSession(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            @session_destroy();
        }
        $_SESSION = [];
        Csrf::reset();
    }

    private function testCsrfTokenGenerationAndPersistence(): void {
        echo "--- 1. Generación y Persistencia del Token CSRF ---\n";
        $this->resetSession();

        $token1 = Csrf::getToken();
        $this->assert('Token generado tiene longitud exacta de 64 caracteres hex (32 bytes)', strlen($token1) === 64 && ctype_xdigit($token1));

        $token2 = Csrf::getToken();
        $this->assert('Llamadas consecutivas en la misma sesión retornan el mismo token CSRF activo', $token1 === $token2);

        $this->assert('Token está persistido en $_SESSION[\'pmo_csrf_token\']', ($_SESSION['pmo_csrf_token'] ?? '') === $token1);
        $this->assert('Marca de tiempo de creación registrada en sesión', !empty($_SESSION['pmo_csrf_token_time']) && is_int($_SESSION['pmo_csrf_token_time']));
    }

    private function testCsrfTokenValidationLogic(): void {
        echo "\n--- 2. Lógica de Validación de Token (hash_equals & Expiración) ---\n";
        $this->resetSession();

        $validToken = Csrf::getToken();
        $this->assert('validateToken() aprueba token legítimo', Csrf::validateToken($validToken) === true);

        $this->assert('validateToken() rechaza null', Csrf::validateToken(null) === false);
        $this->assert('validateToken() rechaza cadena vacía', Csrf::validateToken('') === false);
        $this->assert('validateToken() rechaza token de longitud incorrecta', Csrf::validateToken(substr($validToken, 0, 32)) === false);
        $this->assert('validateToken() rechaza token alterado de 64 caracteres', Csrf::validateToken(str_repeat('a', 64)) === false);

        // Simular expiración (> 7200 segundos)
        $_SESSION['pmo_csrf_token_time'] = time() - 7201;
        $this->assert('validateToken() rechaza token expirado (> 7200s)', Csrf::validateToken($validToken) === false);

        // Al pedir nuevo token tras expiración, debe regenerarse
        $newToken = Csrf::getToken();
        $this->assert('getToken() regenera automáticamente un nuevo token tras expiración', $newToken !== $validToken && strlen($newToken) === 64);
    }

    private function testCsrfTokenExtraction(): void {
        echo "\n--- 3. Extracción de Token CSRF (Headers & Payload) ---\n";
        $this->resetSession();
        $token = Csrf::getToken();

        // Desde HTTP_X_CSRF_TOKEN
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        unset($_SERVER['REDIRECT_HTTP_X_CSRF_TOKEN']);
        $this->assert('extractToken() extrae exitosamente desde HTTP_X_CSRF_TOKEN', Csrf::extractToken() === $token);

        // Desde payload array
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        $payload = ['csrf_token' => $token, 'nombre' => 'Carlos'];
        $this->assert('extractToken() extrae exitosamente desde payload array', Csrf::extractToken($payload) === $token);

        // Sin token
        $this->assert('extractToken() retorna null si no hay cabecera ni payload', Csrf::extractToken([]) === null);
    }

    private function testOriginNormalization(): void {
        echo "\n--- 4. Normalización de Origen Canónico ---\n";

        $this->assert('Normaliza HTTPS estándar sin puerto', Csrf::normalizeOrigin('https://pmo-solutions.com/') === 'https://pmo-solutions.com');
        $this->assert('Normaliza HTTP estándar puerto 80 omitido', Csrf::normalizeOrigin('http://pmo-solutions.com:80/path') === 'http://pmo-solutions.com');
        $this->assert('Normaliza HTTPS estándar puerto 443 omitido', Csrf::normalizeOrigin('https://pmo-solutions.com:443/contacto') === 'https://pmo-solutions.com');
        $this->assert('Conserva puertos no estándar', Csrf::normalizeOrigin('http://127.0.0.1:8899/foo') === 'http://127.0.0.1:8899');
        $this->assert('Convierte esquema y host a minúsculas', Csrf::normalizeOrigin('HTTPS://WWW.PMO-SOLUTIONS.COM') === 'https://www.pmo-solutions.com');
        $this->assert('Rechaza "null"', Csrf::normalizeOrigin('null') === null);
        $this->assert('Rechaza cadena vacía', Csrf::normalizeOrigin('   ') === null);
        $this->assert('Rechaza URL malformada sin esquema', Csrf::normalizeOrigin('pmo-solutions.com') === null);
    }

    private function testEnvironmentOriginRestrictions(): void {
        echo "\n--- 5. Restricción de Orígenes según Entorno ---\n";

        // Modo Producción (por defecto)
        putenv('PMO_APP_ENV=production');
        $_ENV['PMO_APP_ENV'] = 'production';
        $prodOrigins = Csrf::getAllowedOrigins(['https://pmo-solutions.com', 'http://localhost', 'http://127.0.0.1:8899']);
        
        $this->assert('En producción NO se incluye localhost', !in_array('http://localhost', $prodOrigins, true));
        $this->assert('En producción NO se incluye 127.0.0.1', !in_array('http://127.0.0.1:8899', $prodOrigins, true));
        $this->assert('En producción SÍ se incluye https://pmo-solutions.com', in_array('https://pmo-solutions.com', $prodOrigins, true));

        // Modo Desarrollo
        putenv('PMO_APP_ENV=development');
        $_ENV['PMO_APP_ENV'] = 'development';
        $devOrigins = Csrf::getAllowedOrigins(['https://pmo-solutions.com', 'http://localhost', 'http://127.0.0.1:8899']);

        $this->assert('En desarrollo SÍ se incluye localhost', in_array('http://localhost', $devOrigins, true));
        $this->assert('En desarrollo SÍ se incluye 127.0.0.1 con puerto', in_array('http://127.0.0.1:8899', $devOrigins, true));

        // Restaurar a desarrollo para pruebas locales
        putenv('PMO_APP_ENV=development');
        $_ENV['PMO_APP_ENV'] = 'development';
    }

    private function testOriginHeaderValidation(): void {
        echo "\n--- 6. Validación de Cabecera Origin (Anti-Bypass) ---\n";

        $allowed = ['https://pmo-solutions.com', 'http://127.0.0.1:8899'];

        // Origen válido
        $_SERVER['HTTP_ORIGIN'] = 'https://pmo-solutions.com';
        $this->assert('checkOrigin() aprueba origen permitido exacto', Csrf::checkOrigin($allowed) === true);

        // Origen engañoso: subdominio atacante
        $_SERVER['HTTP_ORIGIN'] = 'https://pmo-solutions.com.attacker.com';
        $this->assert('checkOrigin() RECHAZA coincidencia parcial engañosa (pmo-solutions.com.attacker.com)', Csrf::checkOrigin($allowed) === false);

        // Origen engañoso: prefijo atacante
        $_SERVER['HTTP_ORIGIN'] = 'https://attacker-pmo-solutions.com';
        $this->assert('checkOrigin() RECHAZA origen similar no autorizado', Csrf::checkOrigin($allowed) === false);

        // Origen null explícito
        $_SERVER['HTTP_ORIGIN'] = 'null';
        $this->assert('checkOrigin() RECHAZA explícitamente Origin: null', Csrf::checkOrigin($allowed) === false);

        // Origen ausente
        unset($_SERVER['HTTP_ORIGIN']);
        $this->assert('checkOrigin() retorna null si Origin no está presente', Csrf::checkOrigin($allowed) === null);
    }

    private function testRefererHeaderValidation(): void {
        echo "\n--- 7. Validación de Cabecera Referer (Fallback) ---\n";

        $allowed = ['https://pmo-solutions.com', 'http://127.0.0.1:8899'];
        unset($_SERVER['HTTP_ORIGIN']);

        // Referer válido
        $_SERVER['HTTP_REFERER'] = 'https://pmo-solutions.com/contacto';
        $this->assert('checkReferer() aprueba referer con ruta permitido', Csrf::checkReferer($allowed) === true);

        // Referer externo engañoso
        $_SERVER['HTTP_REFERER'] = 'https://evil.com/fake-pmo-solutions.com';
        $this->assert('checkReferer() RECHAZA referer externo', Csrf::checkReferer($allowed) === false);

        // Referer ausente
        unset($_SERVER['HTTP_REFERER']);
        $this->assert('checkReferer() retorna null cuando Referer no está presente', Csrf::checkReferer($allowed) === null);
    }

    private function testMissingOriginAndRefererFallback(): void {
        echo "\n--- 8. Solicitudes sin Origin ni Referer (Clientes Estrictos) ---\n";
        $this->resetSession();
        $token = Csrf::getToken();

        unset($_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_REFERER']);
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        $res = Csrf::validateRequest();
        $this->assert('validateRequest() acepta solicitud sin Origin ni Referer si el token CSRF es válido', $res['valid'] === true);

        // Con token inválido
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'token_falso_invalido';
        $resInvalid = Csrf::validateRequest();
        $this->assert('validateRequest() rechaza solicitud sin Origin/Referer si el token CSRF es inválido', $resInvalid['valid'] === false && $resInvalid['code'] === 403);
    }

    private function testCsrfMandatoryEvenWithValidOrigin(): void {
        echo "\n--- 9. Obligatoriedad de Token CSRF con Origin Válido ---\n";
        $this->resetSession();

        $_SERVER['HTTP_ORIGIN'] = 'https://pmo-solutions.com';
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);

        // Sin token
        $resNoToken = Csrf::validateRequest([]);
        $this->assert('validateRequest() RECHAZA (403) con Origin válido pero sin token CSRF', $resNoToken['valid'] === false && $resNoToken['code'] === 403);

        // Con token incorrecto
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'invalid_token_123';
        $resBadToken = Csrf::validateRequest([]);
        $this->assert('validateRequest() RECHAZA (403) con Origin válido pero token incorrecto', $resBadToken['valid'] === false && $resBadToken['code'] === 403);
    }

    private function testIdempotentRetriesWithSameCsrfToken(): void {
        echo "\n--- 10. No Renovación Destructiva para Reintentos Idempotentes ---\n";
        $this->resetSession();
        $token = Csrf::getToken();

        $_SERVER['HTTP_ORIGIN'] = 'https://pmo-solutions.com';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        $res1 = Csrf::validateRequest();
        $this->assert('Intento 1 con Idempotency-Key y CSRF token es válido', $res1['valid'] === true);

        // Intento 2 con el mismo token en la misma ventana de tiempo
        $res2 = Csrf::validateRequest();
        $this->assert('Reintento 2 con el mismo CSRF token sigue siendo válido (no se destruye prematuramente)', $res2['valid'] === true);
    }

    private function testRealSessionCookieHttpRoundtrip(): void {
        echo "\n--- 11. Prueba HTTP Real de Ciclo Completo con Cookie de Sesión (PHPSESSID) ---\n";

        $phpBinary = PHP_BINARY;
        $rootDir = str_replace('\\', '/', dirname(__DIR__));
        $tmpDir = sys_get_temp_dir();

        // 1. Simulación de GET /contacto en un proceso aislado (petición HTTP 1)
        $scriptGet = <<<PHP
<?php
define('PMO_APP_ACCESS', true);
require_once '{$rootDir}/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

\App\Core\Security::startSecureSession();
\$token = \App\Core\Csrf::getToken();
\$sid = session_id();
session_write_close();

echo json_encode(['sid' => \$sid, 'token' => \$token]);
PHP;

        $tmpFileGet = $tmpDir . DIRECTORY_SEPARATOR . 'pmo_test_get_' . uniqid() . '.php';
        file_put_contents($tmpFileGet, $scriptGet);

        $outGet = shell_exec("\"{$phpBinary}\" \"{$tmpFileGet}\"");
        @unlink($tmpFileGet);

        $dataGet = json_decode($outGet, true);
        $sessionId = $dataGet['sid'] ?? '';
        $csrfToken = $dataGet['token'] ?? '';

        $this->assert('Petición GET inicial genera token y emite cookie de sesión', !empty($sessionId) && !empty($csrfToken) && strlen($csrfToken) === 64);

        // 2. Simulación de POST /contacto con cookie y token en proceso aislado (petición HTTP 2)
        $scriptPost = <<<PHP
<?php
define('PMO_APP_ACCESS', true);
putenv('PMO_APP_ENV=development');
\$_ENV['PMO_APP_ENV'] = 'development';
\$_COOKIE['PHPSESSID'] = '{$sessionId}';
\$_SERVER['HTTP_ORIGIN'] = 'https://pmo-solutions.com';
\$_SERVER['HTTP_X_CSRF_TOKEN'] = '{$csrfToken}';

require_once '{$rootDir}/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

\$res = \App\Core\Csrf::validateRequest();
echo json_encode(\$res);
PHP;

        $tmpFilePost = $tmpDir . DIRECTORY_SEPARATOR . 'pmo_test_post_' . uniqid() . '.php';
        file_put_contents($tmpFilePost, $scriptPost);

        $outPost = shell_exec("\"{$phpBinary}\" \"{$tmpFilePost}\"");
        @unlink($tmpFilePost);

        $dataPost = json_decode($outPost, true);
        $this->assert('Petición POST posterior preservando cookie de sesión real PHPSESSID valida el token CSRF con éxito', !empty($dataPost['valid']) && $dataPost['valid'] === true);

        // 3. Simulación de POST con cookie pero token inválido
        $scriptPostInvalid = str_replace($csrfToken, 'invalid_token_xyz', $scriptPost);
        $tmpFilePostInv = $tmpDir . DIRECTORY_SEPARATOR . 'pmo_test_post_inv_' . uniqid() . '.php';
        file_put_contents($tmpFilePostInv, $scriptPostInvalid);

        $outPostInvalid = shell_exec("\"{$phpBinary}\" \"{$tmpFilePostInv}\"");
        @unlink($tmpFilePostInv);

        $dataPostInvalid = json_decode($outPostInvalid, true);
        $this->assert('Petición POST con cookie válida pero token incorrecto es rechazada con HTTP 403', isset($dataPostInvalid['valid']) && $dataPostInvalid['valid'] === false && ($dataPostInvalid['code'] ?? 0) === 403);
    }

    private function testLegacyAliasMvcDispatch(): void {
        echo "\n--- 12. Despacho Exclusivo MVC para Rutas Alias y Eliminación de Scripts Físicos ---\n";

        $phpBinary = PHP_BINARY;
        $rootDir = str_replace('\\', '/', dirname(__DIR__));
        $tmpDir = sys_get_temp_dir();

        // 1. Verificar eliminación física de scripts antiguos
        $this->assert('Script físico backend/send-contact.php fue eliminado del disco', !file_exists($rootDir . '/backend/send-contact.php'));
        $this->assert('Script físico backend/submit-claim.php fue eliminado del disco', !file_exists($rootDir . '/backend/submit-claim.php'));

        // 2. Probar que las rutas alias obsoletas /backend/*.php retornan HTTP 404 a través del Router/PHP
        $scriptAliasTest = <<<PHP
<?php
define('PMO_APP_ACCESS', true);
putenv('PMO_APP_ENV=development');
\$_ENV['PMO_APP_ENV'] = 'development';
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_SERVER['REQUEST_URI'] = '/backend/send-contact.php';
\$_SERVER['HTTP_ORIGIN'] = 'https://pmo-solutions.com';
\$_SERVER['HTTP_ACCEPT'] = 'application/json';

require_once '{$rootDir}/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

require '{$rootDir}/index.php';
PHP;

        $tmpFileAlias = $tmpDir . DIRECTORY_SEPARATOR . 'pmo_test_alias_' . uniqid() . '.php';
        file_put_contents($tmpFileAlias, $scriptAliasTest);
        $outAliasContact = shell_exec("\"{$phpBinary}\" \"{$tmpFileAlias}\"");
        @unlink($tmpFileAlias);

        $jsonAliasContact = json_decode($outAliasContact, true);
        $this->assert('Ruta alias obsoleta /backend/send-contact.php retorna HTTP 404 / no encontrada vía Router', isset($jsonAliasContact['error']) || (isset($jsonAliasContact['success']) && $jsonAliasContact['success'] === false) || str_contains($outAliasContact, '404'));

        // 3. Probar que las rutas canónicas POST /contacto/submit y POST /reclamaciones/submit exigen CSRF e Idempotency-Key
        $scriptCanonical = <<<PHP
<?php
define('PMO_APP_ACCESS', true);
putenv('PMO_APP_ENV=development');
\$_ENV['PMO_APP_ENV'] = 'development';
\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_SERVER['REQUEST_URI'] = '/contacto/submit';
\$_SERVER['HTTP_ORIGIN'] = 'https://pmo-solutions.com';
\$_SERVER['HTTP_ACCEPT'] = 'application/json';

require_once '{$rootDir}/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

\App\Core\Security::startSecureSession();
\$token = \App\Core\Csrf::getToken();
\$_SERVER['HTTP_X_CSRF_TOKEN'] = \$token;

require '{$rootDir}/index.php';
PHP;

        $tmpFileCanonical = $tmpDir . DIRECTORY_SEPARATOR . 'pmo_test_canon_' . uniqid() . '.php';
        file_put_contents($tmpFileCanonical, $scriptCanonical);
        $outCanonical = shell_exec("\"{$phpBinary}\" \"{$tmpFileCanonical}\"");
        @unlink($tmpFileCanonical);

        $jsonCanonical = json_decode($outCanonical, true);
        $this->assert('Ruta canónica /contacto/submit es atendida por ContactController y exige Idempotency-Key (HTTP 400)', isset($jsonCanonical['success']) && $jsonCanonical['success'] === false && str_contains($jsonCanonical['message'] ?? '', 'Idempotency-Key'));

        $scriptCanonicalClaim = str_replace('/contacto/submit', '/reclamaciones/submit', $scriptCanonical);
        $tmpFileCanonicalClaim = $tmpDir . DIRECTORY_SEPARATOR . 'pmo_test_canon_c_' . uniqid() . '.php';
        file_put_contents($tmpFileCanonicalClaim, $scriptCanonicalClaim);
        $outCanonicalClaim = shell_exec("\"{$phpBinary}\" \"{$tmpFileCanonicalClaim}\"");
        @unlink($tmpFileCanonicalClaim);

        $jsonCanonicalClaim = json_decode($outCanonicalClaim, true);
        $this->assert('Ruta canónica /reclamaciones/submit es atendida por ClaimController y exige Idempotency-Key (HTTP 400)', isset($jsonCanonicalClaim['success']) && $jsonCanonicalClaim['success'] === false && str_contains($jsonCanonicalClaim['message'] ?? '', 'Idempotency-Key'));
    }
}

// Ejecución directa de la suite
$suite = new CsrfAndOriginTest();
$suite->run();
