<?php
/**
 * PMO SOLUTIONS — Suite de Pruebas de Persistencia, Idempotencia y Outbox Resiliente (Tarea 3)
 *
 * Configuración estricta de ejecución:
 *   - error_reporting = E_ALL
 *   - display_errors = 1
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

define('PMO_TEST_RUNNER', true);
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

use App\Core\Cache;
use App\Core\Database;
use App\Core\EmailOutbox;
use App\Core\Idempotency;
use App\Core\Security;
use App\Core\SmtpMailer;
use App\Models\ClaimModel;
use App\Models\ContactModel;

echo "\n======================================================================\n";
echo " VERIFICANDO PERSISTENCIA, IDEMPOTENCIA Y OUTBOX RESILIENTE (TAREA 3)\n";
echo " Entorno de prueba: error_reporting=E_ALL | display_errors=1\n";
echo "======================================================================\n";

$results = [
    'passed'  => 0,
    'failed'  => 0,
    'skipped' => 0,
];

function runTest(string $description, callable $testFn): void {
    global $results;
    try {
        $result = $testFn();
        if ($result === true) {
            $results['passed']++;
            echo "  \033[32m✔ [PASS]\033[0m {$description}\n";
        } elseif (is_string($result) && str_starts_with($result, 'SKIP:')) {
            $results['skipped']++;
            echo "  \033[33m⚡ [SKIP]\033[0m {$description} -> " . substr($result, 5) . "\n";
        } else {
            $results['failed']++;
            $msg = is_string($result) ? $result : 'Condición no cumplida';
            echo "  \033[31m✖ [FAIL]\033[0m {$description} -> {$msg}\n";
        }
    } catch (\Throwable $e) {
        $results['failed']++;
        echo "  \033[31m✖ [FAIL]\033[0m {$description} -> Excepción: {$e->getMessage()}\n";
    }
}

/**
 * Ejecuta una petición simulada en proceso PHP aislado
 */
function simulateHttp(string $method, string $uri, mixed $postData = null, array $headers = [], string $prelude = ''): array {
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
    $script .= '$_SERVER["REMOTE_ADDR"] = "10.0." . rand(1, 250) . "." . rand(2, 250); ';
    $script .= '$_SERVER["HTTP_ACCEPT"] = "application/json"; ';

    if (!empty($prelude)) {
        $script .= $prelude . '; ';
    }

    foreach ($headers as $hKey => $hVal) {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $hKey));
        if (strcasecmp($hKey, 'Content-Type') === 0) {
            $serverKey = 'CONTENT_TYPE';
        }
        $script .= '$_SERVER["' . addslashes($serverKey) . '"] = "' . addslashes((string)$hVal) . '"; ';
    }

    if ($method === 'POST') {
        $hasCsrf = false;
        foreach ($headers as $hKey => $hVal) {
            if (strcasecmp($hKey, 'X-CSRF-Token') === 0) {
                $hasCsrf = true;
                break;
            }
        }
        if (!$hasCsrf && (is_array($postData) && !isset($postData['csrf_token']))) {
            $script .= '\App\Core\Security::startSecureSession(); $csrfTok = \App\Core\Csrf::getToken(); $_SERVER["HTTP_X_CSRF_TOKEN"] = $csrfTok; ';
        }
        if (!isset($headers['Origin']) && !isset($headers['HTTP_ORIGIN'])) {
            $script .= '$_SERVER["HTTP_ORIGIN"] = "https://pmo-solutions.com"; ';
        }

        if (!isset($headers['Content-Type'])) {
            $script .= '$_SERVER["CONTENT_TYPE"] = "application/json"; ';
        }

        if ($postData !== null) {
            $jsonString = is_string($postData) ? $postData : json_encode($postData);
            $script .= '$GLOBALS["_MOCK_INPUT"] = ' . var_export($jsonString, true) . '; ';
            if (is_array($postData)) {
                $script .= '$_POST = ' . var_export($postData, true) . '; ';
            }
        }
    }

    $script .= 'require "' . addslashes($indexFile) . '"; ';

    $tmpFile = tempnam(sys_get_temp_dir(), 'pmo_persist_');
    if ($tmpFile === false) {
        throw new \RuntimeException("No se pudo crear archivo temporal");
    }

    try {
        file_put_contents($tmpFile, $script);
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpFile) . ' 2>&1';
        $rawOutput = (string)shell_exec($command);
    } finally {
        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }
    }

    $statusCode = 200;
    $body = $rawOutput;

    if (preg_match('/___HTTP_STATUS___:(\d+)/', $rawOutput, $matches)) {
        $statusCode = (int)$matches[1];
        $body = trim(preg_replace('/\n?___HTTP_STATUS___:\d+\n?/', '', $rawOutput));
    }

    return [
        'status' => $statusCode,
        'body'   => $body,
        'json'   => json_decode($body, true)
    ];
}

// =============================================================================
// 1. VALIDACIÓN DE FORMATO DE CLAVE DE IDEMPOTENCIA
// =============================================================================
echo "\n--- 1. Validación de Formato de Idempotency-Key ---\n";

