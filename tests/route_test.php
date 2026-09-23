<?php
/**
 * PMO SOLUTIONS - Suite de Verificación Integral de Rutas MVC & Endpoints API
 *
 * Configuración estricta de ejecución:
 *   - error_reporting = E_ALL
 *   - display_errors = 1
 *
 * Cobertura de pruebas:
 * 1. GET de 15 Vistas Públicas HTML (incluyendo /evaluacion-habilidades)
 * 2. Ausencia total de PHP Warnings, Notices, Deprecations o Errores en todas las respuestas
 * 3. GET de API Catálogo de Habilidades Blandas (/catalogo y /catalog)
 *    - Validación de estructura JSON
 *    - Verificación estricta de AUSENCIA DE PUNTAJES en catálogo público (seguridad de examen)
 * 4. POST Válido de Evaluación (/api/evaluacion-habilidades)
 *    - Estructura JSON completa, código EVA-2026-XXXXX, score global y radar (HTTP 201)
 * 5. POST con Form-data válido (HTTP 201)
 * 6. POST Inválidos:
 *    - Payload JSON sintácticamente corrupto / malformado: {"nombre":} -> HTTP 400 Bad Request
 *    - Payload JSON tipo primitivo string: "texto" -> HTTP 422 Unprocessable Entity
 *    - Payload JSON tipo primitivo number: 123 -> HTTP 422 Unprocessable Entity
 *    - Payload JSON tipo primitivo null: null -> HTTP 422 Unprocessable Entity
 *    - Payload JSON tipo primitivo bool: true -> HTTP 422 Unprocessable Entity
 *    - Payload JSON objeto vacío: {} -> HTTP 422 Unprocessable Entity
 *    - Payload JSON con campos omitidos -> HTTP 422
 *    - Payload JSON con correo inválido -> HTTP 422
 *    - Detección de Bot Honeypot -> HTTP 400
 *    - Reseteo de estado de error entre peticiones consecutivas
 * 7. Manejo de Rutas No Encontradas (Error 404)
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

define('PMO_TEST_RUNNER', true);

echo "\n======================================================================\n";
echo " VERIFICANDO COBERTURA INTEGRAL DE RUTAS MVC (PMO SOLUTIONS)\n";
echo " Entorno de prueba: error_reporting=E_ALL | display_errors=1\n";
echo "======================================================================\n";

$results = [
    'passed' => 0,
    'failed' => 0,
];

function runTest(string $description, callable $testFn): void {
    global $results;
    try {
        $result = $testFn();
        if ($result === true) {
            $results['passed']++;
            echo "  \033[32m✔ [PASS]\033[0m {$description}\n";
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
 * Verifica que una salida no contenga warnings, notices ni errores de PHP
 */
function assertNoPhpErrors(string $body): bool|string {
    $errorPatterns = [
        'PHP Warning:',
        'Warning:',
        'PHP Notice:',
        'Notice:',
        'PHP Deprecated:',
        'Deprecated:',
        'PHP Fatal error:',
        'Fatal error:',
        'Parse error:',
        'Uncaught Error',
        'Uncaught Exception',
    ];

    foreach ($errorPatterns as $pattern) {
        if (stripos($body, $pattern) !== false) {
            preg_match('/(' . preg_quote($pattern, '/') . '[^\n\r]+)/i', $body, $matches);
            $errLine = $matches[0] ?? $pattern;
            return "Se detectó error PHP en la respuesta: '{$errLine}'";
        }
    }

    return true;
}

/**
 * Ejecuta una petición simulada en un proceso PHP aislado con tempnam() y captura de código HTTP real
 */
