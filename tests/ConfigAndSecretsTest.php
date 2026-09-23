<?php
/**
 * PMO SOLUTIONS — Suite de Pruebas: Separación de Configuración y Secretos (Tarea 4)
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

use App\Core\Database;
use App\Core\Env;
use App\Core\SmtpMailer;

echo "\n======================================================================\n";
echo " VERIFICANDO SEPARACIÓN DE CONFIGURACIÓN Y SECRETOS (TAREA 4)\n";
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

// =============================================================================
// 1. CONVERSIÓN DE TIPOS Y TIPADO ESTRICTO (BOOLEANOS Y ENTEROS)
// =============================================================================
echo "\n--- 1. Conversión de Tipos y Parsing Estricto en Env ---\n";

runTest("Env::bool() reconoce variantes truthy ('true', '1', 'yes', 'on', 1, true)", function () {
    $truthyValues = ['true', 'TRUE', 'True', '1', 'yes', 'YES', 'on', 'ON', 1, true];
    foreach ($truthyValues as $val) {
        putenv("TEST_BOOL_KEY={$val}");
        if (Env::bool('TEST_BOOL_KEY', false) !== true) {
            putenv('TEST_BOOL_KEY');
            return "Falló conversión a true para valor: " . var_export($val, true);
        }
    }
    putenv('TEST_BOOL_KEY');
    return true;
});

runTest("Env::bool() reconoce variantes falsy ('false', '0', 'no', 'off', 0, false, '')", function () {
    $falsyValues = ['false', 'FALSE', 'False', '0', 'no', 'NO', 'off', 'OFF', '', 0, false];
    foreach ($falsyValues as $val) {
        putenv("TEST_BOOL_KEY={$val}");
        if (Env::bool('TEST_BOOL_KEY', true) !== false) {
            putenv('TEST_BOOL_KEY');
            return "Falló conversión a false para valor: " . var_export($val, true);
        }
    }
    putenv('TEST_BOOL_KEY');
    return true;
});

runTest("Env::bool() retorna default seguro ante valores inválidos o no reconocidos", function () {
    $invalidValues = ['invalido', 'tal_vez', '2', 'null', 'undefined', 'abc'];
    foreach ($invalidValues as $val) {
        putenv("TEST_BOOL_KEY={$val}");
        if (Env::bool('TEST_BOOL_KEY', false) !== false) {
            putenv('TEST_BOOL_KEY');
            return "Valor inválido no retornó false por defecto: " . var_export($val, true);
        }
        if (Env::bool('TEST_BOOL_KEY', true) !== true) {
            putenv('TEST_BOOL_KEY');
            return "Valor inválido no retornó true por defecto: " . var_export($val, true);
        }
    }
    putenv('TEST_BOOL_KEY');
    return true;
});

runTest("Env::int() convierte puertos y enteros numéricos correctamente", function () {
    putenv('TEST_PORT_KEY=3306');
    if (Env::int('TEST_PORT_KEY', 0) !== 3306) {
        putenv('TEST_PORT_KEY');
        return "No convirtió '3306' a entero 3306";
    }

    putenv('TEST_PORT_KEY=587');
    if (Env::int('TEST_PORT_KEY', 0) !== 587) {
        putenv('TEST_PORT_KEY');
        return "No convirtió '587' a entero 587";
    }

    putenv('TEST_PORT_KEY=invalido');
    if (Env::int('TEST_PORT_KEY', 587) !== 587) {
        putenv('TEST_PORT_KEY');
        return "No retornó default seguro 587 ante string no numérico";
    }

    putenv('TEST_PORT_KEY');
    return true;
});

// =============================================================================
// 2. LECTURA, CONDICIONES DE CARGA Y PRIORIDAD DE VARIABLES DE ENTORNO
// =============================================================================
echo "\n--- 2. Lectura, Condiciones de Carga y Prioridad de Variables de Entorno ---\n";

runTest("PMO_APP_ENV ausente + .env existente -> NO se carga automáticamente (default production)", function () {
    Env::reset();
    putenv('PMO_APP_ENV');
    unset($_ENV['PMO_APP_ENV'], $_SERVER['PMO_APP_ENV']);

    $tmpEnv = tempnam(sys_get_temp_dir(), 'pmo_env_test_');
    file_put_contents($tmpEnv, "PMO_CUSTOM_UNLOADED_VAR=should_not_load\n");

    Env::load($tmpEnv, false);

    $val = Env::get('PMO_CUSTOM_UNLOADED_VAR', null);
    @unlink($tmpEnv);

    if ($val !== null) {
        return "El archivo .env fue cargado indebidamente cuando PMO_APP_ENV estaba ausente";
    }

    return true;
});

runTest("PMO_APP_ENV=production + .env existente -> NO se carga automáticamente", function () {
    Env::reset();
    putenv('PMO_APP_ENV=production');

    $tmpEnv = tempnam(sys_get_temp_dir(), 'pmo_env_test_');
    file_put_contents($tmpEnv, "PMO_PROD_UNLOADED_VAR=should_not_load_in_prod\n");

    Env::load($tmpEnv, false);

    $val = Env::get('PMO_PROD_UNLOADED_VAR', null);
    @unlink($tmpEnv);
    putenv('PMO_APP_ENV');

    if ($val !== null) {
        return "El archivo .env fue cargado indebidamente cuando PMO_APP_ENV=production";
    }

    return true;
});

runTest("PMO_APP_ENV=development + .env existente -> SÍ se carga correctamente", function () {
    Env::reset();
    putenv('PMO_APP_ENV=development');

    $tmpEnv = tempnam(sys_get_temp_dir(), 'pmo_env_test_');
    file_put_contents($tmpEnv, "PMO_DEV_LOADED_VAR=loaded_in_dev_ok\n");

    Env::load($tmpEnv, false);

    $val = Env::get('PMO_DEV_LOADED_VAR', null);
    @unlink($tmpEnv);
    putenv('PMO_APP_ENV');

    if ($val !== 'loaded_in_dev_ok') {
        return "El archivo .env debió cargarse cuando PMO_APP_ENV=development. Obtenido: " . var_export($val, true);
    }

    return true;
});

runTest("Variables de entorno en getenv() tienen prioridad absoluta sobre archivo .env y defaults", function () {
    Env::reset();
    $tmpEnv = tempnam(sys_get_temp_dir(), 'pmo_env_test_');
    file_put_contents($tmpEnv, "PMO_TEST_HOST=host-from-file.local\nPMO_TEST_PORT=9000\n");

    putenv('PMO_TEST_HOST=host-from-system.pmo.pe');

    Env::load($tmpEnv, true);

    $host = Env::string('PMO_TEST_HOST', 'default.local');
    $port = Env::int('PMO_TEST_PORT', 3306);

    @unlink($tmpEnv);
    putenv('PMO_TEST_HOST');

    if ($host !== 'host-from-system.pmo.pe') {
        return "getenv() no tuvo prioridad frente al archivo .env. Obtenido: '{$host}'";
    }
    if ($port !== 9000) {
        return "El archivo .env no aportó variable no definida en getenv(). Obtenido: '{$port}'";
    }

    return true;
});

runTest("Mapeo completo de las 14 variables PMO_* en app/Config/config.php", function () {
    Env::reset();
    
    // Simular inyección completa de variables
    putenv('PMO_DB_ENABLED=true');
    putenv('PMO_DB_HOST=10.0.0.5');
    putenv('PMO_DB_PORT=3307');
    putenv('PMO_DB_NAME=pmo_prod_custom_db');
    putenv('PMO_DB_USER=pmo_prod_user');
    putenv('PMO_DB_PASSWORD=secret_env_pass');
    putenv('PMO_SMTP_HOST=smtp.office365.com');
    putenv('PMO_SMTP_PORT=465');
    putenv('PMO_SMTP_ENCRYPTION=ssl');
    putenv('PMO_SMTP_USER=notificaciones@pmo.pe');
    putenv('PMO_SMTP_PASSWORD=smtp_secret_env_pass');
    putenv('PMO_APP_ENV=development');
    putenv('PMO_APP_DEBUG=true');
    putenv('PMO_SITE_URL=https://custom.pmo-solutions.com');

    $configFile = dirname(__DIR__) . '/app/Config/config.php';
    $config = require $configFile;

    // Limpiar variables de prueba
    $keys = [
        'PMO_DB_ENABLED', 'PMO_DB_HOST', 'PMO_DB_PORT', 'PMO_DB_NAME', 'PMO_DB_USER', 'PMO_DB_PASSWORD',
        'PMO_SMTP_HOST', 'PMO_SMTP_PORT', 'PMO_SMTP_ENCRYPTION', 'PMO_SMTP_USER', 'PMO_SMTP_PASSWORD',
        'PMO_APP_ENV', 'PMO_APP_DEBUG', 'PMO_SITE_URL'
    ];
    foreach ($keys as $k) {
        putenv($k);
    }

    if (($config['database']['enabled'] ?? false) !== true) return "Fallo en mapping PMO_DB_ENABLED";
    if (($config['database']['host'] ?? '') !== '10.0.0.5') return "Fallo en mapping PMO_DB_HOST";
    if (($config['database']['port'] ?? 0) !== 3307) return "Fallo en mapping PMO_DB_PORT";
    if (($config['database']['name'] ?? '') !== 'pmo_prod_custom_db') return "Fallo en mapping PMO_DB_NAME";
    if (($config['database']['user'] ?? '') !== 'pmo_prod_user') return "Fallo en mapping PMO_DB_USER";
    if (($config['database']['password'] ?? '') !== 'secret_env_pass') return "Fallo en mapping PMO_DB_PASSWORD";
    if (($config['smtp']['host'] ?? '') !== 'smtp.office365.com') return "Fallo en mapping PMO_SMTP_HOST";
    if (($config['smtp']['port'] ?? 0) !== 465) return "Fallo en mapping PMO_SMTP_PORT";
    if (($config['smtp']['encryption'] ?? '') !== 'ssl') return "Fallo en mapping PMO_SMTP_ENCRYPTION";
    if (($config['smtp']['username'] ?? '') !== 'notificaciones@pmo.pe') return "Fallo en mapping PMO_SMTP_USER";
    if (($config['smtp']['password'] ?? '') !== 'smtp_secret_env_pass') return "Fallo en mapping PMO_SMTP_PASSWORD";
    if (($config['app']['environment'] ?? '') !== 'development') return "Fallo en mapping PMO_APP_ENV";
    if (($config['app']['debug'] ?? false) !== true) return "Fallo en mapping PMO_APP_DEBUG en development";
    if (($config['app']['site_url'] ?? '') !== 'https://custom.pmo-solutions.com') return "Fallo en mapping PMO_SITE_URL";

    return true;
});

runTest("backend/config.php eliminado y app/Config/config.php es la única fuente central", function () {
    $backendConfigExists = file_exists(dirname(__DIR__) . '/backend/config.php');
    $backendExampleExists = file_exists(dirname(__DIR__) . '/backend/config.example.php');
    $appConfig = require dirname(__DIR__) . '/app/Config/config.php';

    if ($backendConfigExists || $backendExampleExists) {
        return "Archivos de configuración obsoletos en backend/ todavía existen en disco";
    }

    if (!is_array($appConfig) || empty($appConfig['app']['name'])) {
        return "app/Config/config.php no retornó una configuración válida";
    }

    return true;
});

// =============================================================================
// 3. SEGURIDAD: PMO_APP_ENV=production FUERZA DEBUG=false
// =============================================================================
echo "\n--- 3. Regla Estricta de Producción: DEBUG Forzado a False ---\n";

runTest("Si PMO_APP_ENV=production, debug permanece false aunque PMO_APP_DEBUG=true", function () {
    Env::reset();
    putenv('PMO_APP_ENV=production');
    putenv('PMO_APP_DEBUG=true');

    $configFile = dirname(__DIR__) . '/app/Config/config.php';
    $config = require $configFile;

    putenv('PMO_APP_ENV');
    putenv('PMO_APP_DEBUG');

    if (($config['app']['debug'] ?? null) !== false) {
        return "FALLO DE SEGURIDAD: En producción debug debe forzarse a false incondicionalmente";
    }

    return true;
});

// =============================================================================
// 4. DEGRADACIÓN SEGURA Y AUSENCIA DE CREDENCIALES HARDCODEADAS
// =============================================================================
echo "\n--- 4. Degradación Segura ante Credenciales Vacías / Ausentes ---\n";

runTest("Si PMO_DB_ENABLED=true pero falta nombre, usuario o password -> BD se desactiva de forma segura", function () {
    Env::reset();
    putenv('PMO_DB_ENABLED=true');
    putenv('PMO_DB_HOST=localhost');
    putenv('PMO_DB_NAME='); // vacio
    putenv('PMO_DB_USER=pmo_user');
    putenv('PMO_DB_PASSWORD=pmo_pass');

    $config = require dirname(__DIR__) . '/app/Config/config.php';
    if ($config['database']['enabled'] !== false) {
        putenv('PMO_DB_ENABLED'); putenv('PMO_DB_HOST'); putenv('PMO_DB_NAME'); putenv('PMO_DB_USER'); putenv('PMO_DB_PASSWORD');
        return "database.enabled debió ser false cuando PMO_DB_NAME está vacío";
    }

    putenv('PMO_DB_NAME=pmo_db');
    putenv('PMO_DB_USER='); // vacio
    $config2 = require dirname(__DIR__) . '/app/Config/config.php';
    if ($config2['database']['enabled'] !== false) {
        putenv('PMO_DB_ENABLED'); putenv('PMO_DB_HOST'); putenv('PMO_DB_NAME'); putenv('PMO_DB_USER'); putenv('PMO_DB_PASSWORD');
        return "database.enabled debió ser false cuando PMO_DB_USER está vacío";
    }

    putenv('PMO_DB_USER=pmo_user');
    putenv('PMO_DB_PASSWORD='); // vacio
    $config3 = require dirname(__DIR__) . '/app/Config/config.php';
    if ($config3['database']['enabled'] !== false) {
        putenv('PMO_DB_ENABLED'); putenv('PMO_DB_HOST'); putenv('PMO_DB_NAME'); putenv('PMO_DB_USER'); putenv('PMO_DB_PASSWORD');
        return "database.enabled debió ser false cuando PMO_DB_PASSWORD está vacío";
    }

    putenv('PMO_DB_ENABLED'); putenv('PMO_DB_HOST'); putenv('PMO_DB_NAME'); putenv('PMO_DB_USER'); putenv('PMO_DB_PASSWORD');
    return true;
});

runTest("Valores por defecto de contraseñas y credenciales en config.php están vacíos", function () {
    Env::reset();
    // Asegurar que no hay variables en entorno
    putenv('PMO_DB_PASSWORD=');
    putenv('PMO_DB_USER=');
    putenv('PMO_DB_NAME=');
    putenv('PMO_SMTP_PASSWORD=');
    unset($_ENV['PMO_DB_PASSWORD'], $_SERVER['PMO_DB_PASSWORD']);
    unset($_ENV['PMO_DB_USER'], $_SERVER['PMO_DB_USER']);
    unset($_ENV['PMO_DB_NAME'], $_SERVER['PMO_DB_NAME']);
    unset($_ENV['PMO_SMTP_PASSWORD'], $_SERVER['PMO_SMTP_PASSWORD']);

    $config = require dirname(__DIR__) . '/app/Config/config.php';

    if ($config['database']['password'] !== '') {
        return "database.password por defecto debe ser string vacío, recibido: " . var_export($config['database']['password'], true);
    }
    if ($config['database']['user'] !== '') {
        return "database.user por defecto debe ser string vacío";
    }
    if ($config['database']['name'] !== '') {
        return "database.name por defecto debe ser string vacío";
    }
    if ($config['smtp']['password'] !== '') {
        return "smtp.password por defecto debe ser string vacío";
    }
    if ($config['database']['enabled'] !== false) {
        return "database.enabled debe ser false cuando faltan usuario o base de datos";
    }

    return true;
});

runTest("Database::getConnection() retorna null de forma segura cuando credenciales están vacías", function () {
    Database::resetConnection();

    $pdo = Database::getConnection([
        'enabled'  => true,
        'host'     => 'localhost',
        'name'     => '', // Nombre de BD vacío
        'user'     => '', // Usuario vacío
        'password' => ''
    ]);

    if ($pdo !== null) {
        return "Database::getConnection() debió retornar null ante credenciales vacías";
    }
    return true;
});

runTest("SmtpMailer::send() degrada de forma segura retornando false si faltan credenciales (sin lanzar excepciones)", function () {
    $mailer = new SmtpMailer([
        'host'     => '',
        'username' => '',
        'password' => '',
        'auth'     => true
    ], [
        'site_url' => 'https://pmo-solutions.com'
    ]);

    // Desactivar mock temporalmente para probar lógica real de degradación
    $origMock = SmtpMailer::$mockHandler;
    SmtpMailer::$mockHandler = null;

    $sent = $mailer->send('dest@test.com', 'Dest', 'Asunto', '<p>Cuerpo</p>');

    SmtpMailer::$mockHandler = $origMock;

    if ($sent !== false) {
        return "SmtpMailer::send() debió retornar false ante configuración vacía";
    }
    if (empty($mailer->getLastError())) {
        return "SmtpMailer debe registrar lastError explicativo";
    }

    return true;
});

// =============================================================================
// 5. ESCANEO DE CÓDIGO: CERO SECRETOS O PASSWORDS REALES EN EL REPOSITORIO
// =============================================================================
echo "\n--- 5. Escaneo de Ausencia de Secretos en el Código ---\n";

runTest("Cero contraseñas hardcodeadas o placeholders en archivos PHP de aplicación y configuración", function () {
    $root = dirname(__DIR__);
    $dirsToScan = ['app', 'backend', 'views'];
    
    $forbiddenPatterns = [
        '/TU_PASSWORD_DB_AQUI/i',
        '/TU_PASSWORD_SMTP_AQUI/i',
        '/\'password\'\s*=>\s*\'[^\'\s]{4,}\'/i',
    ];

    foreach ($dirsToScan as $dirName) {
        $dirPath = $root . '/' . $dirName;
        if (!is_dir($dirPath)) continue;

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getRealPath());
            if ($content === false) continue;

            foreach ($forbiddenPatterns as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    return "Se encontró patrón prohibido '{$matches[0]}' en el archivo: {$file->getPathname()}";
                }
            }
        }
    }

    // Escanear index.php
    $indexContent = (string)file_get_contents($root . '/index.php');
    foreach ($forbiddenPatterns as $pattern) {
        if (preg_match($pattern, $indexContent, $matches)) {
            return "Se encontró patrón prohibido en index.php: '{$matches[0]}'";
        }
    }

    return true;
});

runTest("El archivo .env.example existe y no contiene secretos reales", function () {
    $exampleFile = dirname(__DIR__) . '/.env.example';
    if (!file_exists($exampleFile)) {
        return "No se encontró el archivo .env.example en la raíz del proyecto";
    }

    $content = (string)file_get_contents($exampleFile);
    if (!str_contains($content, 'PMO_DB_ENABLED=') || !str_contains($content, 'PMO_SMTP_HOST=')) {
        return ".env.example carece de las variables requeridas";
    }

    // Verificar que los passwords estén vacíos
    if (preg_match('/PMO_DB_PASSWORD=.+/', $content) || preg_match('/PMO_SMTP_PASSWORD=.+/', $content)) {
        return ".env.example contiene contraseñas no vacías";
    }

    return true;
});

// =============================================================================
// 6. PROTECCIÓN EN .HTACCESS CONTRA ARCHIVOS SENSIBLES
// =============================================================================
echo "\n--- 6. Verificación de Reglas de Bloqueo en .htaccess ---\n";

runTest(".htaccess contiene reglas para bloquear .env, backups, migraciones y configs", function () {
    $htaccess = dirname(__DIR__) . '/.htaccess';
    if (!file_exists($htaccess)) {
        return "No se encontró .htaccess";
    }

    $content = (string)file_get_contents($htaccess);

    // Probar regex de FilesMatch contra nombres de archivo sensibles
    if (!preg_match('/<FilesMatch\s+"([^"]+)"/i', $content, $matches)) {
        return "No se encontró directiva <FilesMatch> en .htaccess";
    }

    $regex = '#' . $matches[1] . '#i';

    $sensitiveFiles = [
        '.env',
        '.env.example',
        '.env.local',
        '.env.production',
        '.env.backup',
        'config.php',
        'backend/config.php',
        'database_backup.sql',
        'backup.dump',
        'database.bak',
        'app.log',
        'sensitive.json'
    ];

    foreach ($sensitiveFiles as $testFile) {
        $basename = basename($testFile);
        if (!preg_match($regex, $basename)) {
            return "La regla FilesMatch en .htaccess no protege el archivo sensible: '{$testFile}' (basename: '{$basename}')";
        }
    }

    return true;
});

// =============================================================================
// RESUMEN FINAL
// =============================================================================
$total = $results['passed'] + $results['failed'] + $results['skipped'];
echo "\n======================================================================\n";
echo " RESUMEN DE PRUEBAS DE CONFIGURACIÓN Y SECRETOS\n";
echo "======================================================================\n";
echo " Total de pruebas ejecutadas : {$total}\n";
echo " \033[32mPruebas exitosas (PASS)      : {$results['passed']}\033[0m\n";
echo " \033[33mPruebas omitidas (SKIP)      : {$results['skipped']}\033[0m\n";
echo " \033[31mPruebas fallidas (FAIL)      : {$results['failed']}\033[0m\n";

if ($results['failed'] === 0) {
    echo "\n\033[32m✓ TODAS LAS PRUEBAS DE LA TAREA 4 PASARON SATISFACTORIAMENTE AL 100%.\033[0m\n\n";
    exit(0);
} else {
    echo "\n\033[31m✗ HUBO FALLOS EN LAS PRUEBAS.\033[0m\n\n";
    exit(1);
}