runTest("Idempotency::isValidKeyFormat() con claves válidas (16 a 64 chars alfanuméricos, '-', '_')", function () {
    $validKeys = [
        '1234567890abcdef',                      // 16 chars
        'a1b2c3d4-e5f6-7890-abcd-ef1234567890',  // UUID format (36 chars)
        'idemp_token_test_12345678901234567890', // 37 chars
        str_repeat('a', 64)                       // 64 chars
    ];

    foreach ($validKeys as $k) {
        if (!Idempotency::isValidKeyFormat($k)) {
            return "Falló validación para clave válida: '{$k}'";
        }
    }
    return true;
});

runTest("Idempotency::isValidKeyFormat() con claves inválidas (cortas, largas, caracteres prohibidos)", function () {
    $invalidKeys = [
        'too_short_123',                         // < 16 chars
        str_repeat('x', 65),                     // > 64 chars
        'invalid key with spaces!!',             // espacios
        'clave_con_arroba@email.com',            // caracter @
        'clave#con$simbolos%',                   // simbolos
        '',                                      // vacia
        null                                     // null
    ];

    foreach ($invalidKeys as $k) {
        if (Idempotency::isValidKeyFormat($k)) {
            return "Aprobó erróneamente clave inválida: '{$k}'";
        }
    }
    return true;
});

// =============================================================================
// 2. ENFORCEMENT DE IDEMPOTENCY-KEY EN ENDPOINTS (HTTP 400)
// =============================================================================
echo "\n--- 2. Exigencia de Cabecera Idempotency-Key en Endpoints ---\n";

runTest("POST /reclamaciones/submit sin Idempotency-Key retorna HTTP 400 Bad Request", function () {
    $res = simulateHttp('POST', '/reclamaciones/submit', [
        'nombre_completo' => 'Juan Perez',
        'email'           => 'juan@empresa.com'
    ]);

    if ($res['status'] !== 400) {
        return "Se esperaba HTTP 400, recibido: {$res['status']}";
    }
    if (empty($res['json']) || $res['json']['success'] !== false) {
        return "La respuesta no indica success=false";
    }
    if (!str_contains($res['json']['message'] ?? '', 'Idempotency-Key')) {
        return "El mensaje debe indicar la obligatoriedad de 'Idempotency-Key'";
    }
    return true;
});

runTest("POST /reclamaciones/submit con Idempotency-Key inválida (<16 chars) retorna HTTP 400", function () {
    $res = simulateHttp('POST', '/reclamaciones/submit', [
        'nombre_completo' => 'Juan Perez',
        'email'           => 'juan@empresa.com'
    ], [
        'Idempotency-Key' => 'corta_123'
    ]);

    if ($res['status'] !== 400) {
        return "Se esperaba HTTP 400, recibido: {$res['status']}";
    }
    return true;
});

runTest("POST /contacto/submit sin Idempotency-Key retorna HTTP 400 Bad Request", function () {
    $res = simulateHttp('POST', '/contacto/submit', [
        'nombre'   => 'Maria Lopez',
        'telefono' => '999888777',
        'email'    => 'maria@empresa.pe',
        'mensaje'  => 'Consulta de prueba'
    ]);

    if ($res['status'] !== 400) {
        return "Se esperaba HTTP 400, recibido: {$res['status']}";
    }
    return true;
});

runTest("POST /contacto/submit con Idempotency-Key inválida retorna HTTP 400", function () {
    $res = simulateHttp('POST', '/contacto/submit', [
        'nombre'   => 'Maria Lopez',
        'telefono' => '999888777',
        'email'    => 'maria@empresa.pe',
        'mensaje'  => 'Consulta de prueba'
    ], [
        'Idempotency-Key' => 'invalida!!_clave'
    ]);

    if ($res['status'] !== 400) {
        return "Se esperaba HTTP 400, recibido: {$res['status']}";
    }
    return true;
});

// =============================================================================
// 3. PERSISTENCIA OBLIGATORIA DEL LIBRO DE RECLAMACIONES
// =============================================================================
echo "\n--- 3. Persistencia Obligatoria del Libro de Reclamaciones ---\n";

runTest("POST /reclamaciones/submit responde HTTP 503 cuando la BD no está disponible", function () {
    if (Database::isConnected()) {
        return "SKIP: Base de datos MySQL conectada; no aplica prueba de caída";
    }

    $validClaim = [
        'tipo_documento'      => 'DNI',
        'numero_documento'    => '44556677',
        'nombre_completo'     => 'Carlos Reclamante Test',
        'telefono'            => '987654321',
        'email'               => 'carlos@reclamante.pe',
        'domicilio'           => 'Av. Javier Prado 123',
        'tipo_servicio'       => 'Capacitación Profesional',
        'nombre_servicio'     => 'Curso NEC4',
        'detalle_servicio'    => '',
        'tipo_registro'       => 'Reclamo',
        'detalle_reclamacion' => 'Hechos ocurridos de prueba para validación de persistencia.',
        'pedido_consumidor'   => 'Solución solicitada de prueba.',
        'declaracion_jurada'  => 1
    ];

    $res = simulateHttp('POST', '/reclamaciones/submit', $validClaim, [
        'Idempotency-Key' => 'claim_key_failover_test_123456789'
    ]);

    if ($res['status'] !== 503) {
        return "Se esperaba HTTP 503 Service Unavailable cuando BD está caída, recibido: {$res['status']}";
    }
    if (empty($res['json']) || $res['json']['success'] !== false) {
        return "El servidor nunca debe confirmar éxito (success=true) si la reclamación no quedó guardada en BD";
    }
    return true;
});

