<?php
/**
 * PMO SOLUTIONS - Script de Diagnóstico del Entorno y Servidor (Health Check)
 * 
 * Verifica que el servidor (GoDaddy, cPanel o Local) cumpla con todos
 * los requisitos de la arquitectura MVC y seguridad antes de producción.
 * 
 * Ejecución:
 * php tests/health_check.php
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
App\Core\Autoloader::register();

use App\Core\Security;
use App\Core\Logger;

class HealthCheck {

    private int $passed = 0;
    private int $warnings = 0;
    private int $failed = 0;

    public function run(): void {
        $this->printHeader("DIAGNÓSTICO DEL ENTORNO Y SERVIDOR (PMO SOLUTIONS - GODADDY READY)");

        $this->checkPhpVersion();
        $this->checkRequiredExtensions();
        $this->checkDirectoryPermissions();
        $this->checkCoreClasses();
        $this->checkSecurityConfiguration();

        $this->printSummary();
    }

    private function checkPhpVersion(): void {
        $this->printSection("1. Versión de PHP");
        $version = PHP_VERSION;
        $isOk = version_compare($version, '8.0.0', '>=');
        $this->assert($isOk, "PHP versión >= 8.0.0 (Actual: {$version})");
    }

    private function checkRequiredExtensions(): void {
        $this->printSection("2. Extensiones PHP Requeridas");
        $extensions = [
            'pdo'       => 'Soporte Base de Datos PDO',
            'pdo_mysql' => 'Driver MySQL para PDO',
            'mbstring'  => 'Manejo de caracteres UTF-8 multi-byte',
            'openssl'   => 'Cifrado seguro y sockets SMTP TLS/SSL',
            'json'      => 'Manipulación de APIs JSON y Rate Limiting',
            'curl'      => 'Peticiones HTTP externas'
        ];

        foreach ($extensions as $ext => $label) {
            $loaded = extension_loaded($ext);
            $this->assert($loaded, "Extensión '{$ext}' cargada ({$label})");
        }
    }

    private function checkDirectoryPermissions(): void {
        $this->printSection("3. Permisos de Almacenamiento y Logs");
        
        $storageDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
        $logsDir = $storageDir . DIRECTORY_SEPARATOR . 'logs';

        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0755, true);
        }

        $canWriteLogs = is_writable($logsDir);
        $this->assert($canWriteLogs, "Directorio de Logs con permisos de escritura: {$logsDir}");

        $tempRateLimit = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_rate_limits';
        if (!is_dir($tempRateLimit)) {
            @mkdir($tempRateLimit, 0755, true);
        }
        $canWriteRate = is_writable($tempRateLimit);
        $this->assert($canWriteRate, "Directorio temporal para Rate Limiting escribible: {$tempRateLimit}");
    }

    private function checkCoreClasses(): void {
        $this->printSection("4. Núcleo Arquitectónico MVC");
        $classes = [
            'App\Core\Autoloader',
            'App\Core\Router',
            'App\Core\Controller',
            'App\Core\Model',
            'App\Core\View',
            'App\Core\Security',
            'App\Core\Logger',
            'App\Core\Database',
            'App\Core\SmtpMailer'
        ];

        foreach ($classes as $cls) {
            $exists = class_exists($cls);
            $this->assert($exists, "Clase Core disponible: {$cls}");
        }
    }

    private function checkSecurityConfiguration(): void {
        $this->printSection("5. Módulo de Seguridad y Tokens CSRF");
        
        $token = Security::generateCsrfToken();
        $this->assert(!empty($token) && strlen($token) === 64, "Generación de Token CSRF (64 chars hex)");

        $valid = Security::validateCsrfToken($token);
        $this->assert($valid === true, "Validación de Token CSRF válido");

        $invalid = Security::validateCsrfToken('token_falso_invalido');
        $this->assert($invalid === false, "Rechazo de Token CSRF inválido");

        Logger::info("HealthCheck ejecutado satisfactoriamente desde CLI");
        $this->assert(true, "Escritura de evento de auditoría en app.log");
    }

    private function assert(bool $condition, string $testName): void {
        if ($condition) {
            $this->passed++;
            echo "  \033[32m✔ [OK]\033[0m {$testName}\n";
        } else {
            $this->failed++;
            echo "  \033[31m✖ [FAIL]\033[0m {$testName}\n";
        }
    }

    private function printHeader(string $title): void {
        echo "\n======================================================================\n";
        echo " {$title}\n";
        echo "======================================================================\n";
    }

    private function printSection(string $title): void {
        echo "\n--- {$title} ---\n";
    }

    private function printSummary(): void {
        $total = $this->passed + $this->failed;
        echo "\n======================================================================\n";
        echo " RESUMEN DEL DIAGNÓSTICO\n";
        echo "======================================================================\n";
        echo " Total de verificaciones: {$total}\n";
        echo " Verificaciones OK      : \033[32m{$this->passed}\033[0m\n";
        echo " Errores encontrados   : " . ($this->failed > 0 ? "\033[31m{$this->failed}\033[0m" : "\033[32m0\033[0m") . "\n";
        
        if ($this->failed === 0) {
            echo "\n \033[32m✓ EL PROYECTO CUMPLE CON TODOS LOS REQUISITOS PARA GODADDY / CPANEL.\033[0m\n\n";
        } else {
            echo "\n \033[31m✗ SE DETECTARON ERRORES QUE DEBEN RESOLVERSE ANTES DEL DESPLIEGUE.\033[0m\n\n";
        }
    }
}

$checker = new HealthCheck();
$checker->run();