function simulateRequest(string $method, string $uri, mixed $postData = null, bool $rawPayload = false, array $headers = []): array {
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
    $script .= '$_SERVER["HTTP_ACCEPT"] = "application/json, text/html, */*"; ';
    
    if ($method === 'POST') {
        $hasCsrf = false;
        foreach ($headers as $k => $v) {
            if (stripos((string)$k, 'csrf') !== false) {
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

        if (isset($headers['CONTENT_TYPE'])) {
            $script .= '$_SERVER["CONTENT_TYPE"] = "' . addslashes($headers['CONTENT_TYPE']) . '"; ';
        } else {
            $script .= '$_SERVER["CONTENT_TYPE"] = "application/json"; ';
        }

        if ($postData !== null) {
            if ($rawPayload && is_string($postData)) {
                $script .= '$GLOBALS["_MOCK_INPUT"] = ' . var_export($postData, true) . '; ';
            } elseif (isset($headers['CONTENT_TYPE']) && stripos($headers['CONTENT_TYPE'], 'x-www-form-urlencoded') !== false) {
                $script .= '$_POST = ' . var_export($postData, true) . '; ';
            } else {
                $jsonString = json_encode($postData);
                $script .= '$GLOBALS["_MOCK_INPUT"] = ' . var_export($jsonString, true) . '; ';
                $script .= '$_POST = ' . var_export($postData, true) . '; ';
            }
        }
    }

    $script .= 'require "' . addslashes($indexFile) . '"; ';

    $tmpFile = tempnam(sys_get_temp_dir(), 'pmo_route_');
    if ($tmpFile === false) {
        throw new \RuntimeException("No se pudo crear archivo temporal con tempnam()");
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
        'raw'    => $rawOutput,
    ];
}

// =============================================================================
// 1. PRUEBAS DE VISTAS PÚBLICAS HTML (GET) & AUSENCIA DE WARNINGS
// =============================================================================
echo "\n--- 1. Pruebas de Renderizado de Vistas Públicas (GET) ---\n";

$htmlRoutes = [
    '/'                       => 'PMO Solutions | Construimos Soluciones',
    '/capacitaciones'         => 'Catálogo de Capacitaciones 2026',
    '/evaluacion-habilidades' => 'Evaluación de Habilidades Blandas',
    '/contacto'               => 'Contacto',
    '/libro-de-reclamaciones' => 'Libro de Reclamaciones Virtual',
    '/nec4'                   => 'Contratos NEC4',
    '/primavera-p6'           => 'Oracle Primavera P6',
    '/dab-jrd'                => 'Dispute Boards',
    '/vdc-bim'                => 'Virtual Design and Construction',
    '/contratos-estado'       => 'Gestión del Cambio',
    '/compliance'             => 'Compliance',
    '/riesgos-pmi'            => 'Gestión Integral de Riesgos',
    '/analisis-cuantitativo'  => 'Análisis Cuantitativo',
    '/analisis-forense'       => 'Análisis Forense',
    '/eventos-compensables'   => 'Eventos Compensables',
];

foreach ($htmlRoutes as $uri => $expectedText) {
    runTest("GET {$uri} (HTTP 200, HTML válido, contiene: '{$expectedText}')", function () use ($uri, $expectedText) {
        $res = simulateRequest('GET', $uri);
        
        $errCheck = assertNoPhpErrors($res['body']);
        if ($errCheck !== true) {
            return $errCheck;
        }

        if ($res['status'] !== 200) {
            return "Código HTTP esperado 200, recibido: {$res['status']}";
        }

        if (empty($res['body'])) {
            return "Respuesta vacía del servidor";
        }

        if (strpos($res['body'], $expectedText) === false) {
            return "No se encontró el texto esperado '{$expectedText}' en el HTML renderizado";
        }

        if (strpos($res['body'], '<!DOCTYPE html>') === false && strpos($res['body'], '<html') === false) {
            return "La respuesta no contiene doctype/html válido";
        }

        return true;
    });
}

// =============================================================================
// 2. PRUEBAS DE API: CATÁLOGO DE HABILIDADES BLANDAS & AUSENCIA DE PUNTAJES
// =============================================================================
echo "\n--- 2. Pruebas de Endpoints API: Catálogo de Habilidades (GET) ---\n";

runTest("GET /api/evaluacion-habilidades/catalogo (HTTP 200, JSON catálogo, sin puntajes)", function () {
    $res = simulateRequest('GET', '/api/evaluacion-habilidades/catalogo');
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 200) {
        return "Código HTTP esperado 200, recibido: {$res['status']}";
    }

    $json = json_decode($res['body'], true);
    if (!is_array($json)) {
        return "Respuesta no es un JSON válido: " . substr($res['body'], 0, 100);
    }

    if (empty($json['success']) || $json['success'] !== true) {
        return "El campo 'success' no es true";
    }
    if (empty($json['message']) || empty($json['timestamp'])) {
        return "El JSON carece de campos base obligatorios (message, timestamp)";
    }
    if (empty($json['catalog']) || !is_array($json['catalog'])) {
        return "Falta el catálogo de competencias en la respuesta";
    }
    if (count($json['catalog']) !== 4) {
        return "Se esperaban 4 competencias, obtenidas: " . count($json['catalog']);
    }

    // ASERCIÓN DE SEGURIDAD: Ausencia total de puntajes en el catálogo público
    foreach ($json['catalog'] as $compKey => $compData) {
        if (!isset($compData['questions']) || !is_array($compData['questions'])) {
            return "Competencia '{$compKey}' no contiene preguntas";
        }
        foreach ($compData['questions'] as $qi => $question) {
            if (isset($question['points']) || isset($question['score']) || isset($question['puntaje'])) {
                return "FALLO DE SEGURIDAD: La pregunta {$qi} de {$compKey} expone puntajes en el API público";
            }
            if (!isset($question['options']) || !is_array($question['options'])) {
                return "La pregunta {$qi} de {$compKey} no contiene opciones";
            }
            foreach ($question['options'] as $oi => $option) {
                if (isset($option['points']) || isset($option['score']) || isset($option['puntaje'])) {
                    return "FALLO DE SEGURIDAD: La opción {$oi} en pregunta {$qi} de {$compKey} expone su puntaje en el catálogo público";
                }
                if (!isset($option['text'])) {
                    return "La opción {$oi} en pregunta {$qi} de {$compKey} no contiene texto";
                }
            }
        }
    }

    return true;
});

runTest("GET /api/evaluacion-habilidades/catalog (HTTP 200, Alias en inglés & ausencia de puntajes)", function () {
    $res = simulateRequest('GET', '/api/evaluacion-habilidades/catalog');
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 200) {
        return "Código HTTP esperado 200, recibido: {$res['status']}";
    }

    $json = json_decode($res['body'], true);
    if (!is_array($json) || empty($json['success'])) {
        return "Respuesta inválida en alias /catalog";
    }

    // Verificar que tampoco tenga puntajes
    foreach ($json['catalog'] as $compKey => $compData) {
        foreach ($compData['questions'] as $q) {
            foreach ($q['options'] as $o) {
                if (isset($o['points']) || isset($o['score']) || isset($o['puntaje'])) {
                    return "Puntajes detectados en /catalog";
                }
            }
        }
    }

    return true;
});