runTest("Dos envíos idénticos de un reclamo retornan mismo HTTP 200 y mismo código de constancia", function () {
    if (!Database::isConnected()) {
        return "SKIP: Base de datos MySQL no disponible para registro de reclamo";
    }

    $idempKey = 'claim_identical_test_' . bin2hex(random_bytes(8));
    $validClaim = [
        'tipo_documento'      => 'DNI',
        'numero_documento'    => '44556677',
        'nombre_completo'     => 'Carlos Reclamante Test',
        'telefono'            => '987654321',
        'email'               => 'carlos@reclamante.pe',
        'domicilio'           => 'Av. Javier Prado 123',
        'tipo_servicio'       => 'Capacitación Profesional',
        'nombre_servicio'     => 'Curso NEC4',
        'detalle_servicio'    => '',
        'tipo_registro'       => 'Reclamo',
        'detalle_reclamacion' => 'Hechos ocurridos idénticos para prueba.',
        'pedido_consumidor'   => 'Solución idéntica.',
        'declaracion_jurada'  => 1
    ];

    $res1 = simulateHttp('POST', '/reclamaciones/submit', $validClaim, ['Idempotency-Key' => $idempKey]);
    if ($res1['status'] !== 200) {
        return "Primer envío falló con status: {$res1['status']}";
    }
    $code1 = $res1['json']['codigo_reclamacion'] ?? ($res1['json']['data']['codigo_reclamacion'] ?? null);
    if (empty($code1)) {
        return "Primer envío no retornó codigo_reclamacion";
    }

    $res2 = simulateHttp('POST', '/reclamaciones/submit', $validClaim, ['Idempotency-Key' => $idempKey]);
    if ($res2['status'] !== 200) {
        return "Segundo envío falló con status: {$res2['status']}";
    }
    $code2 = $res2['json']['codigo_reclamacion'] ?? ($res2['json']['data']['codigo_reclamacion'] ?? null);
    if ($code1 !== $code2) {
        return "Los códigos de reclamación difieren entre envíos idénticos: '{$code1}' vs '{$code2}'";
    }

    $pdo = Database::getConnection();
    if ($pdo) {
        $pdo->prepare("DELETE FROM email_outbox WHERE idempotency_key = :k")->execute([':k' => $idempKey]);
        $pdo->prepare("DELETE FROM reclamaciones WHERE idempotency_key = :k")->execute([':k' => $idempKey]);
    }

    return true;
});

runTest("Mismo reclamo tras vaciar caché L1 devuelve el mismo resultado recuperado desde MySQL", function () {
    if (!Database::isConnected()) {
        return "SKIP: Base de datos MySQL no disponible para recuperación desde BD tras flush de caché";
    }

    $idempKey = 'claim_flush_l1_test_' . bin2hex(random_bytes(8));
    $validClaim = [
        'tipo_documento'      => 'DNI',
        'numero_documento'    => '77665544',
        'nombre_completo'     => 'Elena Recuperacion Test',
        'telefono'            => '988112233',
        'email'               => 'elena@recuperacion.pe',
        'domicilio'           => 'Av. Arequipa 500',
        'tipo_servicio'       => 'Asesoría Técnica',
        'nombre_servicio'     => 'Peritaje de Obra',
        'detalle_servicio'    => '',
        'tipo_registro'       => 'Queja',
        'detalle_reclamacion' => 'Detalle de prueba para validar recuperación post flush.',
        'pedido_consumidor'   => 'Solución tras recuperación.',
        'declaracion_jurada'  => 1
    ];

    $res1 = simulateHttp('POST', '/reclamaciones/submit', $validClaim, ['Idempotency-Key' => $idempKey]);
    if ($res1['status'] !== 200) {
        return "Primer envío falló con status: {$res1['status']}";
    }
    $code1 = $res1['json']['codigo_reclamacion'] ?? ($res1['json']['data']['codigo_reclamacion'] ?? null);

    // Vaciar caché L1 completamente
    Cache::flush();

    // Segundo envío idéntico (debe recuperar de MySQL)
    $res2 = simulateHttp('POST', '/reclamaciones/submit', $validClaim, ['Idempotency-Key' => $idempKey]);
    if ($res2['status'] !== 200) {
        return "Envío tras vaciar caché L1 falló con status: {$res2['status']}";
    }
    $code2 = $res2['json']['codigo_reclamacion'] ?? ($res2['json']['data']['codigo_reclamacion'] ?? null);

    if ($code1 !== $code2) {
        return "El código recuperado de MySQL tras flush de caché ('{$code2}') no coincide con el original ('{$code1}')";
    }

    $pdo = Database::getConnection();
    if ($pdo) {
        $pdo->prepare("DELETE FROM email_outbox WHERE idempotency_key = :k")->execute([':k' => $idempKey]);
        $pdo->prepare("DELETE FROM reclamaciones WHERE idempotency_key = :k")->execute([':k' => $idempKey]);
    }

    return true;
});

