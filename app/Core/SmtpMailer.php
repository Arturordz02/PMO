<?php
namespace App\Core;

use Exception;

/**
 * PMO SOLUTIONS - Cliente SMTP Nativo y Generador de Plantillas de Correo
 * 
 * Implementa comunicación SMTP por sockets (TLS/SSL con STARTTLS y AUTH LOGIN)
 * en PHP nativo sin requerir librerías externas o Composer.
 */
class SmtpMailer {
    private array $config;
    private array $appConfig;
    private ?string $lastError = null;

    public function __construct(?array $smtpConfig = null, ?array $appConfig = null) {
        if ($smtpConfig === null || $appConfig === null) {
            $configFile = dirname(__DIR__) . '/Config/config.php';
            $globalConfig = file_exists($configFile) ? require $configFile : [];
            $this->config = $smtpConfig ?? ($globalConfig['smtp'] ?? []);
            $this->appConfig = $appConfig ?? ($globalConfig['app'] ?? []);
        } else {
            $this->config = $smtpConfig;
            $this->appConfig = $appConfig;
        }
    }

    public function getLastError(): ?string {
        return $this->lastError;
    }

    /**
     * Envía un correo electrónico mediante conexión SMTP por socket
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        string $replyToEmail = '',
        string $replyToName = ''
    ): bool {
        $host       = $this->config['host'] ?? 'localhost';
        $port       = (int)($this->config['port'] ?? 587);
        $encryption = strtolower($this->config['encryption'] ?? 'tls');
        $auth       = !empty($this->config['auth']);
        $username   = $this->config['username'] ?? '';
        $password   = $this->config['password'] ?? '';
        $fromEmail  = $this->config['from_email'] ?? $username;
        $fromName   = $this->config['from_name'] ?? 'PMO Solutions';
        $timeout    = (int)($this->config['timeout'] ?? 15);

        if (empty($textBody)) {
            $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));
        }

        $socketHost = $host;
        if ($encryption === 'ssl') {
            $socketHost = 'ssl://' . $host;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);

        $socket = @stream_socket_client(
            $socketHost . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            $this->lastError = "No se pudo conectar al servidor SMTP ({$host}:{$port}) - {$errstr} ({$errno})";
            return $this->sendNativeMailFallback($toEmail, $toName, $subject, $htmlBody, $replyToEmail, $fromEmail, $fromName);
        }

        stream_set_timeout($socket, $timeout);

        try {
            $this->expectResponse($socket, 220);

            $heloHost = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
            $this->sendCommand($socket, "EHLO {$heloHost}");
            $this->expectResponse($socket, 250);

            if ($encryption === 'tls') {
                $this->sendCommand($socket, "STARTTLS");
                $this->expectResponse($socket, 220);

                $cryptoType = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $cryptoType |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $cryptoType |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }

                if (!@stream_socket_enable_crypto($socket, true, $cryptoType)) {
                    throw new Exception("Fallo en la negociación TLS con el servidor SMTP.");
                }

                $this->sendCommand($socket, "EHLO {$heloHost}");
                $this->expectResponse($socket, 250);
            }

            if ($auth) {
                $this->sendCommand($socket, "AUTH LOGIN");
                $this->expectResponse($socket, 334);

                $this->sendCommand($socket, base64_encode($username));
                $this->expectResponse($socket, 334);

                $this->sendCommand($socket, base64_encode($password));
                $this->expectResponse($socket, 235);
            }

            $this->sendCommand($socket, "MAIL FROM:<{$fromEmail}>");
            $this->expectResponse($socket, 250);

            $this->sendCommand($socket, "RCPT TO:<{$toEmail}>");
            $this->expectResponse($socket, [250, 251]);

            $this->sendCommand($socket, "DATA");
            $this->expectResponse($socket, 354);

            $boundary = "==_PMO_MIME_BOUND_" . md5(uniqid((string)time(), true));
            $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
            $encodedFromName = "=?UTF-8?B?" . base64_encode($fromName) . "?=";
            $encodedToName = !empty($toName) ? "=?UTF-8?B?" . base64_encode($toName) . "?=" : '';

            $headers = [];
            $headers[] = "From: {$encodedFromName} <{$fromEmail}>";
            $headers[] = !empty($encodedToName) ? "To: {$encodedToName} <{$toEmail}>" : "To: <{$toEmail}>";
            if (!empty($replyToEmail)) {
                $encodedReplyName = !empty($replyToName) ? "=?UTF-8?B?" . base64_encode($replyToName) . "?=" : '';
                $headers[] = !empty($encodedReplyName) ? "Reply-To: {$encodedReplyName} <{$replyToEmail}>" : "Reply-To: <{$replyToEmail}>";
            }
            $headers[] = "Subject: {$encodedSubject}";
            $headers[] = "Date: " . date('r');
            $headers[] = "Message-ID: <" . md5(uniqid((string)microtime(), true)) . "@" . ($heloHost ?: 'pmo-solutions.com') . ">";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
            $headers[] = "X-Mailer: PMO Solutions Mailer v1.0";

            $messageBody = implode("\r\n", $headers) . "\r\n\r\n";

            $messageBody .= "--{$boundary}\r\n";
            $messageBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $messageBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $messageBody .= chunk_split(base64_encode($textBody)) . "\r\n";

            $messageBody .= "--{$boundary}\r\n";
            $messageBody .= "Content-Type: text/html; charset=UTF-8\r\n";
            $messageBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $messageBody .= chunk_split(base64_encode($htmlBody)) . "\r\n";

            $messageBody .= "--{$boundary}--\r\n";
            $messageBody .= "\r\n.";

            $this->sendCommand($socket, $messageBody);
            $this->expectResponse($socket, 250);

            $this->sendCommand($socket, "QUIT");
            fclose($socket);

            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            @fclose($socket);
            return $this->sendNativeMailFallback($toEmail, $toName, $subject, $htmlBody, $replyToEmail, $fromEmail, $fromName);
        }
    }

    private function sendNativeMailFallback(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $replyToEmail,
        string $fromEmail,
        string $fromName
    ): bool {
        $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $encodedFromName = "=?UTF-8?B?" . base64_encode($fromName) . "?=";

        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";
        $headers[] = "From: {$encodedFromName} <{$fromEmail}>";
        if (!empty($replyToEmail)) {
            $headers[] = "Reply-To: {$replyToEmail}";
        }
        $headers[] = "X-Mailer: PHP/" . phpversion();

        $toHeader = !empty($toName) ? "=?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>" : $toEmail;

        $sent = @mail($toHeader, $encodedSubject, $htmlBody, implode("\r\n", $headers));
        if (!$sent && $this->lastError === null) {
            $this->lastError = "No se pudo enviar el correo ni por SMTP ni mediante la función nativa mail().";
        }
        return (bool)$sent;
    }

    private function sendCommand($socket, string $command): void {
        fwrite($socket, $command . "\r\n");
    }

    private function expectResponse($socket, $expectedCodes): string {
        if (!is_array($expectedCodes)) {
            $expectedCodes = [$expectedCodes];
        }

        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new Exception("Error SMTP ({$code}): " . trim($response));
        }

        return $response;
    }

    public function sendContactNotification(array $data): bool {
        $adminEmail = $this->config['admin_email'] ?? 'comercial@pmo-solutions.com';
        $adminName  = $this->config['admin_name'] ?? 'Administración PMO Solutions';
        $siteUrl    = $this->appConfig['site_url'] ?? 'https://pmo-solutions.com';
        $subject    = "Nueva Consulta Web: " . ($data['servicio'] ?? 'General') . " - " . ($data['nombre'] ?? '');

        $html = "
<!DOCTYPE html>
<html lang='es'>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
    .container { max-width: 620px; background: #ffffff; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #0A192F 0%, #00509E 100%); color: #ffffff; padding: 28px 30px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
    .badge { background: #FF5722; color: #ffffff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; margin-top: 8px; }
    .content { padding: 30px; }
    .field-row { margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }
    .field-label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .field-value { font-size: 15px; color: #0f172a; font-weight: 600; }
    .message-box { background: #f8fafc; border-left: 4px solid #00509E; padding: 16px; border-radius: 6px; font-size: 14px; line-height: 1.6; color: #334155; margin-top: 10px; }
    .footer { background: #0f172a; color: #94a3b8; text-align: center; padding: 20px; font-size: 12px; }
    .btn-action { display: inline-block; background: #25D366; color: #ffffff !important; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; margin-top: 15px; }
  </style>
</head>
<body>
  <div class='container'>
    <div class='header'>
      <h1>PMO SOLUTIONS</h1>
      <span class='badge'>Nueva Consulta Comercial</span>
    </div>
    <div class='content'>
      <p style='font-size: 15px; margin-top: 0;'>Se ha recibido una nueva solicitud de información desde el formulario de contacto web:</p>
      
      <div class='field-row'>
        <div class='field-label'>Nombre Completo</div>
        <div class='field-value'>" . htmlspecialchars($data['nombre']) . "</div>
      </div>

      <div class='field-row'>
        <div class='field-label'>Teléfono / WhatsApp</div>
        <div class='field-value'>
          <a href='https://wa.me/" . preg_replace('/\D/', '', $data['telefono']) . "' style='color: #00509E; text-decoration: none;'>
            " . htmlspecialchars($data['telefono']) . "
          </a>
        </div>
      </div>

      <div class='field-row'>
        <div class='field-label'>Correo Electrónico</div>
        <div class='field-value'>
          <a href='mailto:" . htmlspecialchars($data['email']) . "' style='color: #00509E; text-decoration: none;'>
            " . htmlspecialchars($data['email']) . "
          </a>
        </div>
      </div>

      <div class='field-row'>
        <div class='field-label'>Servicio o Capacitación de Interés</div>
        <div class='field-value' style='color: #00509E;'>" . htmlspecialchars($data['servicio']) . "</div>
      </div>

      <div class='field-row' style='border-bottom: none;'>
        <div class='field-label'>Detalle de la Consulta / Comentario</div>
        <div class='message-box'>" . nl2br(htmlspecialchars($data['mensaje'])) . "</div>
      </div>

      <div style='text-align: center; margin-top: 25px;'>
        <a href='https://wa.me/" . preg_replace('/\D/', '', $data['telefono']) . "?text=Hola%20" . urlencode($data['nombre']) . ",%20te%20saludamos%20de%20PMO%20Solutions' class='btn-action' target='_blank'>
          Contactar por WhatsApp
        </a>
      </div>
    </div>
    <div class='footer'>
      <p style='margin: 0;'>Notificación automática del sitio web <a href='{$siteUrl}' style='color: #FF5722; text-decoration: none;'>PMO Solutions</a>.</p>
      <p style='margin: 4px 0 0 0;'>Fecha y hora: " . date('d/m/Y H:i:s') . " (PET)</p>
    </div>
  </div>
</body>
</html>";

        return $this->send(
            $adminEmail,
            $adminName,
            $subject,
            $html,
            '',
            $data['email'],
            $data['nombre']
        );
    }

    public function sendClaimAdminNotification(array $data): bool {
        $adminEmail = $this->config['admin_email'] ?? 'comercial@pmo-solutions.com';
        $adminName  = $this->config['admin_name'] ?? 'Administración PMO Solutions';
        $code       = $data['codigo_reclamacion'] ?? 'REC-2026';
        $tipoReg    = $data['tipo_registro'] ?? 'Reclamo';
        $subject    = "[{$tipoReg} Virtual - {$code}] Hoja de Reclamación: " . ($data['nombre_completo'] ?? '');

        $html = "
<!DOCTYPE html>
<html lang='es'>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
    .container { max-width: 680px; background: #ffffff; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    .header { background: linear-gradient(135deg, #0A192F 0%, #B71C1C 100%); color: #ffffff; padding: 28px 30px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; }
    .code-badge { background: #FFC107; color: #000000; padding: 6px 14px; border-radius: 20px; font-size: 14px; font-weight: 800; display: inline-block; margin-top: 10px; }
    .section-title { font-size: 14px; font-weight: 700; color: #00509E; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; margin: 24px 0 12px 0; text-transform: uppercase; }
    .content { padding: 30px; }
    .grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .grid td { padding: 8px 10px; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .grid .label { width: 35%; color: #64748b; font-weight: 600; }
    .grid .val { width: 65%; color: #0f172a; font-weight: 700; }
    .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; font-size: 14px; line-height: 1.6; margin-bottom: 14px; }
    .footer { background: #0f172a; color: #94a3b8; text-align: center; padding: 20px; font-size: 12px; }
    .alert-indecopi { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; padding: 12px; border-radius: 6px; font-size: 13px; margin-top: 20px; }
  </style>
</head>
<body>
  <div class='container'>
    <div class='header'>
      <h1>LIBRO DE RECLAMACIONES VIRTUAL</h1>
      <div class='code-badge'>CÓDIGO: {$code} - {$tipoReg}</div>
    </div>
    <div class='content'>
      <div class='alert-indecopi'>
        <strong>Plazo Legal de Atención:</strong> Según el D.S. N° 011-2011-PCM (INDECOPI), PMO Solutions debe brindar respuesta formal en un plazo máximo de <strong>15 días hábiles</strong>.
      </div>

      <div class='section-title'>1. Datos del Reclamante</div>
      <table class='grid'>
        <tr><td class='label'>Tipo y N° Documento:</td><td class='val'>" . htmlspecialchars($data['tipo_documento']) . ": " . htmlspecialchars($data['numero_documento']) . "</td></tr>
        <tr><td class='label'>Nombres / Razón Social:</td><td class='val'>" . htmlspecialchars($data['nombre_completo']) . "</td></tr>
        <tr><td class='label'>Teléfono / WhatsApp:</td><td class='val'>" . htmlspecialchars($data['telefono']) . "</td></tr>
        <tr><td class='label'>Correo Electrónico:</td><td class='val'><a href='mailto:" . htmlspecialchars($data['email']) . "'>" . htmlspecialchars($data['email']) . "</a></td></tr>
        <tr><td class='label'>Domicilio:</td><td class='val'>" . htmlspecialchars($data['domicilio']) . "</td></tr>
      </table>

      <div class='section-title'>2. Identificación del Servicio Contratado</div>
      <table class='grid'>
        <tr><td class='label'>Tipo de Contratación:</td><td class='val'>" . htmlspecialchars($data['tipo_servicio']) . "</td></tr>
        <tr><td class='label'>Nombre del Servicio/Curso:</td><td class='val'>" . htmlspecialchars($data['nombre_servicio']) . "</td></tr>
        <tr><td class='label'>Detalles / Matrícula:</td><td class='val'>" . (!empty($data['detalle_servicio']) ? htmlspecialchars($data['detalle_servicio']) : 'No especificado') . "</td></tr>
      </table>

      <div class='section-title'>3. Detalle de la Reclamación</div>
      <p style='margin: 0 0 6px 0; font-weight: 700; color: #475569; font-size: 13px;'>Hechos Ocurridos:</p>
      <div class='box'>" . nl2br(htmlspecialchars($data['detalle_reclamacion'])) . "</div>

      <p style='margin: 12px 0 6px 0; font-weight: 700; color: #475569; font-size: 13px;'>Pedido Concreto del Consumidor:</p>
      <div class='box' style='border-left: 4px solid #FF5722;'>" . nl2br(htmlspecialchars($data['pedido_consumidor'])) . "</div>
    </div>
    <div class='footer'>
      <p style='margin: 0;'>PMO SOLUTIONS S.A.C. - Sistema de Gestión del Libro de Reclamaciones Virtual</p>
      <p style='margin: 4px 0 0 0;'>Fecha de Registro: " . date('d/m/Y H:i:s') . "</p>
    </div>
  </div>
</body>
</html>";

        return $this->send(
            $adminEmail,
            $adminName,
            $subject,
            $html,
            '',
            $data['email'],
            $data['nombre_completo']
        );
    }

    public function sendClaimUserReceipt(array $data): bool {
        $code     = $data['codigo_reclamacion'] ?? 'REC-2026';
        $tipoReg  = $data['tipo_registro'] ?? 'Reclamo';
        $subject  = "Constancia de {$tipoReg} Virtual - PMO Solutions [{$code}]";
        $siteUrl  = $this->appConfig['site_url'] ?? 'https://pmo-solutions.com';

        $html = "
<!DOCTYPE html>
<html lang='es'>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #1e293b; }
    .voucher { max-width: 640px; background: #ffffff; margin: 0 auto; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
    .v-header { background: linear-gradient(135deg, #0A192F 0%, #00509E 100%); color: #ffffff; padding: 30px; text-align: center; }
    .v-header h1 { margin: 0 0 4px 0; font-size: 22px; }
    .v-header p { margin: 0; color: #cbd5e1; font-size: 13px; }
    .code-box { background: #FFC107; color: #0A192F; padding: 10px 20px; border-radius: 8px; font-weight: 900; font-size: 18px; margin: 15px auto 0 auto; display: inline-block; letter-spacing: 1px; }
    .v-body { padding: 30px; }
    .notice { background: #eff6ff; border-left: 4px solid #00509E; padding: 14px; border-radius: 6px; font-size: 13px; line-height: 1.6; color: #1e40af; margin-bottom: 24px; }
    .v-section { margin-bottom: 20px; }
    .v-section-title { font-size: 13px; font-weight: 800; color: #00509E; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px; }
    .v-grid { width: 100%; border-collapse: collapse; }
    .v-grid td { padding: 6px 0; font-size: 13px; }
    .v-grid .lbl { width: 40%; color: #64748b; font-weight: 600; }
    .v-grid .txt { width: 60%; color: #0f172a; font-weight: 700; }
    .detail-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; font-size: 13px; line-height: 1.6; color: #334155; margin-top: 6px; }
    .v-footer { background: #0f172a; color: #94a3b8; text-align: center; padding: 20px; font-size: 12px; line-height: 1.6; }
  </style>
</head>
<body>
  <div class='voucher'>
    <div class='v-header'>
      <h1>PMO SOLUTIONS S.A.C.</h1>
      <p>Libro de Reclamaciones Virtual - Hoja de Registro</p>
      <div class='code-box'>CÓDIGO DE SEGUIMIENTO: {$code}</div>
    </div>
    
    <div class='v-body'>
      <p style='font-size: 15px; margin-top: 0;'>Estimado(a) <strong>" . htmlspecialchars($data['nombre_completo']) . "</strong>,</p>
      <p style='font-size: 14px; line-height: 1.6;'>Confirmamos la recepción de su <strong>{$tipoReg}</strong> en nuestro Libro de Reclamaciones Virtual. A continuación, le remitimos la constancia con el resumen de los datos registrados:</p>

      <div class='notice'>
        <strong>Información Importante (INDECOPI):</strong><br>
        Conforme a la Ley N° 29571 y D.S. N° 011-2011-PCM, PMO Solutions dará respuesta a su requerimiento en un plazo no mayor a <strong>15 días hábiles</strong> a esta misma dirección de correo electrónico.
      </div>

      <div class='v-section'>
        <div class='v-section-title'>Datos del Consumidor Reclamante</div>
        <table class='v-grid'>
          <tr><td class='lbl'>Documento de Identidad:</td><td class='txt'>" . htmlspecialchars($data['tipo_documento']) . " - " . htmlspecialchars($data['numero_documento']) . "</td></tr>
          <tr><td class='lbl'>Nombres y Apellidos / Razón:</td><td class='txt'>" . htmlspecialchars($data['nombre_completo']) . "</td></tr>
          <tr><td class='lbl'>Teléfono / Celular:</td><td class='txt'>" . htmlspecialchars($data['telefono']) . "</td></tr>
          <tr><td class='lbl'>Domicilio:</td><td class='txt'>" . htmlspecialchars($data['domicilio']) . "</td></tr>
        </table>
      </div>

      <div class='v-section'>
        <div class='v-section-title'>Identificación del Servicio</div>
        <table class='v-grid'>
          <tr><td class='lbl'>Tipo de Contratación:</td><td class='txt'>" . htmlspecialchars($data['tipo_servicio']) . "</td></tr>
          <tr><td class='lbl'>Servicio / Capacitación:</td><td class='txt'>" . htmlspecialchars($data['nombre_servicio']) . "</td></tr>
        </table>
      </div>

      <div class='v-section'>
        <div class='v-section-title'>Detalle de los Hechos y Pedido</div>
        <p style='margin: 4px 0; font-size: 12px; font-weight: 700; color: #475569;'>Descripción del Reclamo/Queja:</p>
        <div class='detail-box'>" . nl2br(htmlspecialchars($data['detalle_reclamacion'])) . "</div>

        <p style='margin: 10px 0 4px 0; font-size: 12px; font-weight: 700; color: #475569;'>Pedido Concreto:</p>
        <div class='detail-box'>" . nl2br(htmlspecialchars($data['pedido_consumidor'])) . "</div>
      </div>

      <p style='font-size: 13px; color: #64748b; margin-bottom: 0;'>Guarde este correo como constancia de su reclamación con el código <strong>{$code}</strong>.</p>
    </div>

    <div class='v-footer'>
      <strong>PMO SOLUTIONS S.A.C.</strong><br>
      Av. Javier Prado 757, piso 10 Magdalena, Lima 17, Perú.<br>
      Correo de Atención: <a href='mailto:comercial@pmo-solutions.com' style='color: #FFC107;'>comercial@pmo-solutions.com</a> | WhatsApp: +51 944 276 649<br>
      <a href='{$siteUrl}' style='color: #cbd5e1; text-decoration: none;'>www.pmo-solutions.com</a>
    </div>
  </div>
</body>
</html>";

        return $this->send(
            $data['email'],
            $data['nombre_completo'],
            $subject,
            $html
        );
    }
}

