<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * PMO SOLUTIONS — Módulo Email Outbox Resiliente
 * 
 * Gestiona el encolamiento transaccional, reintentos exponenciales y persistencia
 * de correos salientes tanto en MySQL como en almacenamiento local atómico con permisos 0600.
 */
class EmailOutbox {

    /**
     * Encola los correos de administración y constancia para un reclamo dentro de una transacción activa
     */
    public static function enqueueClaimEmails(PDO $pdo, string $idempKey, int $claimId, array $data): bool {
        $mailer = new SmtpMailer();
        $adminMail = $mailer->buildClaimAdminNotification($data);
        $userMail  = $mailer->buildClaimUserReceipt($data);

        $sql = "INSERT INTO email_outbox (
                    idempotency_key, type, reference_id, recipient_email, recipient_name,
                    subject, html_body, text_body, status, next_retry_at, created_at, updated_at
                ) VALUES (
                    :idempotency_key, :type, :reference_id, :recipient_email, :recipient_name,
                    :subject, :html_body, :text_body, 'pending', NOW(), NOW(), NOW()
                )";

        $stmt = $pdo->prepare($sql);

        // 1. Correo al administrador
        $stmt->execute([
            ':idempotency_key' => $idempKey,
            ':type'            => 'claim_admin',
            ':reference_id'    => $claimId,
            ':recipient_email' => $adminMail['to_email'],
            ':recipient_name'  => $adminMail['to_name'],
            ':subject'         => $adminMail['subject'],
            ':html_body'       => $adminMail['html_body'],
            ':text_body'       => $adminMail['text_body'] ?? ''
        ]);

        // 2. Correo de constancia al usuario reclamante
        $stmt->execute([
            ':idempotency_key' => $idempKey,
            ':type'            => 'claim_user',
            ':reference_id'    => $claimId,
            ':recipient_email' => $userMail['to_email'],
            ':recipient_name'  => $userMail['to_name'],
            ':subject'         => $userMail['subject'],
            ':html_body'       => $userMail['html_body'],
            ':text_body'       => $userMail['text_body'] ?? ''
        ]);

