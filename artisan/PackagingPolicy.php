<?php
/**
 * PMO SOLUTIONS — Política Centralizada de Empaquetado y Auditoría de Paquetes
 *
 * Fuente única de verdad para las reglas de inclusión, exclusión y validación
 * de los paquetes PMO-Solutions-Produccion.zip y PMO-Solutions-Codigo-Fuente.zip.
 */

declare(strict_types=1);

namespace Artisan;

use ZipArchive;

class PackagingPolicy {

    /**
     * Lista permitida exacta de archivos de backend/ para producción.
     * Ningún otro archivo dentro de backend/ está permitido en el ZIP de producción.
     */
    public const BACKEND_PROD_ALLOWLIST = [
        'backend/.htaccess',
        'backend/schema.sql',
        'backend/schema_v2.sql',
        'backend/migrations/001_add_report_access_token_hash.sql',
        'backend/migrations/001_rollback.sql',
        'backend/migrations/002_add_idempotency_and_outbox.sql',
        'backend/migrations/002_rollback.sql',
    ];

    /**
     * Archivos del subsistema de usuarios definitivamente eliminado.
     * Prohibidos tanto en el paquete de producción como en el de cierre.
     */
    public const FORBIDDEN_USER_FILES = [
        'app/Controllers/UserAuthController.php',
        'app/Models/UserModel.php',
        'app/Core/UserSession.php',
        'app/Views/pages/registro.php',
        'app/Views/pages/login.php',
        'app/Views/pages/mi-cuenta.php',
        'js/pages/registro.js',
        'js/pages/login.js',
        'js/pages/mi-cuenta.js',
        'backend/migrations/003_add_usuarios_table.sql',
        'backend/migrations/003_rollback.sql',
        'tests/UserRegistrationTest.php',
        'tests/UserLoginTest.php',
        'tests/UserModelTest.php',
        'tests/UserAccountTest.php',
        'tests/UserNavbarTest.php',
        'tests/UserAccountsFeatureFlagTest.php',
    ];

    /**
     * Archivos indispensables que DEBEN estar presentes en el paquete de producción.
     */
    public const MANDATORY_PROD_FILES = [
        // Backend permitido
        'backend/schema.sql',
        'backend/schema_v2.sql',
        'backend/migrations/001_add_report_access_token_hash.sql',
        'backend/migrations/001_rollback.sql',
        'backend/migrations/002_add_idempotency_and_outbox.sql',
        'backend/migrations/002_rollback.sql',
        'backend/.htaccess',
        // Configuración
        '.env.example',
        // Chatbot funcional
        'modules/chatbot/chatbot-view.php',
        'modules/chatbot/chatbot.css',
        'modules/chatbot/chatbot.js',
        'modules/chatbot/config.php',
        'modules/chatbot/knowledge.json',
        'modules/chatbot/.htaccess',
        // Núcleo MVC
        'app/Config/config.php',
        'app/Core/Autoloader.php',
        'app/Core/Router.php',
        'app/Core/Security.php',
        'app/Core/Controller.php',
        'app/Core/Model.php',
        'app/Core/View.php',
        'app/Core/Csrf.php',
        'app/Core/Database.php',
        'app/Core/Env.php',
        // Autenticación administrativa interna (permanece deshabilitada)
        'app/Controllers/AuthController.php',
        // Vistas canónicas
        'app/Views/layouts/main.php',
        'app/Views/pages/home.php',
        'app/Views/pages/contacto.php',
        'app/Views/pages/capacitaciones.php',
        'app/Views/pages/libro-de-reclamaciones.php',
        'app/Views/pages/evaluacion-habilidades.php',
        // Tareas CLI
        'artisan/process-outbox.php',
        'artisan/archive-logs.php',
        // Assets
        'css/styles.css',
        'js/main.js',
        'js/sw.js',
        'js/vendor/chart.umd.min.js',
        'js/vendor/jspdf.umd.min.js',
        'js/vendor/LICENSE-chartjs.txt',
        'js/vendor/LICENSE-jspdf.txt',
        // Storage
        'storage/.htaccess',
        'storage/logs/.gitkeep',
        'storage/cache/.gitkeep',
        'storage/outbox/.gitkeep',
        'storage/outbox/.htaccess',
        // Raíz
        '.htaccess',
        'index.php',
        'robots.txt',
        'sitemap.xml',
    ];

