<?php
/**
 * PMO SOLUTIONS — Suite de Pruebas del Quality Gate & Empaquetador
 * Verificación integral del pipeline de calidad y empaquetador pre-despliegue.
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

define('PMO_TEST_RUNNER', true);

echo "\n======================================================================\n";
echo " SUITE DE PRUEBAS: QUALITY GATE & EMPAQUETADO PRE-DESPLIEGUE\n";
echo "======================================================================\n";

final class QualityGateTest {

    private string $rootDir;
    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;

    public function __construct() {
        $this->rootDir = str_replace('\\', '/', dirname(__DIR__));
    }

    private function getPhpCmd(): string {
        return escapeshellarg(PHP_BINARY) . (extension_loaded('zip') ? ' -d extension=zip' : '');
    }

    public function runAll(): void {
        $this->testSuccessfulQualityGateExecution();
        $this->testReportGenerationAndPiiAbsence();
        $this->testBlockingOnPhpSyntaxErrorInTempCopy();
        $this->testBlockingOnForbiddenFileInTempCopy();
        $this->testBlockingOnHardcodedLocalPathInTempCopy();
        $this->testBlockingOnCspMismatchInTempCopy();
        $this->testGracefulSkipHandling();
        $this->testPackagerRefusesOnQualityGateFailure();
        $this->testSkipQualityGateFlagRestrictions();
        $this->testReportsExcludedFromProductionZip();
        $this->testParserSuiteHandling();
        $this->testBlockingOnMissingOrUnreadableCriticalFileInTempCopy();

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

    private function skip(string $testName, string $reason): void {
        $this->skipped++;
        echo "  \033[33m⚡ [SKIP]\033[0m {$testName} — {$reason}\n";
    }

    /**
     * Crea una copia temporal ligera del proyecto para pruebas destructivas
     */
    private function createTempProjectCopy(): string {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_qg_test_' . bin2hex(random_bytes(6));
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
                str_starts_with($rel, '/.git/') ||
                $rel === '/.git' ||
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
    // 1. Ejecución Exitosa del Quality Gate
    // ─────────────────────────────────────────────────────────────────────────
    private function testSuccessfulQualityGateExecution(): void {
        echo "\n--- 1. Ejecución Exitosa del Quality Gate ---\n";

        $qgScript = $this->rootDir . '/artisan/quality-gate.php';
        $this->assert(file_exists($qgScript), "Script artisan/quality-gate.php existe físicamente");

        $phpCmd = $this->getPhpCmd();
        $cmd = "{$phpCmd} " . escapeshellarg($qgScript) . " " . escapeshellarg($this->rootDir) . " 2>&1";
        exec($cmd, $out, $exitCode);

        $this->assert($exitCode === 0, "artisan/quality-gate.php finaliza con código de salida 0 (éxito)");
        $outText = implode("\n", $out);
        $this->assert(str_contains($outText, 'QUALITY GATE APROBADO'), "Salida estándar confirma aprobación del Quality Gate");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Generación de Reportes JSON y TXT
    // ─────────────────────────────────────────────────────────────────────────
    private function testReportGenerationAndPiiAbsence(): void {
        echo "\n--- 2. Generación de Reportes de Auditoría y Ausencia de PII ---\n";

        $jsonFile = $this->rootDir . '/storage/reports/quality-gate.json';
        $txtFile = $this->rootDir . '/storage/reports/quality-gate.txt';

        $this->assert(file_exists($jsonFile), "Reporte JSON generado en storage/reports/quality-gate.json");
        $this->assert(file_exists($txtFile), "Reporte TXT generado en storage/reports/quality-gate.txt");

        $jsonData = json_decode((string)file_get_contents($jsonFile), true);
        $this->assert(is_array($jsonData) && isset($jsonData['status']) && $jsonData['status'] === 'SUCCESS', "Reporte JSON es válido y contiene status SUCCESS");
        $this->assert(isset($jsonData['metrics']['passed']) && $jsonData['metrics']['passed'] > 50, "Reporte JSON contabiliza métricas de chequeos (> 50)");

        $rawJson = (string)file_get_contents($jsonFile);
        $rawTxt = (string)file_get_contents($txtFile);

        $hasNoDbPassword = !str_contains($rawJson, 'DB_PASS') && !str_contains($rawTxt, 'DB_PASS');
        $hasNoSmtpPassword = !str_contains($rawJson, 'SMTP_PASS') && !str_contains($rawTxt, 'SMTP_PASS');
        $this->assert($hasNoDbPassword && $hasNoSmtpPassword, "Reportes no contienen credenciales ni nombres de variables secretas con valores");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Bloqueo por Error de Sintaxis PHP en Copia Temporal
    // ─────────────────────────────────────────────────────────────────────────
    private function testBlockingOnPhpSyntaxErrorInTempCopy(): void {
        echo "\n--- 3. Bloqueo Obligatorio por Error de Sintaxis PHP (Copia Aislada) ---\n";

        $tempProject = $this->createTempProjectCopy();
        $corruptFile = $tempProject . '/app/Controllers/CorruptTestController.php';
        file_put_contents($corruptFile, '<?php namespace App\Controllers; class CorruptTestController { syntax error here ;;; }');

        $phpCmd = $this->getPhpCmd();
        $qg = $tempProject . '/artisan/quality-gate.php';
        $cmd = "{$phpCmd} " . escapeshellarg($qg) . " " . escapeshellarg($tempProject) . " 2>&1";
        exec($cmd, $out, $exitCode);

        $outText = implode("\n", $out);
        $this->assert($exitCode !== 0, "Quality Gate rechaza con código distinto de 0 ante error de sintaxis PHP");
        $this->assert(str_contains($outText, 'CorruptTestController.php') || str_contains($outText, 'Sintaxis PHP'), "Quality Gate identifica el archivo corrupto");

        $this->removeDir($tempProject);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Bloqueo por Archivo Prohibido (.env / ZIP) en Copia Temporal
    // ─────────────────────────────────────────────────────────────────────────
    private function testBlockingOnForbiddenFileInTempCopy(): void {
        echo "\n--- 4. Bloqueo Obligatorio por Archivo Prohibido (Copia Aislada) ---\n";

        $tempProject = $this->createTempProjectCopy();
        $envFile = $tempProject . '/.env';
        file_put_contents($envFile, 'DB_PASS=secret_test_password');

        $phpCmd = $this->getPhpCmd();
        $qg = $tempProject . '/artisan/quality-gate.php';
        $cmd = "{$phpCmd} " . escapeshellarg($qg) . " " . escapeshellarg($tempProject) . " 2>&1";
        exec($cmd, $out, $exitCode);

        $outText = implode("\n", $out);
        $this->assert($exitCode !== 0, "Quality Gate rechaza con código distinto de 0 ante presencia de .env");
        $this->assert(str_contains($outText, 'Ausencia de archivos prohibidos'), "Quality Gate señala la presencia de archivo prohibido");

        $this->removeDir($tempProject);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Bloqueo por Ruta Absoluta Local en Copia Temporal
    // ─────────────────────────────────────────────────────────────────────────
    private function testBlockingOnHardcodedLocalPathInTempCopy(): void {
        echo "\n--- 5. Bloqueo Obligatorio por Ruta Absoluta Local (Copia Aislada) ---\n";

        $tempProject = $this->createTempProjectCopy();
        $prodFile = $tempProject . '/app/Core/PathTestMock.php';
        file_put_contents($prodFile, '<?php namespace App\Core; class PathTestMock { public static function get() { return "C:\\Users\\Developer\\secret\\path"; } }');

        $phpCmd = $this->getPhpCmd();
        $qg = $tempProject . '/artisan/quality-gate.php';
        $cmd = "{$phpCmd} " . escapeshellarg($qg) . " " . escapeshellarg($tempProject) . " 2>&1";
        exec($cmd, $out, $exitCode);

        $outText = implode("\n", $out);
        $this->assert($exitCode !== 0, "Quality Gate rechaza ante ruta absoluta hardcodeada en app/");
        $this->assert(str_contains($outText, 'Ausencia de rutas absolutas locales'), "Quality Gate señala la presencia de ruta absoluta local");

        $this->removeDir($tempProject);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Bloqueo por Discrepancia CSP en Copia Temporal
    // ─────────────────────────────────────────────────────────────────────────
    private function testBlockingOnCspMismatchInTempCopy(): void {
        echo "\n--- 6. Bloqueo Obligatorio por Discrepancia CSP (Copia Aislada) ---\n";

        $tempProject = $this->createTempProjectCopy();
        $htFile = $tempProject . '/.htaccess';
        $htContent = (string)file_get_contents($htFile);
        $htContent = str_replace("https://maps.google.com", "https://unauthorized-domain.com", $htContent);
        file_put_contents($htFile, $htContent);

        $phpCmd = $this->getPhpCmd();
        $qg = $tempProject . '/artisan/quality-gate.php';
        $cmd = "{$phpCmd} " . escapeshellarg($qg) . " " . escapeshellarg($tempProject) . " 2>&1";
        exec($cmd, $out, $exitCode);

        $outText = implode("\n", $out);
        $this->assert($exitCode !== 0, "Quality Gate rechaza con código != 0 ante discrepancia de directivas CSP");
        $this->assert(str_contains($outText, 'Coherencia CSP'), "Quality Gate identifica el desajuste de CSP");

        $this->removeDir($tempProject);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Manejo Tolerante de Servicios Ausentes (SKIP sin falso PASS)
    // ─────────────────────────────────────────────────────────────────────────
    private function testGracefulSkipHandling(): void {
        echo "\n--- 7. Manejo Tolerante de Dependencias Ausentes (SKIP) ---\n";

        $jsonFile = $this->rootDir . '/storage/reports/quality-gate.json';
        $jsonData = json_decode((string)file_get_contents($jsonFile), true);

        $skipCount = $jsonData['metrics']['assertions']['skipped'] ?? 0;
        $this->assert($skipCount > 0, "Las pruebas dependientes de MySQL desconectado se registran como SKIP ({$skipCount} skips)");

        $failCount = $jsonData['metrics']['failed'] ?? 0;
        $this->assert($failCount === 0, "Los casos SKIP no provocan fallo del Quality Gate");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. Bloqueo del Empaquetador ante Fallo del Quality Gate y Limpieza de Previos
    // ─────────────────────────────────────────────────────────────────────────
    private function testPackagerRefusesOnQualityGateFailure(): void {
        echo "\n--- 8. Bloqueo del Empaquetador ante Fallo del Quality Gate ---\n";

        $tempProject = $this->createTempProjectCopy();
        file_put_contents($tempProject . '/app/Controllers/BrokenCtrl.php', '<?php namespace App\Controllers; broken code');

        $tempOutDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_zip_out_' . bin2hex(random_bytes(4));
        mkdir($tempOutDir, 0777, true);

        $prodZip = $tempOutDir . DIRECTORY_SEPARATOR . 'PMO-Solutions-Produccion.zip';
        $cierreZip = $tempOutDir . DIRECTORY_SEPARATOR . 'PMO-Solutions-Codigo-Fuente.zip';

        // Crear dos ZIP previos para verificar que son eliminados ante fallo
        file_put_contents($prodZip, 'DUMMY_OLD_PROD_ZIP_CONTENT');
        file_put_contents($cierreZip, 'DUMMY_OLD_CIERRE_ZIP_CONTENT');
        $this->assert(file_exists($prodZip) && file_exists($cierreZip), "Se crearon dos archivos ZIP previos para prueba de limpieza");

        $phpCmd = $this->getPhpCmd();
        $packager = $tempProject . '/artisan/create_production_zip.php';
        $cmd = "{$phpCmd} " . escapeshellarg($packager) . " " . escapeshellarg($tempOutDir) . " 2>&1";
        exec($cmd, $out, $exitCode);

        $this->assert($exitCode !== 0, "create_production_zip.php aborta con código != 0 cuando Quality Gate falla");
        $this->assert(!file_exists($prodZip), "El archivo anterior/parcial PMO-Solutions-Produccion.zip fue ELIMINADO");
        $this->assert(!file_exists($cierreZip), "El archivo anterior/parcial PMO-Solutions-Codigo-Fuente.zip fue ELIMINADO");

        $this->removeDir($tempProject);
        $this->removeDir($tempOutDir);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 9. Restricciones del Flag --skip-quality-gate y Bypass en Producción
    // ─────────────────────────────────────────────────────────────────────────
    private function testSkipQualityGateFlagRestrictions(): void {
        echo "\n--- 9. Restricciones del Flag --skip-quality-gate y Bypass ---\n";

        $tempProject = $this->createTempProjectCopy();
        $tempOutDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_zip_out_' . bin2hex(random_bytes(4));
        mkdir($tempOutDir, 0777, true);

        $phpCmd = $this->getPhpCmd();
        $packager = $tempProject . '/artisan/create_production_zip.php';

        // Intento 1: --skip-quality-gate en entorno production -> Debe fallar
        putenv('PMO_APP_ENV=production');
        $_ENV['PMO_APP_ENV'] = 'production';
        $cmdProdEnv = "{$phpCmd} " . escapeshellarg($packager) . " " . escapeshellarg($tempOutDir) . " --skip-quality-gate 2>&1";
        exec($cmdProdEnv, $outProd, $codeProd);
        $this->assert($codeProd !== 0, "--skip-quality-gate es estrictamente RECHAZADO cuando PMO_APP_ENV !== 'development'");

        // Intento 2: PMO_APP_ENV=production + PMO_TEST_RUNNER=1 con código roto -> No puede saltar el gate en producción
        file_put_contents($tempProject . '/app/Controllers/BrokenCtrl2.php', '<?php namespace App\Controllers; broken code');
        putenv('PMO_APP_ENV=production');
        $_ENV['PMO_APP_ENV'] = 'production';
        putenv('PMO_TEST_RUNNER=1');
        $_ENV['PMO_TEST_RUNNER'] = '1';
        $cmdProdRunner = "{$phpCmd} " . escapeshellarg($packager) . " " . escapeshellarg($tempOutDir) . " 2>&1";
        exec($cmdProdRunner, $outProdRun, $codeProdRun);
        $this->assert($codeProdRun !== 0, "PMO_TEST_RUNNER=1 en entorno production NO omite el Quality Gate y falla ante error");
        unlink($tempProject . '/app/Controllers/BrokenCtrl2.php');

        // Intento 3: --skip-quality-gate con PMO_APP_ENV=development -> Debe permitir empaquetado rápido
        putenv('PMO_APP_ENV=development');
        $_ENV['PMO_APP_ENV'] = 'development';
        $cmdDevEnv = "{$phpCmd} " . escapeshellarg($packager) . " " . escapeshellarg($tempOutDir) . " --skip-quality-gate 2>&1";
        exec($cmdDevEnv, $outDev, $codeDev);
        $this->assert($codeDev === 0, "--skip-quality-gate es PERMITIDO cuando PMO_APP_ENV='development'");

        // Restaurar estado de entorno
        putenv('PMO_APP_ENV');
        unset($_ENV['PMO_APP_ENV']);
        putenv('PMO_TEST_RUNNER');
        unset($_ENV['PMO_TEST_RUNNER']);

        $this->removeDir($tempProject);
        $this->removeDir($tempOutDir);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 10. Exclusión de storage/reports/ del ZIP de Producción
    // ─────────────────────────────────────────────────────────────────────────
    private function testReportsExcludedFromProductionZip(): void {
        echo "\n--- 10. Exclusión de storage/reports/ en Paquete de Producción ---\n";

        $tempProject = $this->createTempProjectCopy();
        $tempOutDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_zip_out_' . bin2hex(random_bytes(4));
        mkdir($tempOutDir, 0777, true);

        // Crear reporte dummy en copia
        $repDir = $tempProject . '/storage/reports';
        if (!is_dir($repDir)) {
            mkdir($repDir, 0777, true);
        }
        file_put_contents($repDir . '/quality-gate.json', '{"dummy":true}');

        $phpCmd = $this->getPhpCmd();
        $packager = $tempProject . '/artisan/create_production_zip.php';
        putenv('PMO_APP_ENV=development');
        $_ENV['PMO_APP_ENV'] = 'development';

        $cmd = "{$phpCmd} " . escapeshellarg($packager) . " " . escapeshellarg($tempOutDir) . " --skip-quality-gate 2>&1";
        exec($cmd, $out, $code);

        putenv('PMO_APP_ENV');
        unset($_ENV['PMO_APP_ENV']);
        $prodZipPath = $tempOutDir . DIRECTORY_SEPARATOR . 'PMO-Solutions-Produccion.zip';
        $this->assert(file_exists($prodZipPath), "ZIP de producción generado para inspección");

        $hasReports = false;
        if (file_exists($prodZipPath)) {
            $zip = new ZipArchive();
            $isOpen = $zip->open($prodZipPath);
            $this->assert($isOpen === true, "ZIP de producción es legible");

            if ($isOpen === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    if (str_starts_with($entry, 'storage/reports')) {
                        $hasReports = true;
                        break;
                    }
                }
                $zip->close();
            }
        }

        $this->assert(!$hasReports, "storage/reports/ está estrictamente EXCLUIDO del paquete de producción");

        $this->removeDir($tempProject);
        $this->removeDir($tempOutDir);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 11. Pruebas Unitarias del Parser de Salida de Suites (parseTestSuiteMetrics)
    // ─────────────────────────────────────────────────────────────────────────
    private function parseMetrics(string $rawOutput, int $exitCode, string $suiteFile): array {
        // 1. Eliminar códigos ANSI de escape
        $cleanOutput = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $rawOutput);
        $cleanOutput = preg_replace('/\e\[[0-9;]*[a-zA-Z]/', '', (string)$cleanOutput);

        $passed = 0;
        $failed = 0;
        $skipped = 0;
        $declaredTotal = null;
        $hasSummary = false;
        // Formato 1 (SoftSkillsTest): "Tests ejecutados: 181 | Pasados: 176 | Fallados: 0 | Omitidos: 5"
        if (preg_match('/Tests\s+ejecutados:\s*(\d+)\s*\|\s*Pasados:\s*(\d+)\s*\|\s*Fallados:\s*(\d+)\s*\|\s*Omitidos:\s*(\d+)/i', (string)$cleanOutput, $m)) {
            $declaredTotal = (int)$m[1];
            $passed = (int)$m[2];
            $failed = (int)$m[3];
            $skipped = (int)$m[4];
            $hasSummary = true;
        } elseif (preg_match('/Total\s+tests:\s*(\d+)\s*\|\s*Pasados:\s*(\d+)\s*\|\s*Fallados:\s*(\d+)\s*\|\s*Omitidos[^:]*:\s*(\d+)/i', (string)$cleanOutput, $m)) {
            $declaredTotal = (int)$m[1];
            $passed = (int)$m[2];
            $failed = (int)$m[3];
            $skipped = (int)$m[4];
            $hasSummary = true;
        } elseif (
            preg_match('/Total\s+de\s+pruebas\s+ejecutadas\s*:\s*(\d+)/i', (string)$cleanOutput, $mTot) &&
            preg_match('/Pruebas\s+exitosas[^\d:]*:\s*(\d+)/i', (string)$cleanOutput, $mPass) &&
            preg_match('/Pruebas\s+fallidas[^\d:]*:\s*(\d+)/i', (string)$cleanOutput, $mFail)
        ) {
            $declaredTotal = (int)$mTot[1];
            $passed = (int)$mPass[1];
            $failed = (int)$mFail[1];
            $skipped = preg_match('/Pruebas\s+omitidas[^\d:]*:\s*(\d+)/i', (string)$cleanOutput, $mSkip) ? (int)$mSkip[1] : 0;
            $hasSummary = true;
        } else {
            $passed = preg_match_all('/(✔\s*\[PASS\]|^\s*PASS\s+)/um', (string)$cleanOutput);
            $failed = preg_match_all('/(✖\s*\[FAIL\]|^\s*FAIL\s+)/um', (string)$cleanOutput);
            $skipped = preg_match_all('/((⚡|↷)\s*\[SKIP\]|^\s*SKIP\s+)/um', (string)$cleanOutput);

            if (preg_match('/Pasados:\s*(\d+)\s*\|\s*Fallados:\s*(\d+)/i', (string)$cleanOutput, $mRes)) {
                $passed = max($passed, (int)$mRes[1]);
                $failed = max($failed, (int)$mRes[2]);
                $hasSummary = true;
            }
        }

        // Detectar texto indicativo de fallo explícito
        $hasExplicitFailText = false;
        if (
            preg_match('/(?:✖\s*\[FAIL\]|Fallados:\s*[1-9]\d*|Pruebas\s+fallidas[^\d:]*:\s*[1-9]\d*|SE ENCONTRARON FALLOS|Fatal error:|Parse error:|✗\s+Algunos tests fallaron)/i', (string)$cleanOutput) ||
            preg_match('/^\s*FAIL\s+/m', (string)$cleanOutput)
        ) {
            $hasExplicitFailText = true;
        }

        $calculatedTotal = $passed + $failed + $skipped;

        if ($exitCode !== 0) {
            return [
                'status'  => 'FAIL',
                'passed'  => $passed,
                'failed'  => max(1, $failed),
                'skipped' => $skipped,
                'reason'  => "El subproceso finalizó con código de salida {$exitCode}"
            ];
        }

        if ($hasExplicitFailText && $failed === 0) {
            return [
                'status'  => 'FAIL',
                'passed'  => $passed,
                'failed'  => 1,
                'skipped' => $skipped,
                'reason'  => "Se detectaron indicadores de fallo en la salida de la suite"
            ];
        }

        if ($hasSummary && $declaredTotal !== null && $declaredTotal !== $calculatedTotal) {
            return [
                'status'  => 'FAIL',
                'passed'  => $passed,
                'failed'  => max(1, $failed),
                'skipped' => $skipped,
                'reason'  => "Inconsistencia en total de pruebas: declarado {$declaredTotal} vs calculado {$calculatedTotal} (PASS: {$passed}, FAIL: {$failed}, SKIP: {$skipped})"
            ];
        }

        if ($calculatedTotal === 0) {
            if (!($hasSummary && $declaredTotal === 0)) {
                return [
                    'status'  => 'FAIL',
                    'passed'  => 0,
                    'failed'  => 1,
                    'skipped' => 0,
                    'reason'  => "Cero métricas reconocidas en la salida de la suite sin declaración explícita de 0 pruebas"
                ];
            }
        }

        if ($failed > 0) {
            return [
                'status'  => 'FAIL',
                'passed'  => $passed,
                'failed'  => $failed,
                'skipped' => $skipped,
                'reason'  => "La suite reportó {$failed} pruebas fallidas"
            ];
        }

        return [
            'status'  => 'PASS',
            'passed'  => $passed,
            'failed'  => 0,
            'skipped' => $skipped,
            'reason'  => "PASS: {$passed}" . ($skipped > 0 ? ", SKIP: {$skipped}" : "")
        ];
    }

    private function testParserSuiteHandling(): void {
        echo "\n--- 11. Pruebas Exhaustivas del Parser de Suites de Prueba ---\n";

        // Caso 1: Formato individual [PASS]/[FAIL]/[SKIP]
        $rawOut1 = "  ✔ [PASS] Test uno\n  ✔ [PASS] Test dos\n  ⚡ [SKIP] Test tres -> omitido\n";
        $res1 = $this->parseMetrics($rawOut1, 0, 'Test1.php');
        $this->assert($res1['status'] === 'PASS' && $res1['passed'] === 2 && $res1['skipped'] === 1, "Parser reconoce formato [PASS]/[FAIL]/[SKIP] (2 PASS, 1 SKIP)");

        // Caso 2: Formato resumido con códigos ANSI
        $rawOut2 = "\e[32m  PASS\e[0m Test uno\n\e[32m  PASS\e[0m Test dos\nTests ejecutados: 2 | \e[32mPasados: 2\e[0m | \e[31mFallados: 0\e[0m | \e[33mOmitidos: 0\e[0m\n";
        $res2 = $this->parseMetrics($rawOut2, 0, 'Test2.php');
        $this->assert($res2['status'] === 'PASS' && $res2['passed'] === 2 && $res2['failed'] === 0, "Parser elimina códigos ANSI y procesa resumen correctamente (2 PASS)");

        // Caso 3: Formato SoftSkillsTest con 176 PASS y 5 SKIP
        $rawOut3 = "...\nTests ejecutados: 181 | \e[32mPasados: 176\e[0m | \e[31mFallados: 0\e[0m | \e[33mOmitidos: 5\e[0m\n\e[32m✓ Todos los tests pasaron.\e[0m\n";
        $res3 = $this->parseMetrics($rawOut3, 0, 'SoftSkillsTest.php');
        $this->assert($res3['status'] === 'PASS' && $res3['passed'] === 176 && $res3['skipped'] === 5, "Parser reconoce exactamente 176 PASS y 5 SKIP en formato SoftSkillsTest");

        // Caso 4: Suite con salida vacía y exit code 0 -> Debe fallar
        $res4 = $this->parseMetrics("", 0, 'EmptyTest.php');
        $this->assert($res4['status'] === 'FAIL', "Parser rechaza salida vacía con exit code 0 (FAIL por 0 métricas no declaradas)");

        // Caso 5: Total inconsistente (declarado 10, pero suma 8) -> Debe fallar
        $rawOut5 = "Tests ejecutados: 10 | Pasados: 5 | Fallados: 0 | Omitidos: 3\n";
        $res5 = $this->parseMetrics($rawOut5, 0, 'InconsistentTest.php');
        $this->assert($res5['status'] === 'FAIL' && str_contains($res5['reason'], 'Inconsistencia'), "Parser rechaza suite con total inconsistente (declarado 10 != suma 8)");

        // Caso 6: Exit code distinto de cero -> Debe fallar
        $rawOut6 = "Tests ejecutados: 5 | Pasados: 5 | Fallados: 0 | Omitidos: 0\n";
        $res6 = $this->parseMetrics($rawOut6, 1, 'NonZeroExitTest.php');
        $this->assert($res6['status'] === 'FAIL' && str_contains($res6['reason'], 'código de salida 1'), "Parser rechaza suite con exit code distinto de cero");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 12. Bloqueo por Archivo Crítico Ausente o Ilegible en Copia Temporal
    // ─────────────────────────────────────────────────────────────────────────
    private function testBlockingOnMissingOrUnreadableCriticalFileInTempCopy(): void {
        echo "\n--- 12. Bloqueo Obligatorio por Archivo Crítico Ausente o Ilegible (Copia Aislada) ---\n";

        $tempProject = $this->createTempProjectCopy();
        // Eliminar archivo crítico .htaccess de la copia aislada
        $criticalFile = $tempProject . '/.htaccess';
        if (file_exists($criticalFile)) {
            unlink($criticalFile);
        }

        $phpCmd = $this->getPhpCmd();
        $qg = $tempProject . '/artisan/quality-gate.php';
        $cmd = "{$phpCmd} " . escapeshellarg($qg) . " " . escapeshellarg($tempProject) . " 2>&1";
        exec($cmd, $out, $exitCode);

        $outText = implode("\n", $out);
        $this->assert($exitCode !== 0, "Quality Gate rechaza con código distinto de 0 ante archivo crítico ausente (.htaccess)");
        $this->assert(str_contains($outText, '.htaccess') || str_contains($outText, 'No se pudieron leer'), "Quality Gate identifica la ausencia o fallo de lectura del archivo crítico");

        $this->removeDir($tempProject);
    }

    private function printSummary(): void {
        $total = $this->passed + $this->failed + $this->skipped;
        echo "\n======================================================================\n";
        echo " RESUMEN: PRUEBAS DEL QUALITY GATE\n";
        echo "======================================================================\n";
        echo " Total tests : {$total}\n";
        echo " \033[32m✔ Pasados    : {$this->passed}\033[0m\n";
        echo " \033[33m⚡ Omitidos   : {$this->skipped}\033[0m\n";
        echo " \033[31m✖ Fallados   : {$this->failed}\033[0m\n";
        echo "======================================================================\n\n";

        if ($this->failed === 0) {
            echo "\033[32m✔ TODAS LAS PRUEBAS DEL QUALITY GATE PASARON AL 100%.\033[0m\n\n";
            exit(0);
        } else {
            echo "\033[31m✖ HUBO FALLOS EN LA SUITE DEL QUALITY GATE.\033[0m\n\n";
            exit(1);
        }
    }
}

$suite = new QualityGateTest();
$suite->runAll();