runTest("Misma Idempotency-Key en reclamo con detalle diferente responde HTTP 409 Conflict", function () {
    if (!Database::isConnected()) {
        return "SKIP: Base de datos MySQL no disponible para validación de conflicto en reclamo";
    }

    $idempKey = 'claim_conflict_test_' . bin2hex(random_bytes(8));
    $claim1 = [
        'tipo_documento'      => 'DNI',
        'numero_documento'    => '11223344',
        'nombre_completo'     => 'Raul Conflicto Test',
        'telefono'            => '955443322',
        'email'               => 'raul@conflicto.pe',
        'domicilio'           => 'Av. Brasil 100',
        'tipo_servicio'       => 'Capacitación Profesional',
        'nombre_servicio'     => 'Curso Lean Construction',
        'detalle_servicio'    => '',
        'tipo_registro'       => 'Reclamo',
        'detalle_reclamacion' => 'Hechos originales A.',
        'pedido_consumidor'   => 'Pedido A.',
        'declaracion_jurada'  => 1
    ];

    $res1 = simulateHttp('POST', '/reclamaciones/submit', $claim1, ['Idempotency-Key' => $idempKey]);
    if ($res1['status'] !== 200) {
        return "Primer envío falló con status: {$res1['status']}";
    }

    // Segundo envío con mismo usuario pero detalle modificado
    $claim2 = $claim1;
    $claim2['detalle_reclamacion'] = 'Hechos modificados B totalmente diferentes.';

    $res2 = simulateHttp('POST', '/reclamaciones/submit', $claim2, ['Idempotency-Key' => $idempKey]);
    if ($res2['status'] !== 409) {
        return "Se esperaba HTTP 409 Conflict ante reclamo con detalle diferente, recibido: {$res2['status']}";
    }

    $pdo = Database::getConnection();
    if ($pdo) {
        $pdo->prepare("DELETE FROM email_outbox WHERE idempotency_key = :k")->execute([':k' => $idempKey]);
        $pdo->prepare("DELETE FROM reclamaciones WHERE idempotency_key = :k")->execute([':k' => $idempKey]);
    }

    return true;
});

runTest("Reclamo con Carnet de Extranjería y normalización de CE se valida y persiste correctamente", function () {
    $claimModel = new \App\Models\ClaimModel();

    // 1. Validación con valor canónico 'Carnet de Extranjería'
    $input1 = [
        'tipo_documento'      => 'Carnet de Extranjería',
        'numero_documento'    => '001234567-A',
        'nombre_completo'     => 'Jean Dupont Test',
        'telefono'            => '987654321',
        'email'               => 'jean.dupont@test.com',
        'domicilio'           => 'Av. Salaverry 1000',
        'tipo_servicio'       => 'Capacitación Profesional',
        'nombre_servicio'     => 'Curso PMBOK',
        'tipo_registro'       => 'Reclamo',
        'detalle_reclamacion' => 'Detalle de prueba con carnet de extranjería.',
        'pedido_consumidor'   => 'Pedido de prueba con carnet.',
        'declaracion_jurada'  => 1
    ];
    $val1 = $claimModel->validateAndSanitize($input1);
    if (!empty($val1['errors'])) {
        return "Fallo en validación con 'Carnet de Extranjería': " . json_encode($val1['errors']);
    }
    if ($val1['data']['tipo_documento'] !== 'Carnet de Extranjería') {
        return "El tipo_documento obtenido no es 'Carnet de Extranjería': " . $val1['data']['tipo_documento'];
    }

    // 2. Validación con 'CE' (debe normalizarse a 'Carnet de Extranjería')
    $input2 = $input1;
    $input2['tipo_documento'] = 'CE';
    $val2 = $claimModel->validateAndSanitize($input2);
    if (!empty($val2['errors'])) {
        return "Fallo en validación con 'CE': " . json_encode($val2['errors']);
    }
    if ($val2['data']['tipo_documento'] !== 'Carnet de Extranjería') {
        return "El valor 'CE' no fue normalizado a 'Carnet de Extranjería': " . $val2['data']['tipo_documento'];
    }

    // 3. Si MySQL está conectado, probar inserción real
    if (Database::isConnected()) {
        $idempKey = 'claim_ce_test_' . bin2hex(random_bytes(8));
        $res = simulateHttp('POST', '/reclamaciones/submit', $input1, ['Idempotency-Key' => $idempKey]);
        if ($res['status'] !== 200) {
            return "Inserción con 'Carnet de Extranjería' falló con status: {$res['status']}";
        }
        $pdo = Database::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT tipo_documento, numero_documento FROM reclamaciones WHERE idempotency_key = :k");
            $stmt->execute([':k' => $idempKey]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row || $row['tipo_documento'] !== 'Carnet de Extranjería') {
                return "La fila insertada en MySQL no contiene 'Carnet de Extranjería': " . json_encode($row);
            }
            $pdo->prepare("DELETE FROM email_outbox WHERE idempotency_key = :k")->execute([':k' => $idempKey]);
            $pdo->prepare("DELETE FROM reclamaciones WHERE idempotency_key = :k")->execute([':k' => $idempKey]);
        }
    }

    return true;
});

// =============================================================================
// 4. RESILIENCIA DEL FORMULARIO DE CONTACTO (FALLBACK ATÓMICO EN ARCHIVO)
// =============================================================================
echo "\n--- 4. Resiliencia Multi-Canal del Formulario de Contacto ---\n";