// =============================================================================
// 3. PRUEBAS DE API: ENVÍO DE EVALUACIÓN (POST VÁLIDO E INVÁLIDOS)
// =============================================================================
echo "\n--- 3. Pruebas de Endpoints API: Envío de Evaluación (POST) ---\n";

$validAnswers = [
    'comunicacion'          => ['1' => 1, '2' => 2, '3' => 1, '4' => 2, '5' => 1],
    'trabajo_equipo'        => ['1' => 1, '2' => 1, '3' => 2, '4' => 1, '5' => 2],
    'resolucion_problemas'  => ['1' => 2, '2' => 1, '3' => 1, '4' => 2, '5' => 1],
    'adaptabilidad'         => ['1' => 1, '2' => 2, '3' => 1, '4' => 1, '5' => 2],
];

// A. POST Válido JSON
runTest("POST /api/evaluacion-habilidades con payload JSON válido (HTTP 201 & estructura reporte)", function () use ($validAnswers) {
    $payload = [
        'nombre_completo' => 'Ing. Juan Pérez Test',
        'email'           => 'juan.perez@constructora.com',
        'cargo'           => 'Gerente de Proyectos',
        'empresa'         => 'Constructora Global S.A.',
        'tipo_evaluacion' => 'Individual',
        'answers'         => $validAnswers,
        'website_hp'      => '',
    ];

    $res = simulateRequest('POST', '/api/evaluacion-habilidades', $payload);
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 201) {
        return "Código HTTP esperado 201, recibido: {$res['status']}";
    }

    $json = json_decode($res['body'], true);
    if (!is_array($json)) {
        return "Respuesta no es JSON: " . substr($res['body'], 0, 100);
    }
    if (empty($json['success']) || $json['success'] !== true) {
        return "Falló el envío: " . ($json['message'] ?? 'Error desconocido');
    }
    if (empty($json['codigo_evaluacion']) || !preg_match('/^EVA-\d{4}-[A-Z0-9]{5}$/', $json['codigo_evaluacion'])) {
        return "Código de evaluación inválido o faltante: " . ($json['codigo_evaluacion'] ?? 'null');
    }
    if (!isset($json['report']['summary']['global_score']) || $json['report']['summary']['global_score'] <= 0) {
        return "No se calculó el puntaje global del reporte correctamente";
    }
    if (empty($json['report']['summary']['maturity_level'])) {
        return "Falta el nivel de madurez en el resumen";
    }
    if (empty($json['report']['summary']['radar_data']['datasets'])) {
        return "Faltan los datos formateados para el gráfico radar (Chart.js)";
    }
    // Aserciones de minimización y no exposición (Scope Ajustado)
    if (isset($json['report_access_token'])) {
        return "No debe exponerse report_access_token en la respuesta inmediata";
    }
    if (isset($json['evaluation_id'])) {
        return "No debe exponerse evaluation_id en la respuesta inmediata";
    }
    if ($json['report_retrieval_available'] !== false) {
        return "report_retrieval_available debe ser estrictamente false";
    }
    if (isset($json['report']['participant']['email'])) {
        return "No debe exponerse el email del participante dentro del reporte público";
    }
    return true;
});

