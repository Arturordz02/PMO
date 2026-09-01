<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\ClaimModel;

/**
 * PMO SOLUTIONS - Controlador del Libro de Reclamaciones (ClaimController)
 */
class ClaimController extends Controller {

    /**
     * Muestra el formulario del Libro de Reclamaciones Virtual
     */
    public function index(): void {
        $this->render('libro-de-reclamaciones', [
            'pageTitle'       => 'Libro de Reclamaciones Virtual | PMO Solutions',
            'metaDescription' => 'Libro de Reclamaciones Virtual de PMO Solutions conforme a la Ley N° 29571 (Código de Protección y Defensa del Consumidor) y directivas de INDECOPI.',
            'activeNav'       => 'reclamos'
        ]);
    }

    /**
     * Procesa el registro de la Hoja de Reclamación (AJAX / POST)
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
                    'Has superado el límite de solicitudes. Por favor espera unos minutos antes de volver a intentar.',
                    [],
                    429
                );
            }
        }

        $input = Security::getRequestData();
        $claimModel = new ClaimModel();
        $validation = $claimModel->validateAndSanitize($input);

        if (!empty($validation['errors'])) {
            $this->json(
                false,
                'Por favor completa todos los campos obligatorios del Libro de Reclamaciones.',
                ['errors' => $validation['errors']],
                422
            );
        }

        $data = $validation['data'];

        // Guardar en MySQL
        $claimModel->save($data);

        // Enviar correos (Administración y Constancia al Usuario)
        $claimModel->sendEmails($data);

        $this->json(
            true,
            "Su Hoja de {$data['tipo_registro']} ha sido registrada formalmente. Hemos enviado una copia en formato digital a su correo ({$data['email']}) con el código de seguimiento {$data['codigo_reclamacion']}.",
            [
                'codigo_reclamacion' => $data['codigo_reclamacion'],
                'tipo_registro'      => $data['tipo_registro'],
                'email'              => $data['email']
            ]
        );
    }
}