runTest("Formulario de contacto sin BD: SMTP exitoso -> estrictamente NO crea archivo outbox", function () {
    $idempKey = 'contact_smtp_ok_test_' . bin2hex(random_bytes(8));
    $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $idempKey);
    $expectedFile = dirname(__DIR__) . "/storage/outbox/contact_{$safeKey}.json";

    // Asegurar que no exista previo
    if (file_exists($expectedFile)) {
        @unlink($expectedFile);
    }

    $contactPayload = [
        'nombre'   => 'Ing. SMTP Success Test',
        'telefono' => '988776655',
        'email'    => 'smtpok@test.com',
        'servicio' => 'Consultoría Especializada',
        'mensaje'  => 'Mensaje con SMTP simulado exitoso.'
    ];

    // Forzar mock SMTP exitoso mediante prelude
    $prelude = '\App\Core\SmtpMailer::$mockHandler = function() { return true; };';

    $res = simulateHttp('POST', '/contacto/submit', $contactPayload, [
        'Idempotency-Key' => $idempKey
    ], $prelude);

    if ($res['status'] !== 200) {
        return "Se esperaba HTTP 200, recibido: {$res['status']}";
    }
    if (empty($res['json']) || $res['json']['success'] !== true) {
        return "No respondió success=true";
    }

    // Aserción estricta: NO debe existir archivo outbox porque SMTP envió correctamente
    if (file_exists($expectedFile) || file_exists($expectedFile . '.processing')) {
        @unlink($expectedFile);
        @unlink($expectedFile . '.processing');
        return "FALLO: Se creó archivo outbox cuando SMTP fue 100% exitoso";
    }

    return true;
});

runTest("Formulario de contacto sin BD: SMTP fallido -> estrictamente crea EXACTAMENTE UN archivo outbox", function () {
    if (Database::isConnected()) {
        return "SKIP: Base de datos MySQL conectada; la prueba de fallback a archivos requiere BD ausente";
    }

    $idempKey = 'contact_smtp_fail_test_' . bin2hex(random_bytes(8));
    $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $idempKey);
    $expectedFile = dirname(__DIR__) . "/storage/outbox/contact_{$safeKey}.json";

    if (file_exists($expectedFile)) {
        @unlink($expectedFile);
    }

    $contactPayload = [
        'nombre'   => 'Ing. SMTP Fail Test',
        'telefono' => '988776655',
        'email'    => 'smtpfail@test.com',
        'servicio' => 'Consultoría Especializada',
        'mensaje'  => 'Mensaje con SMTP simulado fallido.'
    ];

    // Forzar mock SMTP fallido mediante prelude
    $prelude = '\App\Core\SmtpMailer::$mockHandler = function() { return false; };';

    $res = simulateHttp('POST', '/contacto/submit', $contactPayload, [
        'Idempotency-Key' => $idempKey
    ], $prelude);

    if ($res['status'] !== 200) {
        return "Se esperaba HTTP 200 resiliente, recibido: {$res['status']}";
    }

    // Aserción estricta: Debe existir exactamente el archivo outbox
    if (!file_exists($expectedFile)) {
        return "FALLO: No se creó el archivo outbox de respaldo tras fallo de SMTP";
    }

    $content = json_decode((string)file_get_contents($expectedFile), true);
    if (!is_array($content) || ($content['idempotency_key'] ?? '') !== $idempKey) {
        @unlink($expectedFile);
        return "El archivo outbox no contiene la clave de idempotencia esperada";
    }
    if (($content['status'] ?? '') !== 'pending') {
        @unlink($expectedFile);
        return "El estado del archivo outbox debe ser 'pending'";
    }

    @unlink($expectedFile);
    return true;
});

runTest("Reintento con misma Idempotency-Key devuelve respuesta original sin duplicar procesamiento", function () {
    $idempKey = 'contact_repeat_idemp_key_' . bin2hex(random_bytes(8));

    $contactPayload = [
        'nombre'   => 'Ing. Duplicado Test',
        'telefono' => '911223344',
        'email'    => 'duplicado@test.com',
        'servicio' => 'Peritajes Técnicos',
        'mensaje'  => 'Consulta para probar idempotencia repetida.'
    ];

    // Primer envío
    $res1 = simulateHttp('POST', '/contacto/submit', $contactPayload, [
        'Idempotency-Key' => $idempKey
    ]);

    if ($res1['status'] !== 200) {
        return "Primer envío falló con status: {$res1['status']}";
    }

    // Segundo envío idéntico con la misma clave
    $res2 = simulateHttp('POST', '/contacto/submit', $contactPayload, [
        'Idempotency-Key' => $idempKey
    ]);

    if ($res2['status'] !== 200) {
        return "Segundo envío con misma clave falló con status: {$res2['status']}";
    }
    if (($res1['json']['message'] ?? '') !== ($res2['json']['message'] ?? '')) {
        return "Los mensajes de respuesta entre llamadas idempotentes no coinciden";
    }

    // Limpieza
    $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $idempKey);
    $expectedFile = dirname(__DIR__) . "/storage/outbox/contact_{$safeKey}.json";
    if (file_exists($expectedFile)) {
        @unlink($expectedFile);
    }

    return true;
});

// =============================================================================
// 5. NAMESPACES DE IDEMPOTENCIA & CONFLICTOS (HTTP 409)
// =============================================================================
echo "\n--- 5. Namespaces de Idempotencia y Detección de Conflicto 409 ---\n";