// B. POST Form-Data Rechazado con HTTP 415 (Tarea 6: Content-Type application/json estricto)
runTest("POST /api/evaluacion-habilidades con Form-Data es rechazado con HTTP 415", function () use ($validAnswers) {
    $formData = [
        'nombre_completo' => 'Ing. Maria Rodriguez',
        'email'           => 'maria.rodriguez@empresa.pe',
        'cargo'           => 'Coordinadora BIM',
        'empresa'         => 'Ingeniería Andina',
        'tipo_evaluacion' => 'Individual',
        'answers'         => $validAnswers,
        'website_hp'      => '',
    ];

    $res = simulateRequest('POST', '/api/evaluacion-habilidades', $formData, false, [
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded'
    ]);
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 415) {
        return "Código HTTP esperado 415 (Unsupported Media Type), recibido: {$res['status']}";
    }

    $json = json_decode($res['body'], true);
    if (!is_array($json) || !isset($json['success']) || $json['success'] !== false) {
        return "Respuesta no es JSON de error 415 válido";
    }
    return true;
});

// =============================================================================
// 4. PRUEBAS DE DISTINCIÓN JSON: SINTAXIS CORRUPTA VS ESTRUCTURA INVÁLIDA
// =============================================================================
echo "\n--- 4. Pruebas de Distinción JSON: Sintaxis Corrupta vs Estructura Inválida ---\n";

// Caso 1: JSON sintácticamente corrupto
runTest("POST /api/evaluacion-habilidades con payload '{\"nombre\":}' (HTTP 400 Bad Request)", function () {
    $res = simulateRequest('POST', '/api/evaluacion-habilidades', '{"nombre":}', true);
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 400) {
        return "Código HTTP esperado 400, recibido: {$res['status']}";
    }

    $json = json_decode($res['body'], true);
    if (!is_array($json) || $json['success'] !== false) {
        return "Se esperaba JSON con success=false";
    }
    return true;
});

// Caso 2: Primitivo string
runTest("POST /api/evaluacion-habilidades con payload '\"texto\"' (HTTP 422 Unprocessable Entity)", function () {
    $res = simulateRequest('POST', '/api/evaluacion-habilidades', '"texto"', true);
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 422) {
        return "Código HTTP esperado 422, recibido: {$res['status']}";
    }

    $json = json_decode($res['body'], true);
    if (!is_array($json) || $json['success'] !== false) {
        return "Se esperaba fallo por campos obligatorios faltantes";
    }
    return true;
});

// Caso 3: Primitivo número
runTest("POST /api/evaluacion-habilidades con payload '123' (HTTP 422 Unprocessable Entity)", function () {
    $res = simulateRequest('POST', '/api/evaluacion-habilidades', '123', true);
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 422) {
        return "Código HTTP esperado 422, recibido: {$res['status']}";
    }
    return true;
});

// Caso 4: Primitivo null
runTest("POST /api/evaluacion-habilidades con payload 'null' (HTTP 422 Unprocessable Entity)", function () {
    $res = simulateRequest('POST', '/api/evaluacion-habilidades', 'null', true);
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 422) {
        return "Código HTTP esperado 422, recibido: {$res['status']}";
    }
    return true;
});

// Caso 5: Primitivo booleano
runTest("POST /api/evaluacion-habilidades con payload 'true' (HTTP 422 Unprocessable Entity)", function () {
    $res = simulateRequest('POST', '/api/evaluacion-habilidades', 'true', true);
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 422) {
        return "Código HTTP esperado 422, recibido: {$res['status']}";
    }
    return true;
});

