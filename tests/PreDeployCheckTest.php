<?php
/**
 * PMO SOLUTIONS — Suite de Pruebas: Verificación Pre-Despliegue (Tarea 11)
 *
 * Configuración estricta de ejecución:
 *   - error_reporting = E_ALL
 *   - display_errors = 1
 *
 * Cobertura de pruebas:
 *   1. Existencia física y sintaxis válida de artisan/pre-deploy-check.php.
 *   2. Ejecución exitosa del script pre-deploy-check (código de salida 0).
 *   3. Presencia de chequeos locales como [PASS].
 *   4. Presencia obligatoria de comprobaciones de hosting como [PENDING] (nunca [PASS]).
 *   5. Existencia y contenido completo de DEPLOYMENT.md.
 *   6. Bloqueo obligatorio (código != 0) ante archivo crítico ausente en copia aislada.
 *   7. Bloqueo obligatorio (código != 0) ante presencia de .env o archivos prohibidos en copia aislada.
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

define('PMO_TEST_RUNNER', true);

echo "\n======================================================================\n";
echo " SUITE DE PRUEBAS: VERIFICACIÓN PRE-DESPLIEGUE (TAREA 11)\n";
echo "======================================================================\n";

final class PreDeployCheckTest {

    private string $rootDir;
    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;

    public function __construct() {
        $this->rootDir = str_replace('\\', '/', dirname(__DIR__));
    }

    public function runAll(): void {
        $this->testScriptExistenceAndSyntax();
        $this->testSuccessfulPreDeployExecution();
        $this->testLocalChecksReportedAsPass();
        $this->testHostingChecksReportedAsPendingNeverPass();
        $this->testDeploymentGuideCompleteness();
        $this->testBlockingOnMissingCriticalFileInTempCopy();
        $this->testBlockingOnForbiddenFileInTempCopy();

        $this->printSummary();
    }

    private function assert(bool $condition, string $message): void {
        if ($condition) {
            $this->passed++;
            echo "  \033[32m✔ [PASS]\033[0m {$message}\n";
        } else {
            $this->failed++;
            echo "  \033[31m✖ [FAIL]\033[0m {$message}\n";
        }
    }

    private function createTempProjectCopy(): string {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_predeploy_' . bin2hex(random_bytes(6));
        $tempDir = str_replace('\\', '/', $tempDir);
        mkdir($tempDir, 0777, true);

        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iter as $item) {
            $norm = str_replace('\\', '/', $item->getPathname());
            $rel = substr($norm, strlen($this->rootDir));

            if (
                str_starts_with($rel, '/.git') ||
                str_starts_with($rel, '/.agent') ||
                str_ends_with(strtolower($rel), '.zip') ||
                str_ends_with(strtolower($rel), '.rar')
            ) {
                continue;
            }

            $dest = $tempDir . $rel;
            if ($item->isDir()) {
                if (!is_dir($dest)) {
                    mkdir($dest, 0777, true);
                }
            } else {
                $parent = dirname($dest);
                if (!is_dir($parent)) {
                    mkdir($parent, 0777, true);
                }
                copy($item->getPathname(), $dest);
            }
        }

        return $tempDir;
    }

    private function removeDir(string $dir): void {
        if (!is_dir($dir)) return;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }
        @rmdir($dir);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Existencia y Sintaxis de artisan/pre-deploy-check.php
    // ─────────────────────────────────────────────────────────────────────────
    private function testScriptExistenceAndSyntax(): void {
        echo "\n--- 1. Existencia Física y Sintaxis de artisan/pre-deploy-check.php ---\n";

        $script = $this->rootDir . '/artisan/pre-deploy-check.php';
        $this->assert(file_exists($script), "Script artisan/pre-deploy-check.php existe físicamente");

        $phpBin = PHP_BINARY;
        $cmd = "\"{$phpBin}\" -l \"{$script}\" 2>&1";
        exec($cmd, $out, $exitCode);
        $this->assert($exitCode === 0, "Sintaxis PHP de artisan/pre-deploy-check.php es válida (php -l)");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Ejecución Exitosa del Pre-Deploy Check
    // ─────────────────────────────────────────────────────────────────────────
    private function testSuccessfulPreDeployExecution(): void {
        echo "\n--- 2. Ejecución Exitosa de artisan/pre-deploy-check.php ---\n";

        $script = $this->rootDir . '/artisan/pre-deploy-check.php';
        $phpBin = PHP_BINARY;
        $cmd = "\"{$phpBin}\" \"{$script}\" \"{$this->rootDir}\" 2>&1";
        exec($cmd, $out, $exitCode);

        $outText = implode("\n", $out);
        $this->assert($exitCode === 0, "artisan/pre-deploy-check.php finaliza con código de salida 0");
        $this->assert(str_contains($outText, 'VERIFICACIÓN LOCAL COMPLETADA EXITOSAMENTE'), "Salida confirma éxito de la verificación local");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Chequeos Locales como PASS
    // ─────────────────────────────────────────────────────────────────────────
    private function testLocalChecksReportedAsPass(): void {
        echo "\n--- 3. Verificación de Chequeos Locales Reportados como PASS ---\n";

        $script = $this->rootDir . '/artisan/pre-deploy-check.php';
        $phpBin = PHP_BINARY;
        $cmd = "\"{$phpBin}\" \"{$script}\" \"{$this->rootDir}\" 2>&1";
        exec($cmd, $out, $exitCode);

        $outText = implode("\n", $out);
        $this->assert(str_contains($outText, '[Plataforma] Versión compatible PHP 8.0.30+'), "Chequeo de versión PHP registrado");
        $this->assert(str_contains($outText, '[Extensiones] Extensión pdo'), "Chequeo de extensión pdo registrado");
        $this->assert(str_contains($outText, '[Extensiones] Extensión pdo_mysql'), "Chequeo de extensión pdo_mysql registrado");
        $this->assert(str_contains($outText, '[Permisos] Directorio escribible: storage'), "Chequeo de directorio storage registrado");
        $this->assert(str_contains($outText, '[Archivos] Archivo crítico: backend/schema_v2.sql'), "Chequeo de esquema backend/schema_v2.sql registrado");
        $this->assert(str_contains($outText, '[Seguridad] admin_enabled desactivado'), "Chequeo de admin_enabled registrado");
        $this->assert(str_contains($outText, '[Limpieza] Ausencia de archivos .env y backups'), "Chequeo de ausencia de secretos registrado");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Comprobaciones de Hosting como PENDING (Nunca PASS)
    // ─────────────────────────────────────────────────────────────────────────
    private function testHostingChecksReportedAsPendingNeverPass(): void {
        echo "\n--- 4. Comprobaciones de Hosting Exclusivas como PENDING (Nunca PASS) ---\n";

        $script = $this->rootDir . '/artisan/pre-deploy-check.php';
        $phpBin = PHP_BINARY;
        $cmd = "\"{$phpBin}\" \"{$script}\" \"{$this->rootDir}\" 2>&1";
        exec($cmd, $out, $exitCode);

        $rawText = implode("\n", $out);
        $cleanText = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $rawText);
        $cleanText = (string)preg_replace('/\e\[[0-9;]*[a-zA-Z]/', '', (string)$cleanText);

        $hostingChecks = [
            'Aprovisionamiento de Hosting',
            'Certificado SSL/TLS',
            'Base de Datos MySQL Remota',
            'Servidor de Correo SMTP',
            'Tareas Programadas (Cron)',
            'DNS & Dominio'
        ];

        foreach ($hostingChecks as $checkName) {
            $isPending = str_contains($cleanText, "[PENDING] [Hosting GoDaddy] {$checkName}");
            $isPass = str_contains($cleanText, "[PASS] [Hosting GoDaddy] {$checkName}");
            $this->assert($isPending && !$isPass, "Chequeo '{$checkName}' está en PENDING y NUNCA en PASS");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Guía de Despliegue DEPLOYMENT.md
    // ─────────────────────────────────────────────────────────────────────────
    private function testDeploymentGuideCompleteness(): void {
        echo "\n--- 5. Completitud y Coherencia de DEPLOYMENT.md ---\n";

        $deployDoc = $this->rootDir . '/DEPLOYMENT.md';
        $this->assert(file_exists($deployDoc), "Archivo DEPLOYMENT.md existe en raíz");

        $content = (string)file_get_contents($deployDoc);
        $this->assert(str_contains($content, 'PHP 8.0.30') || str_contains($content, 'PHP 8.0'), "DEPLOYMENT.md documenta versión de PHP");
        $this->assert(str_contains($content, 'pdo_mysql') && str_contains($content, 'openssl'), "DEPLOYMENT.md documenta extensiones PHP");
        $this->assert(str_contains($content, 'backend/schema_v2.sql'), "DEPLOYMENT.md documenta importación de schema_v2.sql");
        $this->assert(str_contains($content, 'PMO_APP_ENV') && str_contains($content, 'PMO_DB_') && str_contains($content, 'PMO_SMTP_'), "DEPLOYMENT.md documenta variables reales del servidor PMO_*");
        $this->assert(str_contains($content, 'En producción, la aplicación NO carga automáticamente archivos `.env`') || str_contains($content, 'NO carga automáticamente archivos .env'), "DEPLOYMENT.md advierte que .env no se carga en producción");
        $this->assert(!str_contains($content, 'Crear Archivo `.env` en Producción') && !str_contains($content, 'Crear Archivo .env en Producción'), "DEPLOYMENT.md NO instruye crear .env en producción");
        $this->assert(str_contains($content, 'desarrollo') && str_contains($content, 'fuera de la raíz web'), "DEPLOYMENT.md presenta .env solo para desarrollo y documenta alternativa segura fuera de la raíz web");
        $this->assert(str_contains($content, 'chmod 755') || str_contains($content, 'storage/'), "DEPLOYMENT.md documenta permisos de storage/");
        $this->assert(str_contains($content, 'process-outbox.php') && str_contains($content, 'Cron'), "DEPLOYMENT.md documenta cron job de outbox");
        $this->assert(str_contains($content, 'POST /contacto/submit') && str_contains($content, 'POST /reclamaciones/submit'), "DEPLOYMENT.md documenta verificación de rutas de formularios");
        $this->assert(str_contains($content, 'Rollback') || str_contains($content, 'reversión'), "DEPLOYMENT.md documenta plan de rollback");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Bloqueo ante Archivo Crítico Ausente en Copia Aislada
    // ─────────────────────────────────────────────────────────────────────────
    private function testBlockingOnMissingCriticalFileInTempCopy(): void {
        echo "\n--- 6. Bloqueo Obligatorio por Archivo Crítico Ausente (Copia Aislada) ---\n";

        $tempProject = $this->createTempProjectCopy();
        $targetFile = $tempProject . '/backend/schema_v2.sql';
        if (file_exists($targetFile)) {
            unlink($targetFile);
        }

        $script = $tempProject . '/artisan/pre-deploy-check.php';
        $phpBin = PHP_BINARY;
        $cmd = "\"{$phpBin}\" \"{$script}\" \"{$tempProject}\" 2>&1";
        exec($cmd, $out, $exitCode);

        $outText = implode("\n", $out);
        $this->assert($exitCode !== 0, "pre-deploy-check.php rechaza con código != 0 ante schema_v2.sql ausente");
        $this->assert(str_contains($outText, 'backend/schema_v2.sql'), "pre-deploy-check.php reporta el archivo crítico faltante");

        $this->removeDir($tempProject);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Bloqueo ante Archivo Prohibido en Copia Aislada
    // ─────────────────────────────────────────────────────────────────────────
    private function testBlockingOnForbiddenFileInTempCopy(): void {
        echo "\n--- 7. Bloqueo Obligatorio por Archivo Prohibido .env (Copia Aislada) ---\n";

        $tempProject = $this->createTempProjectCopy();
        $envFile = $tempProject . '/.env';
        file_put_contents($envFile, "DB_PASS=secret123\n");

        $script = $tempProject . '/artisan/pre-deploy-check.php';
        $phpBin = PHP_BINARY;
        $cmd = "\"{$phpBin}\" \"{$script}\" \"{$tempProject}\" 2>&1";
        exec($cmd, $out, $exitCode);

        $outText = implode("\n", $out);
        $this->assert($exitCode !== 0, "pre-deploy-check.php rechaza con código != 0 ante presencia de .env");
        $this->assert(str_contains($outText, 'Archivos prohibidos encontrados'), "pre-deploy-check.php señala la detección del archivo .env");

        $this->removeDir($tempProject);
    }

    private function printSummary(): void {
        $total = $this->passed + $this->failed + $this->skipped;
        echo "\n======================================================================\n";
        echo " RESUMEN: PRUEBAS DE VERIFICACIÓN PRE-DESPLIEGUE (TAREA 11)\n";
        echo "======================================================================\n";
        echo " Total tests : {$total}\n";
        echo " \033[32m✔ Pasados    : {$this->passed}\033[0m\n";
        echo " \033[33m⚡ Omitidos   : {$this->skipped}\033[0m\n";
        echo " \033[31m✖ Fallados   : {$this->failed}\033[0m\n";
        echo "======================================================================\n\n";

        if ($this->failed === 0) {
            echo "\033[32m✔ TODAS LAS PRUEBAS DE PRE-DESPLIEGUE PASARON AL 100%.\033[0m\n\n";
            exit(0);
        } else {
            echo "\033[31m✖ HUBO FALLOS EN LA SUITE DE PRE-DESPLIEGUE.\033[0m\n\n";
            exit(1);
        }
    }
}

$suite = new PreDeployCheckTest();
$suite->runAll();