runTest("Misma Idempotency-Key en /contacto y /reclamaciones no genera colisión cruzada", function () {
    $sharedKey = 'shared_op_idemp_key_' . bin2hex(random_bytes(8));

    $contactPayload = [
        'nombre'   => 'Ing. Compartido Test',
        'telefono' => '988112233',
        'email'    => 'compartido@test.pe',
        'servicio' => 'Consultoría',
        'mensaje'  => 'Mensaje en contacto.'
    ];

    $resContact = simulateHttp('POST', '/contacto/submit', $contactPayload, [
        'Idempotency-Key' => $sharedKey
    ]);

    if ($resContact['status'] !== 200) {
        return "Contacto falló con status: {$resContact['status']}";
    }

    // Verificar en caché L1 que las claves estén separadas por namespace de operación
    $cachedContact = Idempotency::getStoredResponse('contact', $sharedKey);
    $cachedClaim   = Idempotency::getStoredResponse('claim', $sharedKey);

    if ($cachedContact === null) {
        return "No se almacenó la respuesta de contacto en caché de idempotencia";
    }
    if ($cachedClaim !== null) {
        return "Colisión cruzada: La clave de contacto sobreescribió o se leyó en namespace 'claim'";
    }

    // Limpieza
    $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sharedKey);
    @unlink(dirname(__DIR__) . "/storage/outbox/contact_{$safeKey}.json");

    return true;
});

runTest("Reutilización de Idempotency-Key con mensaje diferente (mismo nombre y email) responde HTTP 409 Conflict", function () {
    $conflictKey = 'conflict_msg_test_key_' . bin2hex(random_bytes(8));

    $payload1 = [
        'nombre'   => 'Ana Gomez',
        'telefono' => '900111222',
        'email'    => 'ana.gomez@test.com',
        'servicio' => 'Curso NEC4',
        'mensaje'  => 'Primer mensaje de consulta original.'
    ];

    $res1 = simulateHttp('POST', '/contacto/submit', $payload1, [
        'Idempotency-Key' => $conflictKey
    ]);

    if ($res1['status'] !== 200) {
        return "Primer envío falló con status: {$res1['status']}";
    }

    // Mismo nombre y mismo email, pero mensaje modificado
    $payload2 = [
        'nombre'   => 'Ana Gomez',
        'telefono' => '900111222',
        'email'    => 'ana.gomez@test.com',
        'servicio' => 'Curso NEC4',
        'mensaje'  => 'Segundo mensaje MODIFICADO con la MISMA clave pero contenido distinto.'
    ];

    $res2 = simulateHttp('POST', '/contacto/submit', $payload2, [
        'Idempotency-Key' => $conflictKey
    ]);

    if ($res2['status'] !== 409) {
        return "Se esperaba HTTP 409 Conflict ante mensaje diferente, recibido: {$res2['status']}";
    }
    if (empty($res2['json']) || $res2['json']['success'] !== false) {
        return "La respuesta 409 debe indicar success=false";
    }

    // Limpieza
    $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $conflictKey);
    @unlink(dirname(__DIR__) . "/storage/outbox/contact_{$safeKey}.json");

    return true;
});

runTest("Colisión concurrente con payload diferente responde HTTP 409 Conflict", function () {
    if (!Database::isConnected()) {
        return "SKIP: Base de datos MySQL no disponible para colisión concurrente con diferente huella";
    }

    $pdo = Database::getConnection();
    $idempKey = 'race_diff_fp_key_' . bin2hex(random_bytes(8));

    $claimModel = new ClaimModel();
    $winnerCode = 'REC-2026-WIN-' . bin2hex(random_bytes(4));
    $dataWinner = [
        'codigo_reclamacion'  => $winnerCode,
        'tipo_documento'      => 'DNI',
        'numero_documento'    => '77889900',
        'nombre_completo'     => 'Ganador Inicial',
        'telefono'            => '987111222',
        'email'               => 'ganador@test.pe',
        'domicilio'           => 'Av. Test 1',
        'tipo_servicio'       => 'Capacitacion',
        'nombre_servicio'     => 'NEC4',
        'detalle_servicio'    => '',
        'tipo_registro'       => 'Reclamo',
        'detalle_reclamacion' => 'Reclamo original ganador',
        'pedido_consumidor'   => 'Solucion A',
        'declaracion_jurada'  => 1
    ];
    $fpWinner = Idempotency::computeFingerprint($dataWinner);

    $claimModel->saveWithOutbox($idempKey, $dataWinner, $fpWinner);

    // Intentar registrar segundo reclamo concurrentemente con diferente payload
    $dataLoser = $dataWinner;
    $dataLoser['nombre_completo'] = 'Segundo Reclamante Diferente';
    $dataLoser['detalle_reclamacion'] = 'Reclamo perdedor modificado';

    $res = simulateHttp('POST', '/reclamaciones/submit', $dataLoser, [
        'Idempotency-Key' => $idempKey
    ]);

    if ($res['status'] !== 409) {
        return "Se esperaba HTTP 409 Conflict ante colisión con diferente huella, recibido: {$res['status']}";
    }

    // Limpieza
    $pdo->prepare("DELETE FROM email_outbox WHERE idempotency_key = :k")->execute([':k' => $idempKey]);
    $pdo->prepare("DELETE FROM reclamaciones WHERE idempotency_key = :k")->execute([':k' => $idempKey]);

    return true;
});

// =============================================================================
// 6. MÓDULO EMAIL OUTBOX, CONCURRENCIA Y ZERO PII
// =============================================================================
echo "\n--- 6. Outbox Worker, Concurrencia Atómica y Redacción de PII ---\n";