// Caso 6: Objeto vacío {}
runTest("POST /api/evaluacion-habilidades con payload '{}' (HTTP 422 Unprocessable Entity)", function () {
    $res = simulateRequest('POST', '/api/evaluacion-habilidades', '{}', true);
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 422) {
        return "Código HTTP esperado 422, recibido: {$res['status']}";
    }
    return true;
});

// Caso 7: Payload completamente vacío
runTest("POST /api/evaluacion-habilidades con payload vacío '' (HTTP 422 Unprocessable Entity)", function () {
    $res = simulateRequest('POST', '/api/evaluacion-habilidades', '', true);
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 422) {
        return "Código HTTP esperado 422, recibido: {$res['status']}";
    }
    return true;
});

// Caso 8: Correo electrónico con formato inválido
runTest("POST /api/evaluacion-habilidades con correo inválido (HTTP 422)", function () use ($validAnswers) {
    $payload = [
        'nombre_completo' => 'Ing. Correo Inválido',
        'email'           => 'correo_sin_arroba_ni_punto',
        'cargo'           => 'Ingeniero',
        'empresa'         => 'Constructora',
        'tipo_evaluacion' => 'Individual',
        'answers'         => $validAnswers,
        'website_hp'      => '',
    ];

    $res = simulateRequest('POST', '/api/evaluacion-habilidades', $payload);
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 422) {
        return "Código HTTP esperado 422, recibido: {$res['status']}";
    }

    $json = json_decode($res['body'], true);
    if (!is_array($json) || $json['success'] !== false) {
        return "Se esperaba fallo por correo inválido";
    }
    return true;
});

// Caso 9: Honeypot activado por bot de spam
runTest("POST /api/evaluacion-habilidades con Honeypot activado (HTTP 400 Bad Request)", function () use ($validAnswers) {
    $payload = [
        'nombre_completo' => 'Bot Spammer',
        'email'           => 'spam@bot.com',
        'cargo'           => 'Bot',
        'empresa'         => 'Spam Corp',
        'tipo_evaluacion' => 'Individual',
        'answers'         => $validAnswers,
        'website_hp'      => 'http://sitio-malicioso-trampa.com', // Honeypot completado
    ];

    $res = simulateRequest('POST', '/api/evaluacion-habilidades', $payload);
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 400) {
        return "Código HTTP esperado 400, recibido: {$res['status']}";
    }

    $json = json_decode($res['body'], true);
    if (!is_array($json) || $json['success'] !== false) {
        return "Se esperaba bloqueo por honeypot";
    }
    return true;
});

// Caso 10: Reseteo de estado de error entre peticiones consecutivas
runTest("Verificación de reseteo de jsonSyntaxError entre peticiones consecutivas", function () use ($validAnswers) {
    // 1. Petición con JSON corrupto
    $res1 = simulateRequest('POST', '/api/evaluacion-habilidades', '{"invalido":', true);
    if ($res1['status'] !== 400) {
        return "Paso 1: Se esperaba status 400 para JSON corrupto, recibido: {$res1['status']}";
    }

    // 2. Petición inmediata con JSON válido
    $payload = [
        'nombre_completo' => 'Ing. Juan Reseteo',
        'email'           => 'juan.reseteo@constructora.com',
        'cargo'           => 'Gerente',
        'empresa'         => 'Empresa Reseteo',
        'tipo_evaluacion' => 'Individual',
        'answers'         => $validAnswers,
        'website_hp'      => '',
    ];
    $res2 = simulateRequest('POST', '/api/evaluacion-habilidades', $payload);
    if ($res2['status'] !== 201) {
        return "Paso 2: Se esperaba status 201 tras reseteo de estado, recibido: {$res2['status']}";
    }

    return true;
});

// =============================================================================
// 5. PRUEBA DE RUTAS 404 (NOT FOUND)
// =============================================================================
echo "\n--- 5. Pruebas de Manejo de Errores (404 Not Found) ---\n";

runTest("GET /ruta-inexistente-test (HTTP 404, Renderiza página 404 sin errores)", function () {
    $res = simulateRequest('GET', '/ruta-inexistente-test');
    
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 404) {
        return "Código HTTP esperado 404, recibido: {$res['status']}";
    }

    $is404 = strpos($res['body'], '404') !== false || strpos($res['body'], 'Desvío en la Ruta Crítica') !== false;
    if (!$is404) {
        return "No se renderizó la vista 404 esperada";
    }
    return true;
});