        return true;
    }

    /**
     * Encola el correo de contacto en MySQL o en archivo atómico si la BD no está disponible
     */
    public static function enqueueContactEmail(?PDO $pdo, string $idempKey, ?int $contactId, array $data, ?string $fingerprint = null): bool {
        $mailer = new SmtpMailer();
        $mailData = $mailer->buildContactNotification($data);
        $fp = $fingerprint ?? Idempotency::computeFingerprint($data);

        // Intentar encolar en MySQL si hay conexión PDO
        if ($pdo instanceof PDO) {
            try {
                $sql = "INSERT INTO email_outbox (
                            idempotency_key, type, reference_id, recipient_email, recipient_name,
                            subject, html_body, text_body, status, next_retry_at, created_at, updated_at
                        ) VALUES (
                            :idempotency_key, 'contact_admin', :reference_id, :recipient_email, :recipient_name,
                            :subject, :html_body, :text_body, 'pending', NOW(), NOW(), NOW()
                        )";

                $stmt = $pdo->prepare($sql);
                return $stmt->execute([
                    ':idempotency_key' => $idempKey,
                    ':reference_id'    => $contactId,
                    ':recipient_email' => $mailData['to_email'],
                    ':recipient_name'  => $mailData['to_name'],
                    ':subject'         => $mailData['subject'],
                    ':html_body'       => $mailData['html_body'],
                    ':text_body'       => $mailData['text_body'] ?? ''
                ]);
            } catch (PDOException $e) {
                // Manejar colisión de clave única (carreras concurrentes)
                if ((int)$e->errorInfo[1] === 1062 || $e->getCode() === '23000') {
                    return true;
                }
                error_log("[EmailOutbox enqueueContact DB Error] " . self::sanitizeError($e->getMessage()));
            }
        }

        // Fallback a almacenamiento seguro en archivo
        return self::enqueueToFile('contact', $idempKey, [
            'type'                => 'contact_admin',
            'idempotency_key'     => $idempKey,
            'payload_fingerprint' => $fp,
            'contact_id'          => $contactId,
            'mail_data'           => $mailData,
            'form_data'           => $data,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
            'next_retry_at'       => date('Y-m-d H:i:s'),
            'attempts'            => 0,
            'max_attempts'        => 5,
            'status'              => 'pending'
        ]);
    }

    /**
     * Guarda un elemento en el directorio de outbox de archivos con creación exclusiva y permisos 0600
     */
    public static function enqueueToFile(string $prefix, string $idempKey, array $payload): bool {
        $outboxDir = dirname(__DIR__, 2) . '/storage/outbox';
        if (!is_dir($outboxDir)) {
            if (!@mkdir($outboxDir, 0750, true)) {
                return false;
            }
        }

        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $idempKey);
        $filePath = $outboxDir . "/{$prefix}_{$safeKey}.json";

        // Si ya existe el archivo final o está en proceso, es idempotente
        if (file_exists($filePath) || file_exists($filePath . '.processing')) {
            return true;
        }

        // Intentar creación exclusiva con 'x'
        $fp = @fopen($filePath, 'x');
        if ($fp === false) {
            // Comprobar la causa real del fallo
            if (file_exists($filePath)) {
                // Se creó simultáneamente entre la comprobación y el fopen
                return true;
            }
            // Fallo real de almacenamiento (permisos o disco lleno)
            return false;
        }

        $jsonContent = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $written = fwrite($fp, $jsonContent);
        fflush($fp);
        fclose($fp);

        if ($written === false) {
            @unlink($filePath);
            return false;
        }

        // Aplicar permisos 0600 estrictos
        @chmod($filePath, 0600);
        return true;
    }

    /**
     * Actualiza un archivo de outbox de manera atómica usando archivo temporal
     */
    public static function updateFileAtomically(string $filePath, array $data): bool {
        $outboxDir = dirname($filePath);
        $tempFile = $outboxDir . '/tmp_' . bin2hex(random_bytes(8)) . '.tmp';

        $fp = @fopen($tempFile, 'w');
        if ($fp === false) {
            return false;
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        fclose($fp);

        @chmod($tempFile, 0600);
        return @rename($tempFile, $filePath);
    }

    /**
     * Procesa los correos pendientes en la cola outbox (DB y archivos) con adquisición atómica
     */
    public static function processPending(int $limit = 20): array {
        $results = [
            'db_processed'    => 0,
            'db_sent'         => 0,
            'db_failed'       => 0,
            'files_processed' => 0,
            'files_sent'      => 0,
            'files_failed'    => 0
        ];

        $mailer = new SmtpMailer();

        // 1. Procesar outbox en MySQL si está disponible
        $pdo = Database::getConnection();

        if ($pdo instanceof PDO) {
            // Auto-recuperación de registros atascados en 'processing' por más de 5 minutos
            try {
                $pdo->exec("UPDATE email_outbox 
                            SET status = 'pending', updated_at = NOW() 
                            WHERE status = 'processing' 
                              AND updated_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
            } catch (PDOException $e) {
                // Silencioso
            }

            // Obtener registros pendientes listos para envío
            try {
                $sql = "SELECT id, idempotency_key, type, recipient_email, recipient_name,
                               subject, html_body, text_body, attempts, max_attempts
                        FROM email_outbox
                        WHERE status = 'pending'
                          AND (next_retry_at IS NULL OR next_retry_at <= NOW())
                          AND attempts < max_attempts
                        ORDER BY id ASC
                        LIMIT :limit";

                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($items as $item) {
                    $results['db_processed']++;

                    // Adquisición atómica del estado 'processing'
                    $lockStmt = $pdo->prepare("UPDATE email_outbox 
                                              SET status = 'processing', updated_at = NOW() 
                                              WHERE id = :id AND status = 'pending'");
                    $lockStmt->execute([':id' => $item['id']]);

                    if ($lockStmt->rowCount() !== 1) {
                        // Otro proceso tomó el registro
                        continue;
                    }

                    $sent = $mailer->send(
                        $item['recipient_email'],
                        $item['recipient_name'],
                        $item['subject'],
                        $item['html_body'],
                        $item['text_body'] ?? ''
                    );

                    if ($sent) {
                        $results['db_sent']++;
                        $updStmt = $pdo->prepare("UPDATE email_outbox 
                                                  SET status = 'sent', sent_at = NOW(), last_error = NULL, updated_at = NOW() 
                                                  WHERE id = :id");
                        $updStmt->execute([':id' => $item['id']]);
                    } else {
                        $results['db_failed']++;
                        $attempts = (int)$item['attempts'] + 1;
                        $maxAttempts = (int)$item['max_attempts'];
                        $newStatus = ($attempts >= $maxAttempts) ? 'failed' : 'pending';
                        $delaySec = min(3600, max(60, (int)pow(2, $attempts) * 60));
                        $err = self::sanitizeError($mailer->getLastError() ?? 'SMTP Error');

                        $updStmt = $pdo->prepare("UPDATE email_outbox 
                                                  SET attempts = :attempts, status = :status, last_error = :err,
                                                      next_retry_at = DATE_ADD(NOW(), INTERVAL :delay SECOND),
                                                      updated_at = NOW() 
                                                  WHERE id = :id");
                        $updStmt->execute([
                            ':attempts' => $attempts,
                            ':status'   => $newStatus,
                            ':err'      => $err,
                            ':delay'    => $delaySec,
                            ':id'       => $item['id']
                        ]);
                    }
                }
            } catch (PDOException $e) {
                error_log("[EmailOutbox processPending DB Error] " . self::sanitizeError($e->getMessage()));
            }
        }

        // 2. Procesar outbox en archivos
        $outboxDir = dirname(__DIR__, 2) . '/storage/outbox';
        if (is_dir($outboxDir)) {
            // Recuperar archivos bloqueados en .processing por más de 5 minutos
            $stuckProcessing = glob($outboxDir . '/*.json.processing');
            if (is_array($stuckProcessing)) {
                foreach ($stuckProcessing as $stuck) {
                    if ((time() - filemtime($stuck)) > 300) {
                        $original = substr($stuck, 0, -11);
                        @rename($stuck, $original);
                    }
                }
            }

            $files = glob($outboxDir . '/*.json');
            if (is_array($files)) {
                $processedCount = 0;
                foreach ($files as $filePath) {
                    if ($processedCount >= $limit) {
                        break;
                    }

                    // Ignorar temporales
                    if (str_ends_with($filePath, '.tmp') || str_ends_with($filePath, '.processing')) {
                        continue;
                    }

                    // Adquisición atómica del archivo: renombrar a .processing
                    $processingPath = $filePath . '.processing';
                    if (!@rename($filePath, $processingPath)) {
                        // Otro worker adquirió o movió el archivo concurrentemente
                        continue;
                    }

                    $raw = @file_get_contents($processingPath);
                    if ($raw === false) {
                        @rename($processingPath, $filePath);
                        continue;
                    }

                    $item = json_decode($raw, true);
                    if (!is_array($item)) {
                        @unlink($processingPath);
                        continue;
                    }

                    // No reprocesar archivos marcados como 'failed'
                    if (($item['status'] ?? '') === 'failed') {
                        // Restaurar como failed y no procesar
                        @rename($processingPath, $filePath);
                        continue;
                    }

                    // Respetar next_retry_at si está en el futuro
                    if (!empty($item['next_retry_at'])) {
                        $nextRetryTime = strtotime($item['next_retry_at']);
                        if ($nextRetryTime > time()) {
                            // Aún no es momento de reintentar
                            @rename($processingPath, $filePath);
                            continue;
                        }
                    }

                    $processedCount++;
                    $results['files_processed']++;

                    $mailData = $item['mail_data'] ?? [];
                    if (empty($mailData['to_email']) || empty($mailData['subject'])) {
                        @unlink($processingPath);
                        continue;
                    }

                    $sent = $mailer->send(
                        $mailData['to_email'],
                        $mailData['to_name'] ?? '',
                        $mailData['subject'],
                        $mailData['html_body'] ?? '',
                        $mailData['text_body'] ?? '',
                        $mailData['reply_to_email'] ?? '',
                        $mailData['reply_to_name'] ?? ''
                    );

                    if ($sent) {
                        $results['files_sent']++;
                        @unlink($processingPath);
                    } else {
                        $results['files_failed']++;
                        $attempts = (int)($item['attempts'] ?? 0) + 1;
                        $maxAttempts = (int)($item['max_attempts'] ?? 5);
                        $delaySec = min(3600, max(60, (int)pow(2, $attempts) * 60));

                        $item['attempts'] = $attempts;
                        $item['last_error'] = self::sanitizeError($mailer->getLastError() ?? 'SMTP delivery failed');
                        $item['next_retry_at'] = date('Y-m-d H:i:s', time() + $delaySec);

                        if ($attempts >= $maxAttempts) {
                            $item['status'] = 'failed';
                        } else {
                            $item['status'] = 'pending';
                        }

                        // Guardar actualización atómicamente y liberar
                        self::updateFileAtomically($processingPath, $item);
                        @rename($processingPath, $filePath);
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Purga registros antiguos procesados y archivos vencidos/fallidos
     */
    public static function purgeOldRecords(?PDO $pdo = null, int $sentDays = 7, int $failedDays = 30): int {
        $deleted = 0;

        // 1. Purga en MySQL
        if (!($pdo instanceof PDO)) {
            $pdo = Database::getConnection();
        }

        if ($pdo instanceof PDO) {
            try {
                // Purgar enviados exitosos
                $stmt = $pdo->prepare("DELETE FROM email_outbox WHERE status = 'sent' AND sent_at < DATE_SUB(NOW(), INTERVAL :sentDays DAY)");
                $stmt->execute([':sentDays' => $sentDays]);
                $deleted += $stmt->rowCount();

                // Purgar fallidos definitivos
                $stmt2 = $pdo->prepare("DELETE FROM email_outbox WHERE status = 'failed' AND updated_at < DATE_SUB(NOW(), INTERVAL :failedDays DAY)");
                $stmt2->execute([':failedDays' => $failedDays]);
                $deleted += $stmt2->rowCount();
            } catch (PDOException $e) {
                error_log("[EmailOutbox purge DB Error] " . self::sanitizeError($e->getMessage()));
            }
        }

        // 2. Purga en almacenamiento por archivos
        $outboxDir = dirname(__DIR__, 2) . '/storage/outbox';
        if (is_dir($outboxDir)) {
            $files = glob($outboxDir . '/*.json');
            $now = time();
            $failedCutoff = $now - ($failedDays * 86400);

            if (is_array($files)) {
                foreach ($files as $filePath) {
                    $raw = @file_get_contents($filePath);
                    if ($raw === false) continue;
                    $item = json_decode($raw, true);
                    if (!is_array($item)) {
                        @unlink($filePath);
                        $deleted++;
                        continue;
                    }

                    if (($item['status'] ?? '') === 'failed') {
                        $fileTime = filemtime($filePath);
                        if ($fileTime < $failedCutoff) {
                            @unlink($filePath);
                            $deleted++;
                        }
                    }
                }
            }
        }

        return $deleted;
    }

    /**
     * Sanitiza mensajes de error para garantizar ZERO exposición de PII
     */
    public static function sanitizeError(string $error): string {
        // Redactar emails
        $sanitized = preg_replace('/[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+/', '[REDACTED_EMAIL]', $error);
        // Redactar teléfonos
        $sanitized = preg_replace('/\b(?:\+?51|01)?[98]\d{8}\b/', '[REDACTED_PHONE]', $sanitized);
        // Redactar sentencias SQL o datos sensibles
        $sanitized = preg_replace('/(password|clave|token|secret)\s*=\s*[^\s,]+/i', '$1=[REDACTED]', $sanitized);
        // Limitar tamaño
        if (mb_strlen($sanitized) > 500) {
            $sanitized = mb_substr($sanitized, 0, 497) . '...';
        }
        return trim($sanitized);
    }
}