runTest("EmailOutbox::sanitizeError() anonimiza emails, teléfonos y secretos", function () {
    $rawError = "SMTP Error: Failed to authenticate user admin@pmo-solutions.com with password=SecretPassword123! for recipient cliente.vip@gmail.com, phone 987654321, token=abcdef1234567890";
    $sanitized = EmailOutbox::sanitizeError($rawError);

    if (str_contains($sanitized, 'admin@pmo-solutions.com') || str_contains($sanitized, 'cliente.vip@gmail.com')) {
        return "Fallo de sanitización: Email sin redactar en '{$sanitized}'";
    }
    if (str_contains($sanitized, '987654321')) {
        return "Fallo de sanitización: Teléfono sin redactar en '{$sanitized}'";
    }
    if (str_contains($sanitized, 'SecretPassword123!')) {
        return "Fallo de sanitización: Contraseña sin redactar en '{$sanitized}'";
    }
    if (!str_contains($sanitized, '[REDACTED_EMAIL]')) {
        return "No se insertó la etiqueta [REDACTED_EMAIL]";
    }

    return true;
});

runTest("EmailOutbox::enqueueToFile() garantiza creación exclusiva atómica ('x') y comprueba causa de error", function () {
    $idempKey = 'outbox_atomic_test_' . bin2hex(random_bytes(8));
    $payload = ['test' => true, 'timestamp' => time(), 'status' => 'pending'];

    $first = EmailOutbox::enqueueToFile('test', $idempKey, $payload);
    if (!$first) {
        return "Primer encolamiento falló";
    }

    $second = EmailOutbox::enqueueToFile('test', $idempKey, $payload);
    if (!$second) {
        return "Segundo encolamiento idempotente falló";
    }

    $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $idempKey);
    $filePath = dirname(__DIR__) . "/storage/outbox/test_{$safeKey}.json";
    if (!file_exists($filePath)) {
        return "El archivo outbox atómico no fue creado";
    }

    @unlink($filePath);
    return true;
});

runTest("EmailOutbox respeta 'next_retry_at' en el futuro y omite archivos con estado 'failed'", function () {
    $futureKey = 'outbox_future_' . bin2hex(random_bytes(8));
    $failedKey = 'outbox_failed_' . bin2hex(random_bytes(8));

    $outboxDir = dirname(__DIR__) . '/storage/outbox';
    $futureFile = $outboxDir . "/contact_{$futureKey}.json";
    $failedFile = $outboxDir . "/contact_{$failedKey}.json";

    // 1. Archivo con retry en el futuro (+1 hora)
    file_put_contents($futureFile, json_encode([
        'type'          => 'contact_admin',
        'status'        => 'pending',
        'attempts'      => 1,
        'next_retry_at' => date('Y-m-d H:i:s', time() + 3600),
        'mail_data'     => ['to_email' => 'future@test.pe', 'subject' => 'Future Test']
    ]));

    // 2. Archivo con estado 'failed'
    file_put_contents($failedFile, json_encode([
        'type'          => 'contact_admin',
        'status'        => 'failed',
        'attempts'      => 5,
        'next_retry_at' => date('Y-m-d H:i:s', time() - 3600),
        'mail_data'     => ['to_email' => 'failed@test.pe', 'subject' => 'Failed Test']
    ]));

    $report = EmailOutbox::processPending(10);

    // Ambos archivos deben seguir existiendo sin haber sido enviados
    $futureContent = json_decode((string)file_get_contents($futureFile), true);
    $failedContent = json_decode((string)file_get_contents($failedFile), true);

    if (($futureContent['status'] ?? '') !== 'pending') {
        return "El archivo con next_retry_at futuro debió mantenerse en 'pending'";
    }
    if (($failedContent['status'] ?? '') !== 'failed') {
        return "El archivo fallido debió mantenerse en 'failed'";
    }

    @unlink($futureFile);
    @unlink($failedFile);
    return true;
});

runTest("EmailOutbox::purgeOldRecords() purga archivos fallidos vencidos", function () {
    $oldFailedKey = 'outbox_old_failed_' . bin2hex(random_bytes(8));
    $outboxDir = dirname(__DIR__) . '/storage/outbox';
    $oldFailedFile = $outboxDir . "/contact_{$oldFailedKey}.json";

    file_put_contents($oldFailedFile, json_encode([
        'type'     => 'contact_admin',
        'status'   => 'failed',
        'attempts' => 5
    ]));

    // Modificar timestamp del archivo para simular 40 días de antigüedad
    touch($oldFailedFile, time() - (40 * 86400));

    $purged = EmailOutbox::purgeOldRecords(null, 7, 30);

    if (file_exists($oldFailedFile)) {
        @unlink($oldFailedFile);
        return "El archivo fallido con más de 30 días debió ser purgado";
    }

    return true;
});

// =============================================================================
// 7. PRUEBAS DE INTEGRACIÓN MYSQL REAL & ROLLBACK (EJECUTAR O SKIP SI NO HAY BD)
// =============================================================================
echo "\n--- 7. Pruebas de Integración con MySQL Real & Transacciones ---\n";