// =============================================================================
// 6. PRUEBAS DE RUTAS ADMINISTRATIVAS & CONSULTAS DESHABILITADAS (SCOPE AJUSTADO)
// =============================================================================
echo "\n--- 6. Pruebas de Rutas Administrativas & Consultas Deshabilitadas (Scope Ajustado) ---\n";

runTest("GET /admin/login (HTTP 404: Vista administrativa deshabilitada)", function () {
    $res = simulateRequest('GET', '/admin/login');
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 404) {
        return "Código HTTP esperado 404, recibido: {$res['status']}";
    }
    return true;
});

runTest("POST /admin/login (HTTP 404: Endpoint administrativo deshabilitado)", function () {
    $res = simulateRequest('POST', '/admin/login', ['username' => 'admin', 'password' => 'pass']);
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 404) {
        return "Código HTTP esperado 404, recibido: {$res['status']}";
    }
    return true;
});

runTest("POST /admin/logout (HTTP 404: Endpoint de logout deshabilitado)", function () {
    $res = simulateRequest('POST', '/admin/logout');
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 404) {
        return "Código HTTP esperado 404, recibido: {$res['status']}";
    }
    return true;
});

runTest("GET /evaluacion-habilidades/resultados (HTTP 404: Panel administrativo oculto)", function () {
    $res = simulateRequest('GET', '/evaluacion-habilidades/resultados');
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    if ($res['status'] !== 404) {
        return "Código HTTP esperado 404, recibido: {$res['status']}";
    }
    return true;
});

runTest("GET /api/evaluacion-habilidades/{codigo} (HTTP 404: Consulta posterior deshabilitada)", function () {
    $res1 = simulateRequest('GET', '/api/evaluacion-habilidades/EVA-2026-TEST1');
    $res2 = simulateRequest('GET', '/api/evaluacion-habilidades/EVA-2026-TEST2');

    $errCheck1 = assertNoPhpErrors($res1['body']);
    if ($errCheck1 !== true) return $errCheck1;
    $errCheck2 = assertNoPhpErrors($res2['body']);
    if ($errCheck2 !== true) return $errCheck2;

    if ($res1['status'] !== 404 || $res2['status'] !== 404) {
        return "Ambas respuestas deben retornar 404";
    }

    if ($res1['body'] !== $res2['body']) {
        return "Las respuestas 404 no son idénticas byte a byte";
    }

    return true;
});

// --- 7. Pruebas de Rutas de Cuentas de Usuario Definitivamente Eliminadas ---
$userDisabledRoutes = [
    ['GET', '/registro'],
    ['POST', '/registro'],
    ['GET', '/login'],
    ['POST', '/login'],
    ['GET', '/mi-cuenta'],
    ['POST', '/logout'],
];

foreach ($userDisabledRoutes as [$uMethod, $uUri]) {
    runTest("{$uMethod} {$uUri} (HTTP 404: Ruta de cuenta de usuario eliminada)", function () use ($uMethod, $uUri) {
        $res = simulateRequest($uMethod, $uUri, $uMethod === 'POST' ? ['csrf_token' => 'dummy'] : null);
        $errCheck = assertNoPhpErrors($res['body']);
        if ($errCheck !== true) return $errCheck;

        if ($res['status'] !== 404) {
            return "Código HTTP esperado 404, recibido: {$res['status']}";
        }
        return true;
    });
}

runTest("GET / navbar no contiene elementos ni scripts de cuentas de usuario", function () {
    $res = simulateRequest('GET', '/');
    $errCheck = assertNoPhpErrors($res['body']);
    if ($errCheck !== true) return $errCheck;

    $forbiddenStrings = [
        'href="/login"',
        'href="/registro"',
        'Iniciar sesión',
        'Crear cuenta',
        'href="/mi-cuenta"',
        'Mi Cuenta',
        'pmo-logout-form',
        'js/pages/login.js',
        'js/pages/registro.js',
        'js/pages/mi-cuenta.js'
    ];

    foreach ($forbiddenStrings as $str) {
        if (strpos($res['body'], $str) !== false) {
            return "El HTML de GET / contiene texto/enlace prohibido cuando usuarios está desactivado: '{$str}'";
        }
    }
    return true;
});

