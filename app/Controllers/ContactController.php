<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\ContactModel;

/**
 * PMO SOLUTIONS - Controlador de Contacto y Asesoría (ContactController)
 */
class ContactController extends Controller {

    /**
     * Muestra la vista del formulario de contacto
     */
    public function index(): void {
        $this->render('contacto', [
            'pageTitle'       => 'Contacto & Asesoría Corporativa | PMO Solutions',
            'metaDescription' => 'Ponte en contacto con PMO Solutions. Asesoría técnica, consultas sobre cursos in-house, peritajes, capacitaciones y servicios de consultoría en ingeniería.',
            'activeNav'       => 'contacto'
        ]);
    }

    /**
     * Procesa la solicitud de envío de contacto (AJAX / POST)
     */
    public function submit(): void {
        Security::requireMethod('POST');

        // Rate Limiting
        $securityConfig = $this->config['security'] ?? [];
        if (!empty($securityConfig['rate_limit_enabled'])) {
            $maxReq = (int)($securityConfig['rate_limit_requests'] ?? 10);
            $window = (int)($securityConfig['rate_limit_window'] ?? 300);

            if (!Security::checkRateLimit($maxReq, $window)) {
                $this->json(
                    false,
                    'Has superado el límite de solicitudes permitidas. Por favor, espera unos minutos o contáctanos directamente por WhatsApp.',
                    [],
                    429
                );
            }
        }

        $input = Security::getRequestData();

        // Verificación de Token CSRF (si fue provisto desde web o sesión activa)
        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!empty($csrfToken) && !Security::validateCsrfToken($csrfToken)) {
            Logger::warning('Intento de envío de formulario de contacto con token CSRF inválido', ['input' => $input]);
            $this->json(
                false,
                'La sesión del formulario expiró o el token de seguridad es inválido. Por favor, recarga la página.',
                [],
                403
            );
        }

        $contactModel = new ContactModel();
        $validation = $contactModel->validateAndSanitize($input);

        if (!empty($validation['errors'])) {
            Logger::info('Formulario de contacto rechazado por validación', ['errors' => $validation['errors']]);
            $this->json(
                false,
                'Por favor verifica los campos obligatorios del formulario.',
                ['errors' => $validation['errors']],
                422
            );
        }

        $data = $validation['data'];

        // Guardar en base de datos si está habilitada
        $saved = $contactModel->save($data);
        if ($saved) {
            Logger::info("Nuevo mensaje de contacto registrado para: {$data['nombre']} ({$data['email']})");
        }

        // Enviar correo de notificación
        $emailSent = $contactModel->sendEmail($data);

        if ($emailSent) {
            $this->json(
                true,
                '¡Gracias por comunicarte con PMO Solutions! Tu mensaje ha sido recibido con éxito. Uno de nuestros directores o asesores técnicos se pondrá en contacto a la brevedad.',
                ['data' => ['nombre' => $data['nombre']]]
            );
        } else {
            $this->json(
                true,
                'Tu mensaje fue registrado correctamente en nuestro sistema. Pronto nos pondremos en contacto contigo.',
                ['warning' => 'Notificación en cola']
            );
        }
    }
}

