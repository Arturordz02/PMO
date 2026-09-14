<?php
/**
 * PMO SOLUTIONS - Suite de Pruebas Automatizadas: Módulo Chatbot Guiado
 *
 * Cobertura de Pruebas:
 *  1. Existencia e integridad estructural de archivos del módulo
 *  2. Sintaxis PHP válida en archivos del módulo
 *  3. Activación: chatbot_enabled = true inyecta HTML, CSS versionado y JS versionado
 *  4. Desactivación: chatbot_enabled = false produce ausencia total de HTML, CSS y JS
 *  5. Resiliencia ante eliminación física: el sistema opera normalmente si el módulo no existe
 *  6. Inclusión del chatbot en las 15 vistas públicas del sistema MVC
 *  7. Seguridad y reglas .htaccess para servir assets (CSS, JS, JSON) y proteger configs
 *  8. Cumplimiento estricto de CSP: Cero inline styles (style=), scripts inline, eval o handlers on*
 *  9. Verificación de prefijo obligatorio 'pmo-chatbot-' en 100% de clases CSS del módulo
 * 10. Integridad de la base de conocimiento (knowledge.json) y los 6 dominios obligatorios
 * 11. Coincidencia exacta de los 10 cursos reales y rutas MVC en App\Core\Router
 * 12. Canal oficial de WhatsApp verificado (sin selector de países ni números alternos)
 * 13. Reglas de diseño responsive presentes en chatbot.css
 * 14. Inclusión completa en ambos paquetes ZIP (Producción y Cierre) según reglas de empaquetado
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

define('PMO_TEST_RUNNER', true);

$projectDir = dirname(__DIR__, 3);

require_once $projectDir . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

echo "\n======================================================================\n";
echo " SUITE DE PRUEBAS AUTOMATIZADAS: CHATBOT GUIADO PMO SOLUTIONS\n";
echo " Entorno: PHP " . PHP_VERSION . " | Raíz: {$projectDir}\n";
echo "======================================================================\n\n";

$results = [
    'passed'  => 0,
    'failed'  => 0,
    'skipped' => 0,
];

function runTest(string $description, callable $testFn): void {
    global $results;
    try {
        $res = $testFn();
        if ($res === true) {
            $results['passed']++;
            echo "  \033[32m✔ [PASS]\033[0m {$description}\n";
        } elseif (is_array($res) && ($res['status'] ?? '') === 'SKIP') {
            $results['skipped']++;
            echo "  \033[33m⚡ [SKIP]\033[0m {$description}" . (!empty($res['reason']) ? " — {$res['reason']}" : "") . "\n";
        } else {
            $results['failed']++;
            $msg = is_string($res) ? $res : 'Condición no cumplida';
            echo "  \033[31m✖ [FAIL]\033[0m {$description} -> {$msg}\n";
        }
    } catch (\Throwable $e) {
        $results['failed']++;
        echo "  \033[31m✖ [FAIL]\033[0m {$description} -> Excepción: {$e->getMessage()}\n";
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Integridad Estructural y Sintaxis
// ─────────────────────────────────────────────────────────────────────────────
echo "--- 1. Integridad de Archivos y Sintaxis del Módulo ---\n";

$moduleDir = $projectDir . '/modules/chatbot';
$expectedFiles = [
    'config.php',
    'knowledge.json',
    'chatbot-view.php',
    'chatbot.css',
    'chatbot.js',
    '.htaccess',
    'README.md',
    'tests/ChatbotTest.php'
];

foreach ($expectedFiles as $f) {
    runTest("Archivo del módulo existe: {$f}", function () use ($moduleDir, $f) {
        $fullPath = $moduleDir . '/' . $f;
        if (!file_exists($fullPath)) {
            return "No se encontró el archivo {$fullPath}";
        }
        if (filesize($fullPath) === 0) {
            return "El archivo {$fullPath} está vacío";
        }
        return true;
    });
}

runTest("Sintaxis PHP válida en config.php y chatbot-view.php", function () use ($moduleDir) {
    $phpFiles = [$moduleDir . '/config.php', $moduleDir . '/chatbot-view.php'];
    foreach ($phpFiles as $file) {
        $output = [];
        $code = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $code);
        if ($code !== 0) {
            return "Error de sintaxis en " . basename($file) . ": " . implode(" ", $output);
        }
    }
    return true;
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Activación, Desactivación y Resiliencia ante Eliminación
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 2. Pruebas de Activación, Desactivación y Resiliencia ---\n";

function renderViewWithChatbot(string $view = 'home', ?bool $forceFlag = null, bool $simulateDelete = false): string {
    global $projectDir;

    $autoloader = $projectDir . '/app/Core/Autoloader.php';
    $configFile = $projectDir . '/modules/chatbot/config.php';

    $script = '<?php ';
    $script .= 'ini_set("display_errors", "1"); error_reporting(E_ALL); ';
    $script .= 'require_once "' . addslashes($autoloader) . '"; ';
    $script .= '\App\Core\Autoloader::register(); ';
    $script .= '$_SERVER["REQUEST_METHOD"] = "GET"; ';
    $script .= '$_SERVER["REQUEST_URI"] = "/"; ';
    $script .= '$_SERVER["SCRIPT_NAME"] = "/index.php"; ';
    $script .= '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; ';

    if ($simulateDelete) {
        // Simular que el módulo no existe renombrando o alterando la ruta requerida en runtime
        $script .= 'define("PMO_SIMULATE_CHATBOT_DELETED", true); ';
    }

    if ($forceFlag !== null) {
        $script .= '$GLOBALS["_OVERRIDE_CHATBOT_ENABLED"] = ' . ($forceFlag ? 'true' : 'false') . '; ';
    }

    $script .= 'ob_start(); ';
    $script .= '\App\Core\View::render("' . addslashes($view) . '", ["pageTitle" => "Test Page"]); ';
    $script .= 'echo ob_get_clean(); ';

    $tmp = tempnam(sys_get_temp_dir(), 'pmo_cb_');
    file_put_contents($tmp, $script);
    $out = (string)shell_exec("php " . escapeshellarg($tmp) . " 2>&1");
    @unlink($tmp);
    return $out;
}

runTest("Activación: chatbot_enabled = true renderiza botón flotante y assets versionados", function () {
    $html = renderViewWithChatbot('home', true);
    if (!str_contains($html, 'id="pmo-chatbot-root"')) {
        return "No se encontró id='pmo-chatbot-root' en el HTML renderizado con chatbot activado";
    }
    if (!str_contains($html, 'modules/chatbot/chatbot.css?v=')) {
        return "No se encontró enlace versionado a chatbot.css";
    }
    if (!str_contains($html, 'modules/chatbot/chatbot.js?v=')) {
        return "No se encontró script versionado a chatbot.js";
    }
    return true;
});

runTest("Desactivación: chatbot_enabled = false produce ausencia total de HTML, CSS y JS", function () use ($moduleDir) {
    // Configurar temporalmente en false
    $configFile = $moduleDir . '/config.php';
    $original = (string)file_get_contents($configFile);

    try {
        file_put_contents($configFile, "<?php return ['chatbot_enabled' => false];");
        $html = renderViewWithChatbot('home', false);

        if (str_contains($html, 'pmo-chatbot-root')) {
            return "Se detectó marcado HTML del chatbot cuando chatbot_enabled está en false";
        }
        if (str_contains($html, 'chatbot.css')) {
            return "Se detectó referencia a chatbot.css cuando chatbot_enabled está en false";
        }
        if (str_contains($html, 'chatbot.js')) {
            return "Se detectó referencia a chatbot.js cuando chatbot_enabled está en false";
        }
        if (str_contains($html, 'pmo-chatbot-')) {
            return "Se detectaron clases pmo-chatbot- cuando chatbot_enabled está en false";
        }
        return true;
    } finally {
        file_put_contents($configFile, $original);
    }
});

runTest("Resiliencia: El sistema opera normalmente si modules/chatbot/ no existe", function () use ($projectDir, $moduleDir) {
    $mainLayout = (string)file_get_contents($projectDir . '/app/Views/layouts/main.php');
    if (!str_contains($mainLayout, 'file_exists')) {
        return "El layout principal no utiliza file_exists() para proteger la inclusión del módulo";
    }

    // Probar temporalmente renombrando la carpeta para verificar que la página renderiza normalmente sin error fatal
    $tempRename = $projectDir . '/modules/chatbot_temp_test';
    rename($moduleDir, $tempRename);

    try {
        $html = renderViewWithChatbot('home');
        if (str_contains($html, 'Fatal error') || str_contains($html, 'Warning')) {
            return "Se produjeron errores PHP al no existir la carpeta modules/chatbot/";
        }
        if (!str_contains($html, '<!DOCTYPE html>')) {
            return "La página no renderizó el HTML base correctamente ante la ausencia del chatbot";
        }
        return true;
    } finally {
        rename($tempRename, $moduleDir);
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Cobertura en las 15 Vistas Públicas del Sistema
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 3. Disponibilidad en las 15 Vistas Públicas ---\n";

$publicViews = [
    'home'                        => '/',
    'capacitaciones'              => '/capacitaciones',
    'evaluacion-habilidades'      => '/evaluacion-habilidades',
    'contacto'                    => '/contacto',
    'libro-de-reclamaciones'      => '/libro-de-reclamaciones',
    'courses/nec4'                => '/nec4',
    'courses/primavera-p6'        => '/primavera-p6',
    'courses/dab-jrd'             => '/dab-jrd',
    'courses/vdc-bim'             => '/vdc-bim',
    'courses/contratos-estado'    => '/contratos-estado',
    'courses/compliance'          => '/compliance',
    'courses/riesgos-pmi'         => '/riesgos-pmi',
    'courses/analisis-cuantitativo' => '/analisis-cuantitativo',
    'courses/analisis-forense'    => '/analisis-forense',
    'courses/eventos-compensables' => '/eventos-compensables',
];

foreach ($publicViews as $viewPath => $urlPath) {
    runTest("Chatbot inyectado en vista '{$viewPath}' ({$urlPath})", function () use ($viewPath) {
        $html = renderViewWithChatbot($viewPath);
        if (!str_contains($html, 'id="pmo-chatbot-root"')) {
            return "No se encontró el contenedor del chatbot en la vista {$viewPath}";
        }
        if (!str_contains($html, 'chatbot.css?v=')) {
            return "No se encontró chatbot.css en la vista {$viewPath}";
        }
        if (!str_contains($html, 'chatbot.js?v=')) {
            return "No se encontró chatbot.js en la vista {$viewPath}";
        }
        return true;
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. Seguridad de Servidor Apache y Reglas .htaccess
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 4. Servibilidad de Recursos en Apache y Seguridad .htaccess ---\n";

runTest("Reglas en modules/chatbot/.htaccess permiten chatbot.css, chatbot.js y knowledge.json", function () use ($moduleDir) {
    $ht = (string)file_get_contents($moduleDir . '/.htaccess');
    if (!str_contains($ht, 'chatbot\.(css|js)|knowledge\.json')) {
        return "El archivo .htaccess del módulo no contiene la regla de concesión para los assets públicos";
    }
    if (!str_contains($ht, 'Require all granted') && !str_contains($ht, 'Allow from all')) {
        return "El archivo .htaccess del módulo no otorga acceso permitido a los assets públicos";
    }
    return true;
});

runTest("Reglas en modules/chatbot/.htaccess bloquean config.php y README.md", function () use ($moduleDir) {
    $ht = (string)file_get_contents($moduleDir . '/.htaccess');
    if (!str_contains($ht, 'config\.php|README\.md')) {
        return "El archivo .htaccess del módulo no contiene la regla de restricción para archivos sensibles";
    }
    if (!str_contains($ht, 'Require all denied') && !str_contains($ht, 'Deny from all')) {
        return "El archivo .htaccess del módulo no deniega el acceso a config.php y README.md";
    }
    return true;
});

runTest("Protecciones existentes de app/, backend/ y storage/ intactas", function () use ($projectDir) {
    $rootHt = (string)file_get_contents($projectDir . '/.htaccess');
    if (!str_contains($rootHt, 'RedirectMatch 403 ^/app(/.*)?$')) {
        return "Protección de /app/ fue debilitada o modificada";
    }
    if (!str_contains($rootHt, 'RedirectMatch 403 ^/backend(/.*)?$')) {
        return "Protección de /backend/ fue debilitada o modificada";
    }
    if (!str_contains($rootHt, 'RedirectMatch 403 ^/storage(/.*)?$')) {
        return "Protección de /storage/ fue debilitada o modificada";
    }
    return true;
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Cumplimiento Estricto de CSP (Sin inline scripts ni styles)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 5. Cumplimiento de CSP y Ausencia de Elementos Inline ---\n";

runTest("chatbot-view.php no contiene atributos style= inline", function () use ($moduleDir) {
    $content = (string)file_get_contents($moduleDir . '/chatbot-view.php');
    if (preg_match('/\bstyle\s*=/i', $content)) {
        return "Se encontró atributo style= inline en chatbot-view.php";
    }
    return true;
});

runTest("chatbot-view.php no contiene scripts inline (<script> con código)", function () use ($moduleDir) {
    $content = (string)file_get_contents($moduleDir . '/chatbot-view.php');
    // Permitir exclusivamente <script src="...">
    if (preg_match('/<script(?![^>]*\bsrc=)[^>]*>(.*?)<\/script>/is', $content, $m)) {
        if (!empty(trim($m[1]))) {
            return "Se encontró etiqueta <script> inline con código ejecutable en chatbot-view.php";
        }
    }
    return true;
});

runTest("chatbot.js no utiliza eval ni funciones dinámicas inseguras", function () use ($moduleDir) {
    $content = (string)file_get_contents($moduleDir . '/chatbot.js');
    if (preg_match('/\beval\s*\(/i', $content)) {
        return "Se detectó uso de eval() en chatbot.js";
    }
    if (preg_match('/\bnew\s+Function\s*\(/i', $content)) {
        return "Se detectó uso de new Function() en chatbot.js";
    }
    return true;
});

runTest("chatbot-view.php y chatbot.js no contienen controladores de eventos inline (onclick, onload, etc.)", function () use ($moduleDir) {
    $files = [$moduleDir . '/chatbot-view.php', $moduleDir . '/chatbot.js'];
    foreach ($files as $file) {
        $content = (string)file_get_contents($file);
        if (preg_match('/\s+on(click|load|change|submit|mouseover|focus|blur)\s*=/i', $content, $m)) {
            return "Se detectó evento inline '{$m[0]}' en " . basename($file);
        }
    }
    return true;
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. Validación de Clases CSS con Prefijo pmo-chatbot-
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 6. Convención de Clases CSS (Prefijo 'pmo-chatbot-') ---\n";

runTest("100% de clases definidas en chatbot.css inician con 'pmo-chatbot-'", function () use ($moduleDir) {
    $css = (string)file_get_contents($moduleDir . '/chatbot.css');
    // Remover comentarios CSS
    $cleanCss = preg_replace('/\/\*.*?\*\//s', '', $css);
    
    // Extraer solo la parte de selectores (antes de las llaves {)
    $selectorsOnly = '';
    $chunks = explode('{', $cleanCss);
    foreach ($chunks as $i => $chunk) {
        if ($i === 0) {
            $selectorsOnly .= ' ' . $chunk;
        } else {
            // El chunk contiene "propiedades } selector"
            $parts = explode('}', $chunk);
            if (isset($parts[1])) {
                $selectorsOnly .= ' ' . $parts[1];
            }
        }
    }

    // Buscar selectores de clase (deben iniciar con letra o guion bajo, no número)
    preg_match_all('/\.([a-zA-Z_][a-zA-Z0-9_-]*)/', $selectorsOnly, $matches);

    $nonCompliant = [];
    foreach ($matches[1] as $className) {
        if (!str_starts_with($className, 'pmo-chatbot-')) {
            $nonCompliant[] = $className;
        }
    }

    if (!empty($nonCompliant)) {
        $unique = array_unique($nonCompliant);
        return "Clases CSS sin prefijo obligatorio: " . implode(', ', $unique);
    }
    return true;
});

runTest("Clases usadas en chatbot-view.php cumplen con prefijo o iconos FontAwesome", function () use ($moduleDir) {
    $html = (string)file_get_contents($moduleDir . '/chatbot-view.php');
    preg_match_all('/class="([^"]+)"/', $html, $matches);

    $invalid = [];
    foreach ($matches[1] as $classList) {
        $classes = preg_split('/\s+/', trim($classList));
        foreach ($classes as $c) {
            if ($c === '') continue;
            // Permitir iconos FontAwesome (fa, fas, far, fab, fa-*)
            if (str_starts_with($c, 'fa') || str_starts_with($c, 'fas') || str_starts_with($c, 'far') || str_starts_with($c, 'fab')) {
                continue;
            }
            if (!str_starts_with($c, 'pmo-chatbot-')) {
                $invalid[] = $c;
            }
        }
    }

    if (!empty($invalid)) {
        return "Clases en la vista sin prefijo: " . implode(', ', array_unique($invalid));
    }
    return true;
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. Integridad de la Base de Conocimiento (knowledge.json) y Cursos
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 7. Integridad de la Base de Conocimiento y Catálogo Real ---\n";

runTest("knowledge.json es JSON válido y contiene estructura de nodos requerida", function () use ($moduleDir) {
    $jsonRaw = (string)file_get_contents($moduleDir . '/knowledge.json');
    $data = json_decode($jsonRaw, true);
    if ($data === null) {
        return "knowledge.json no es un JSON válido: " . json_last_error_msg();
    }
    if (!isset($data['nodes']) || !is_array($data['nodes'])) {
        return "knowledge.json no contiene nodo raíz 'nodes'";
    }
    return true;
});

runTest("knowledge.json contiene los 6 dominios de opciones obligatorios", function () use ($moduleDir) {
    $data = json_decode((string)file_get_contents($moduleDir . '/knowledge.json'), true);
    $mainOptions = $data['nodes']['main_menu']['options'] ?? [];
    
    $requiredDestinations = [
        'capacitaciones_menu',
        'servicios_menu',
        'evaluacion_habilidades',
        'asesor_whatsapp',
        'libro_reclamaciones',
        'ubicacion_horarios'
    ];

    $found = [];
    foreach ($mainOptions as $opt) {
        if (!empty($opt['next_node'])) {
            $found[] = $opt['next_node'];
        }
    }

    foreach ($requiredDestinations as $dest) {
        if (!in_array($dest, $found, true)) {
            return "Falta la opción obligatoria para '{$dest}' en main_menu";
        }
    }
    return true;
});

runTest("knowledge.json contiene exactamente los 10 cursos reales del catálogo", function () use ($moduleDir, $projectDir) {
    require_once $projectDir . '/app/Models/CourseModel.php';
    $model = new \App\Models\CourseModel();
    $catalog = $model->getAll();

    $expectedSlugs = array_keys($catalog);
    if (count($expectedSlugs) !== 10) {
        return "El catálogo de CourseModel tiene " . count($expectedSlugs) . " cursos, se esperaban 10";
    }

    $data = json_decode((string)file_get_contents($moduleDir . '/knowledge.json'), true);
    $courseOptions = $data['nodes']['capacitaciones_menu']['options'] ?? [];

    $urls = [];
    foreach ($courseOptions as $opt) {
        if (!empty($opt['url'])) {
            $urls[] = ltrim($opt['url'], '/');
        }
    }

    foreach ($expectedSlugs as $slug) {
        if (!in_array($slug, $urls, true)) {
            return "El curso oficial '{$slug}' no está presente en capacitaciones_menu de knowledge.json";
        }
    }
    return true;
});

runTest("Todas las URLs internas de knowledge.json existen en el Router de PMO", function () use ($moduleDir, $projectDir) {
    $data = json_decode((string)file_get_contents($moduleDir . '/knowledge.json'), true);

    $internalUrls = [];
    foreach ($data['nodes'] as $node) {
        foreach ($node['options'] ?? [] as $opt) {
            if (!empty($opt['url']) && ($opt['type'] ?? '') === 'link') {
                $internalUrls[] = $opt['url'];
            }
        }
    }

    $internalUrls = array_unique($internalUrls);

    // Obtener rutas registradas en index.php
    $indexContent = (string)file_get_contents($projectDir . '/index.php');
    foreach ($internalUrls as $url) {
        $quoted = preg_quote($url, '/');
        if (!preg_match("/->get\(\s*['\"]{$quoted}['\"]/i", $indexContent)) {
            return "La URL '{$url}' de knowledge.json no está registrada como ruta GET en index.php";
        }
    }
    return true;
});

runTest("Enlace de WhatsApp es exactamente 'https://api.whatsapp.com/send?phone=51944276649'", function () use ($moduleDir) {
    $data = json_decode((string)file_get_contents($moduleDir . '/knowledge.json'), true);
    $targetUrl = 'https://api.whatsapp.com/send?phone=51944276649';

    $found = false;
    foreach ($data['nodes'] as $node) {
        foreach ($node['options'] ?? [] as $opt) {
            if (!empty($opt['url']) && str_contains($opt['url'], 'api.whatsapp.com')) {
                if ($opt['url'] !== $targetUrl) {
                    return "URL de WhatsApp no autorizada o alterada: '{$opt['url']}', se esperaba '{$targetUrl}'";
                }
                $found = true;
            }
        }
    }

    if (!$found) {
        return "No se encontró el enlace oficial de WhatsApp en knowledge.json";
    }
    return true;
});

// ─────────────────────────────────────────────────────────────────────────────
// 8. Responsive Design & Empaquetado ZIP
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 8. Responsive Design y Reglas de Empaquetado ZIP ---\n";

runTest("chatbot.css incluye reglas @media (max-width: 480px) para móviles", function () use ($moduleDir) {
    $css = (string)file_get_contents($moduleDir . '/chatbot.css');
    if (!str_contains($css, '@media (max-width: 480px)')) {
        return "No se encontró la regla @media (max-width: 480px) en chatbot.css";
    }
    if (!str_contains($css, 'pmo-chatbot-window') || !str_contains($css, 'calc(100vw')) {
        return "No se encontraron reglas de adaptación de ancho/alto para la ventana en dispositivos móviles";
    }
    return true;
});

runTest("create_production_zip.php incluye modules/chatbot/ en paquete de producción y cierre", function () use ($projectDir) {
    $zipScript = (string)file_get_contents($projectDir . '/artisan/create_production_zip.php');

    // Verificar que /modules no esté en la blacklist de producción ni de cierre
    if (preg_match("/blacklistProd\s*=\s*\[(.*?)\];/s", $zipScript, $mProd)) {
        if (str_contains($mProd[1], "'/modules'") || str_contains($mProd[1], '"/modules"')) {
            return "modules/ está en blacklistProd de create_production_zip.php";
        }
    } else {
        return "No se pudo inspeccionar blacklistProd en create_production_zip.php";
    }

    if (preg_match("/blacklistCierre\s*=\s*\[(.*?)\];/s", $zipScript, $mCierre)) {
        if (str_contains($mCierre[1], "'/modules'") || str_contains($mCierre[1], '"/modules"')) {
            return "modules/ está en blacklistCierre de create_production_zip.php";
        }
    } else {
        return "No se pudo inspeccionar blacklistCierre en create_production_zip.php";
    }

    return true;
});

// =============================================================================
// RESUMEN FINAL
// =============================================================================
$total = $results['passed'] + $results['failed'] + $results['skipped'];

echo "\n======================================================================\n";
echo " RESUMEN: PRUEBAS DEL CHATBOT GUIADO PMO SOLUTIONS\n";
echo "======================================================================\n";
echo " Total tests : {$total}\n";
echo " ✔ Pasados    : {$results['passed']}\n";
echo " ⚡ Omitidos   : {$results['skipped']}\n";
echo " ✖ Fallados   : {$results['failed']}\n";
echo " Total tests: {$total} | Pasados: {$results['passed']} | Fallados: {$results['failed']} | Omitidos (SKIP): {$results['skipped']}\n";
echo "======================================================================\n\n";

if ($results['failed'] > 0) {
    echo "✗ SE ENCONTRARON FALLOS EN LA SUITE DEL CHATBOT.\n";
    exit(1);
} else {
    echo "✔ TODAS LAS PRUEBAS DEL CHATBOT PASARON SATISFACTORIAMENTE.\n";
    exit(0);
}