// ── 7. URLs Canónicas Dinámicas y Open Graph (SEO) ──────────────────────────
runTest("GET / genera canonical y og:url con barra final (https://pmo-solutions.com/)", function () {
    $res = simulateRequest('GET', '/');
    if (!str_contains($res['body'], '<link rel="canonical" href="https://pmo-solutions.com/">')) {
        return "No se encontró canonical con barra final en portada";
    }
    if (!str_contains($res['body'], '<meta property="og:url" content="https://pmo-solutions.com/">')) {
        return "No se encontró og:url con barra final en portada";
    }
    return true;
});

runTest("GET /?utm_source=google&ref=123 excluye parámetros query de canonical y og:url", function () {
    $res = simulateRequest('GET', '/?utm_source=google&ref=123');
    if (!str_contains($res['body'], '<link rel="canonical" href="https://pmo-solutions.com/">')) {
        return "Canonical contiene parámetros query en portada";
    }
    if (!str_contains($res['body'], '<meta property="og:url" content="https://pmo-solutions.com/">')) {
        return "og:url contiene parámetros query en portada";
    }
    return true;
});

runTest("GET /evaluacion-habilidades genera canonical y og:url sin barra final", function () {
    $res = simulateRequest('GET', '/evaluacion-habilidades');
    if (!str_contains($res['body'], '<link rel="canonical" href="https://pmo-solutions.com/evaluacion-habilidades">')) {
        return "Canonical erróneo en /evaluacion-habilidades";
    }
    if (!str_contains($res['body'], '<meta property="og:url" content="https://pmo-solutions.com/evaluacion-habilidades">')) {
        return "og:url erróneo en /evaluacion-habilidades";
    }
    return true;
});

runTest("GET /terminos-y-condiciones genera canonical y og:url limpios", function () {
    $res = simulateRequest('GET', '/terminos-y-condiciones');
    if (!str_contains($res['body'], '<link rel="canonical" href="https://pmo-solutions.com/terminos-y-condiciones">')) {
        return "Canonical erróneo en /terminos-y-condiciones";
    }
    if (!str_contains($res['body'], '<meta property="og:url" content="https://pmo-solutions.com/terminos-y-condiciones">')) {
        return "og:url erróneo en /terminos-y-condiciones";
    }
    return true;
});

runTest("GET /politica-de-privacidad genera canonical y og:url en singular", function () {
    $res = simulateRequest('GET', '/politica-de-privacidad');
    if (!str_contains($res['body'], '<link rel="canonical" href="https://pmo-solutions.com/politica-de-privacidad">')) {
        return "Canonical erróneo en /politica-de-privacidad";
    }
    if (!str_contains($res['body'], '<meta property="og:url" content="https://pmo-solutions.com/politica-de-privacidad">')) {
        return "og:url erróneo en /politica-de-privacidad";
    }
    return true;
});

runTest("GET /contacto/?ref=social elimina barra final y excluye parámetros query", function () {
    $res = simulateRequest('GET', '/contacto/?ref=social');
    if (!str_contains($res['body'], '<link rel="canonical" href="https://pmo-solutions.com/contacto">')) {
        return "Canonical erróneo en /contacto/?ref=social";
    }
    if (!str_contains($res['body'], '<meta property="og:url" content="https://pmo-solutions.com/contacto">')) {
        return "og:url erróneo en /contacto/?ref=social";
    }
    return true;
});

// =============================================================================
// RESUMEN FINAL
// =============================================================================
$total = $results['passed'] + $results['failed'];
echo "\n======================================================================\n";
echo " RESUMEN DE RESULTADOS DE PRUEBAS DE RUTAS\n";
echo "======================================================================\n";
echo " Total de pruebas ejecutadas : {$total}\n";
echo " \033[32mPruebas exitosas (PASS)      : {$results['passed']}\033[0m\n";
echo " \033[31mPruebas fallidas (FAIL)      : {$results['failed']}\033[0m\n";

if ($results['failed'] === 0) {
    echo "\n\033[32m✓ TODAS LAS RUTAS Y ENDPOINTS PASARON SATISFACTORIAMENTE AL 100%.\033[0m\n\n";
    exit(0);
} else {
    echo "\n\033[31m✗ HUBO FALLOS EN LAS PRUEBAS.\033[0m\n\n";
    exit(1);
}