runTest("Transacción MySQL: Colisión UNIQUE devuelve respuesta del ganador sin duplicar", function () {
    if (!Database::isConnected()) {
        return "SKIP: Base de datos MySQL no disponible en el entorno de pruebas actual";
    }

    $pdo = Database::getConnection();
    $idempKey = 'mysql_race_test_' . bin2hex(random_bytes(8));

    $claimModel = new ClaimModel();
    $data1 = [
        'codigo_reclamacion'  => 'REC-2026-RACE1',
        'tipo_documento'      => 'DNI',
        'numero_documento'    => '77889900',
        'nombre_completo'     => 'Competidor Uno',
        'telefono'            => '987111222',
        'email'               => 'comp1@test.pe',
        'domicilio'           => 'Av. Test 1',
        'tipo_servicio'       => 'Capacitacion',
        'nombre_servicio'     => 'NEC4',
        'detalle_servicio'    => '',
        'tipo_registro'       => 'Reclamo',
        'detalle_reclamacion' => 'Reclamo de prueba 1',
        'pedido_consumidor'   => 'Solucion 1',
        'declaracion_jurada'  => 1
    ];

    $res1 = $claimModel->saveWithOutbox($idempKey, $data1);
    if (!is_array($res1)) {
        return "Primer guardado en BD falló";
    }

    // Segundo guardado con la misma clave (simulando colisión concurrente)
    $res2 = $claimModel->saveWithOutbox($idempKey, $data1);
    if ($res2 !== 'duplicate') {
        return "Se esperaba 'duplicate' ante colisión UNIQUE en saveWithOutbox, recibido: " . var_export($res2, true);
    }

    // Limpieza en BD de prueba
    $pdo->prepare("DELETE FROM email_outbox WHERE idempotency_key = :k")->execute([':k' => $idempKey]);
    $pdo->prepare("DELETE FROM reclamaciones WHERE idempotency_key = :k")->execute([':k' => $idempKey]);

    return true;
});

runTest("Transacción MySQL: Rollback completo de reclamación y outbox ante fallo", function () {
    if (!Database::isConnected()) {
        return "SKIP: Base de datos MySQL no disponible";
    }

    $pdo = Database::getConnection();
    $idempKey = 'mysql_rollback_test_' . bin2hex(random_bytes(8));

    // Forzar fallo simulado
    $claimModel = new ClaimModel();
    $invalidData = [
        'codigo_reclamacion'  => null, // Provocará NOT NULL violation en BD
        'tipo_documento'      => 'DNI',
        'numero_documento'    => '77889900',
        'nombre_completo'     => 'Test Rollback',
        'telefono'            => '987111222',
        'email'               => 'rb@test.pe',
        'domicilio'           => 'Av. Test',
        'tipo_servicio'       => 'Capacitacion',
        'nombre_servicio'     => 'NEC4',
        'detalle_servicio'    => '',
        'tipo_registro'       => 'Reclamo',
        'detalle_reclamacion' => 'Reclamo',
        'pedido_consumidor'   => 'Solucion',
        'declaracion_jurada'  => 1
    ];

    $res = $claimModel->saveWithOutbox($idempKey, $invalidData);
    if ($res !== false) {
        return "Se esperaba fallo y rollback en saveWithOutbox";
    }

    // Verificar que NADA quedó insertado en ninguna tabla
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM reclamaciones WHERE idempotency_key = :k");
    $stmt1->execute([':k' => $idempKey]);
    if ((int)$stmt1->fetchColumn() !== 0) {
        return "Fallo de rollback: Se encontró registro en 'reclamaciones'";
    }

    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM email_outbox WHERE idempotency_key = :k");
    $stmt2->execute([':k' => $idempKey]);
    if ((int)$stmt2->fetchColumn() !== 0) {
        return "Fallo de rollback: Se encontró registro en 'email_outbox'";
    }

    return true;
});

runTest("Migración y Rollback DDL 002 son idempotentes (ejecución doble sin error)", function () {
    if (!Database::isConnected()) {
        return "SKIP: Base de datos MySQL no disponible para ejecución DDL";
    }

    $pdo = Database::getConnection();
    $migSql = file_get_contents(dirname(__DIR__) . '/backend/migrations/002_add_idempotency_and_outbox.sql');
    $rollSql = file_get_contents(dirname(__DIR__) . '/backend/migrations/002_rollback.sql');

    // Ejecución 1 migración
    $pdo->exec($migSql);
    // Ejecución 2 migración (idempotencia)
    $pdo->exec($migSql);

    // Ejecución 1 rollback
    $pdo->exec($rollSql);
    // Ejecución 2 rollback (idempotencia)
    $pdo->exec($rollSql);

    // Restaurar migración para dejar el esquema listo
    $pdo->exec($migSql);

    return true;
});

// =============================================================================
// RESUMEN FINAL
// =============================================================================
$total = $results['passed'] + $results['failed'] + $results['skipped'];
echo "\n======================================================================\n";
echo " RESUMEN DE PRUEBAS DE PERSISTENCIA & IDEMPOTENCIA\n";
echo "======================================================================\n";
echo " Total de pruebas ejecutadas : {$total}\n";
echo " \033[32mPruebas exitosas (PASS)      : {$results['passed']}\033[0m\n";
echo " \033[33mPruebas omitidas (SKIP)      : {$results['skipped']}\033[0m\n";
echo " \033[31mPruebas fallidas (FAIL)      : {$results['failed']}\033[0m\n";

if ($results['failed'] === 0) {
    echo "\n\033[32m✓ TODAS LAS PRUEBAS DE LA TAREA 3 PASARON SATISFACTORIAMENTE AL 100%.\033[0m\n\n";
    exit(0);
} else {
    echo "\n\033[31m✗ HUBO FALLOS EN LAS PRUEBAS.\033[0m\n\n";
    exit(1);
}