    /**
     * Archivos indispensables que DEBEN estar presentes en el paquete de Código Fuente / Cierre.
     */
    public const MANDATORY_CLOSURE_FILES = [
        'modules/chatbot/tests/ChatbotTest.php',
        'tests/ProjectCleanupTest.php',
        'tests/QualityGateTest.php',
        'tests/ProductionAuditTest.php',
        'tests/browser_test.js',
        'artisan/create_production_zip.php',
        'artisan/quality-gate.php',
        'artisan/pre-deploy-check.php',
        'artisan/PackagingPolicy.php',
        'backend/schema_v2.sql',
        'README.md',
        'DEPLOYMENT.md',
        'modules/chatbot/README.md',
        '.env.example',
        '.gitignore',
    ];

    /**
     * Determina si un archivo o directorio debe ser excluido del paquete de producción.
     *
     * @param string $relativePath Ruta normalizada relativa a la raíz del proyecto (e.g. '/app/Core/Env.php' o 'app/Core/Env.php')
     * @param bool $isDir Indica si la ruta evaluada es un directorio
     * @return bool True si debe excluirse, False si debe incluirse
     */
    public static function shouldExcludeFromProduction(string $relativePath, bool $isDir = false): bool {
        $clean = ltrim(str_replace('\\', '/', $relativePath), '/');

        // 0. Archivos del subsistema de usuarios definitivamente eliminado
        if (in_array($clean, self::FORBIDDEN_USER_FILES, true)) {
            return true;
        }

        // 1. backend/: Solo permitir los elementos explícitos de BACKEND_PROD_ALLOWLIST
        if (str_starts_with($clean, 'backend')) {
            if ($clean === 'backend' || $clean === 'backend/migrations') {
                return false; // permitir directorios contenedores
            }
            if ($isDir) {
                return true; // no permitir ningún otro subdirectorio en backend
            }
            return !in_array($clean, self::BACKEND_PROD_ALLOWLIST, true);
        }

        // 2. Secretos y variables de entorno: .env y variantes estrictamente excluidas, permitir .env.example
        if ($clean === '.env' || (str_starts_with($clean, '.env') && $clean !== '.env.example')) {
            return true;
        }

        // 3. Tests y pruebas internas: excluir tests/ raíz, subcarpetas tests/ y archivos *Test.php
        if (
            str_starts_with($clean, 'tests/') ||
            $clean === 'tests' ||
            str_contains($clean, '/tests/') ||
            str_ends_with($clean, '/tests') ||
            str_ends_with($clean, 'Test.php')
        ) {
            return true;
        }

        // 4. Herramientas de build y desarrollo en artisan/ (solo se conservan process-outbox y archive-logs)
        if (
            $clean === 'artisan/create_production_zip.php' ||
            $clean === 'artisan/quality-gate.php' ||
            $clean === 'artisan/pre-deploy-check.php' ||
            $clean === 'artisan/PackagingPolicy.php'
        ) {
            return true;
        }

        // 5. VCS, IDE y metadatos de desarrollo
        $segments = explode('/', $clean);
        foreach ($segments as $seg) {
            if (in_array($seg, ['.git', '.gitignore', '.vscode', '.idea', '.agent', '.github'], true)) {
                return true;
            }
        }

        // 6. Archivos heredados ya retirados
        if ($clean === 'frontend' || str_starts_with($clean, 'frontend/')) {
            return true;
        }

        // 7. Documentación markdown (.md) excluida en producción
        if (str_ends_with(strtolower($clean), '.md')) {
            return true;
        }

        // 8. Empaquetados, backups y archivos temporales
        $lower = strtolower($clean);
        $base = basename($clean);
        if (
            str_ends_with($lower, '.zip') ||
            str_ends_with($lower, '.rar') ||
            str_ends_with($lower, '.tar') ||
            str_ends_with($lower, '.gz') ||
            str_ends_with($lower, '.7z') ||
            str_ends_with($lower, '.tmp') ||
            str_ends_with($lower, '.bak') ||
            str_ends_with($lower, '.swp') ||
            str_ends_with($lower, '~') ||
            $base === '.ds_store' ||
            $base === 'thumbs.db'
        ) {
            return true;
        }

        // 9. Storage: Solo preservar .gitkeep y .htaccess, excluir reportes y datos runtime
        if (str_starts_with($clean, 'storage/')) {
            if ($clean === 'storage/reports' || str_starts_with($clean, 'storage/reports/')) {
                return true;
            }
            if ($base !== '.gitkeep' && $base !== '.htaccess') {
                if (
                    str_starts_with($clean, 'storage/cache/') ||
                    str_starts_with($clean, 'storage/logs/') ||
                    str_starts_with($clean, 'storage/outbox/') ||
                    str_starts_with($clean, 'storage/framework/')
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determina si un archivo o directorio debe ser excluido del paquete de Código Fuente / Cierre.
     */
    public static function shouldExcludeFromClosure(string $relativePath, bool $isDir = false): bool {
        $clean = ltrim(str_replace('\\', '/', $relativePath), '/');

        // 0. Archivos del subsistema de usuarios definitivamente eliminado
        if (in_array($clean, self::FORBIDDEN_USER_FILES, true)) {
            return true;
        }

        // 1. Secretos y credenciales locales: .env excluido, permitir .env.example
        if ($clean === '.env' || (str_starts_with($clean, '.env') && $clean !== '.env.example')) {
            return true;
        }

        // 2. Control de versiones e IDEs (.gitignore se conserva en código fuente)
        $segments = explode('/', $clean);
        foreach ($segments as $seg) {
            if (in_array($seg, ['.git', '.vscode', '.idea', '.agent', '.github'], true)) {
                return true;
            }
        }

        // 3. Empaquetados, backups y archivos temporales
        $lower = strtolower($clean);
        $base = basename($clean);
        if (
            str_ends_with($lower, '.zip') ||
            str_ends_with($lower, '.rar') ||
            str_ends_with($lower, '.tar') ||
            str_ends_with($lower, '.gz') ||
            str_ends_with($lower, '.7z') ||
            str_ends_with($lower, '.tmp') ||
            str_ends_with($lower, '.bak') ||
            str_ends_with($lower, '.swp') ||
            str_ends_with($lower, '~') ||
            $base === '.ds_store' ||
            $base === 'thumbs.db'
        ) {
            return true;
        }

        // 4. Storage runtime y reportes
        if (str_starts_with($clean, 'storage/')) {
            if ($clean === 'storage/reports' || str_starts_with($clean, 'storage/reports/')) {
                return true;
            }
            if ($base !== '.gitkeep' && $base !== '.htaccess') {
                if (
                    str_starts_with($clean, 'storage/cache/') ||
                    str_starts_with($clean, 'storage/logs/') ||
                    str_starts_with($clean, 'storage/outbox/') ||
                    str_starts_with($clean, 'storage/framework/')
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Calcula la lista exacta de archivos que formarán parte del paquete de producción
     * recorriendo el árbol del proyecto y aplicando la política central.
     *
     * @param string $projectDir Directorio raíz del proyecto
     * @return string[] Lista de rutas relativas ordenadas
     */
    public static function calculateProductionManifest(string $projectDir): array {
        $manifest = [];
        $normProjectDir = str_replace('\\', '/', $projectDir);

        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iter as $item) {
            $fullPath = str_replace('\\', '/', $item->getPathname());
            $rel = substr($fullPath, strlen($normProjectDir));
            if (!str_starts_with($rel, '/')) {
                $rel = '/' . $rel;
            }

            if (self::shouldExcludeFromProduction($rel, $item->isDir())) {
                continue;
            }

            if ($item->isFile()) {
                $manifest[] = ltrim($rel, '/');
            }
        }

        sort($manifest);
        return $manifest;
    }

    /**
     * Valida una lista de entradas de producción contra las reglas obligatorias y prohibidas.
     *
     * @param string[] $entries Lista de entradas (rutas relativas con '/')
     * @param string|null $projectDir Directorio del proyecto para validaciones adicionales de contenido
     * @return string[] Lista de mensajes de error encontrados (vacía si es 100% válido)
     */
    public static function validateProductionEntries(array $entries, ?string $projectDir = null): array {
        $errors = [];

        // 1. Separadores y rutas canónicas
        foreach ($entries as $e) {
            if (str_contains($e, '\\')) {
                $errors[] = "Entrada con separador Windows '\\': {$e}";
            }
            if (str_starts_with($e, '/')) {
                $errors[] = "Entrada con barra inicial '/': {$e}";
            }
            if (str_starts_with($e, 'PMO-Solutions/')) {
                $errors[] = "Entrada dentro de carpeta contenedora duplicada: {$e}";
            }
        }

        // 2. Presencia de archivos obligatorios
        foreach (self::MANDATORY_PROD_FILES as $mand) {
            if (!in_array($mand, $entries, true)) {
                $errors[] = "Archivo obligatorio ausente en producción: {$mand}";
            }
        }

        // 2b. Prohibición estricta de archivos del prototipo de usuarios eliminado
        foreach ($entries as $e) {
            if (in_array($e, self::FORBIDDEN_USER_FILES, true) || str_contains($e, '003_add_usuarios')) {
                $errors[] = "Archivo del prototipo de usuarios eliminado presente en producción: {$e}";
            }
        }

        // 3. Regla estricta para backend/: solo BACKEND_PROD_ALLOWLIST
        foreach ($entries as $e) {
            if (str_starts_with($e, 'backend/')) {
                if ($e === 'backend/' || $e === 'backend/migrations/') {
                    continue; // directorios contenedores válidos
                }
                if (!in_array($e, self::BACKEND_PROD_ALLOWLIST, true)) {
                    $errors[] = "Archivo no permitido en backend/ de producción: {$e}";
                }
            }
        }

        // 4. Prohibición estricta de tests y pruebas de módulos
        foreach ($entries as $e) {
            if (
                str_starts_with($e, 'tests/') ||
                str_contains($e, '/tests/') ||
                str_ends_with($e, 'Test.php')
            ) {
                $errors[] = "Archivo de prueba prohibido en producción: {$e}";
            }
        }

        // 5. Prohibición estricta de .env y variantes (excepto .env.example)
        foreach ($entries as $e) {
            if ($e === '.env' || (str_starts_with($e, '.env') && $e !== '.env.example')) {
                $errors[] = "Archivo .env con posibles credenciales presente en producción: {$e}";
            }
        }

        // 6. Prohibición de .git, .vscode, .idea, .agent, .gitignore, markdown
        foreach ($entries as $e) {
            $segments = explode('/', $e);
            foreach ($segments as $seg) {
                if (in_array($seg, ['.git', '.gitignore', '.vscode', '.idea', '.agent', '.github'], true)) {
                    $errors[] = "Archivo o carpeta de control/IDE prohibido en producción: {$e}";
                    break;
                }
            }
            if (str_ends_with(strtolower($e), '.md')) {
                $errors[] = "Archivo markdown prohibido en producción: {$e}";
            }
            if (
                str_ends_with(strtolower($e), '.zip') ||
                str_ends_with(strtolower($e), '.rar') ||
                str_ends_with(strtolower($e), '.tmp') ||
                str_ends_with(strtolower($e), '.bak')
            ) {
                $errors[] = "Archivo comprimido o temporal prohibido en producción: {$e}";
            }
            if (str_starts_with($e, 'storage/reports')) {
                $errors[] = "Reportes runtime prohibidos en producción: {$e}";
            }
            if (
                str_starts_with($e, 'storage/cache/') ||
                str_starts_with($e, 'storage/logs/') ||
                str_starts_with($e, 'storage/outbox/')
            ) {
                $bn = basename($e);
                if ($bn !== '.gitkeep' && $bn !== '.htaccess' && !str_ends_with($e, '/')) {
                    $errors[] = "Archivo runtime de storage prohibido en producción: {$e}";
                }
            }
        }

        // 7. Inspección de contenido de .env.example si projectDir está disponible
        if ($projectDir !== null && in_array('.env.example', $entries, true)) {
            $envPath = $projectDir . '/.env.example';
            if (file_exists($envPath)) {
                $content = (string)file_get_contents($envPath);
                if (preg_match('/PMO_DB_PASSWORD=[^\r\n\s]+/', $content, $m)) {
                    $errors[] = "El archivo .env.example contiene una contraseña de base de datos no vacía";
                }
                if (preg_match('/PMO_SMTP_PASSWORD=[^\r\n\s]+/', $content, $m)) {
                    $errors[] = "El archivo .env.example contiene una contraseña SMTP no vacía";
                }
            }
        }

        return $errors;
    }

    /**
     * Valida una lista de entradas del paquete de Código Fuente / Cierre.
     *
     * @param string[] $entries
     * @return string[]
     */
    public static function validateClosureEntries(array $entries): array {
        $errors = [];

        // 1. Separadores y rutas canónicas
        foreach ($entries as $e) {
            if (str_contains($e, '\\')) {
                $errors[] = "Entrada con separador Windows '\\': {$e}";
            }
            if (str_starts_with($e, 'PMO-Solutions/')) {
                $errors[] = "Entrada dentro de carpeta contenedora duplicada: {$e}";
            }
        }

        // 2. Archivos indispensables
        foreach (self::MANDATORY_CLOSURE_FILES as $mand) {
            if (!in_array($mand, $entries, true)) {
                $errors[] = "Archivo indispensable ausente en código fuente: {$mand}";
            }
        }

        // 2b. Prohibición estricta de archivos del prototipo de usuarios eliminado
        foreach ($entries as $e) {
            if (in_array($e, self::FORBIDDEN_USER_FILES, true) || str_contains($e, '003_add_usuarios')) {
                $errors[] = "Archivo del prototipo de usuarios eliminado presente en código fuente: {$e}";
            }
        }

        // 3. Prohibición de .env, .git, .vscode, .idea, .agent, reportes y zips
        foreach ($entries as $e) {
            if ($e === '.env' || (str_starts_with($e, '.env') && $e !== '.env.example')) {
                $errors[] = "Archivo .env con posibles credenciales presente en código fuente: {$e}";
            }
            $segments = explode('/', $e);
            foreach ($segments as $seg) {
                if (in_array($seg, ['.git', '.vscode', '.idea', '.agent', '.github'], true)) {
                    $errors[] = "Directorio de control/IDE presente en código fuente: {$e}";
                    break;
                }
            }
            if (str_starts_with($e, 'storage/reports')) {
                $errors[] = "Reportes runtime presentes en código fuente: {$e}";
            }
            if (
                str_ends_with(strtolower($e), '.zip') ||
                str_ends_with(strtolower($e), '.rar') ||
                str_ends_with(strtolower($e), '.tmp') ||
                str_ends_with(strtolower($e), '.bak')
            ) {
                $errors[] = "Archivo comprimido o temporal residual en código fuente: {$e}";
            }
        }

        return $errors;
    }

    /**
     * Abre un archivo ZIP de producción real con ZipArchive y valida todas sus entradas.
     *
     * @param string $zipFilePath Ruta absoluta o relativa al archivo ZIP
     * @return string[] Lista de errores (vacía si es válido)
     */
    public static function validateProductionZipArchive(string $zipFilePath): array {
        if (!file_exists($zipFilePath)) {
            return ["El archivo ZIP de producción no existe: {$zipFilePath}"];
        }

        $zip = new ZipArchive();
        $res = $zip->open($zipFilePath);
        if ($res !== true) {
            return ["No se pudo abrir el archivo ZIP con ZipArchive (Código: {$res}): {$zipFilePath}"];
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = str_replace('\\', '/', $zip->getNameIndex($i));
        }

        // Inspeccionar contenido de .env.example dentro del ZIP
        $envExampleContent = $zip->getFromName('.env.example');
        $zip->close();

        $errors = self::validateProductionEntries($entries);

        if ($envExampleContent !== false && $envExampleContent !== '') {
            if (preg_match('/PMO_DB_PASSWORD=[^\r\n\s]+/', $envExampleContent)) {
                $errors[] = "El archivo .env.example dentro del ZIP contiene credenciales de BD";
            }
            if (preg_match('/PMO_SMTP_PASSWORD=[^\r\n\s]+/', $envExampleContent)) {
                $errors[] = "El archivo .env.example dentro del ZIP contiene credenciales SMTP";
            }
        }

        return $errors;
    }

    /**
     * Abre un archivo ZIP de código fuente real con ZipArchive y valida todas sus entradas.
     *
     * @param string $zipFilePath
     * @return string[]
     */
    public static function validateClosureZipArchive(string $zipFilePath): array {
        if (!file_exists($zipFilePath)) {
            return ["El archivo ZIP de código fuente no existe: {$zipFilePath}"];
        }

        $zip = new ZipArchive();
        $res = $zip->open($zipFilePath);
        if ($res !== true) {
            return ["No se pudo abrir el archivo ZIP de cierre (Código: {$res}): {$zipFilePath}"];
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = str_replace('\\', '/', $zip->getNameIndex($i));
        }
        $zip->close();

        return self::validateClosureEntries($entries);
    }
}
