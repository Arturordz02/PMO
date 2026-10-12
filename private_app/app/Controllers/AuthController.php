<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;

/**
 * PMO SOLUTIONS — Controlador de Autenticación Administrativa
 * 
 * Gestiona el inicio y cierre de sesión de administradores,
 * control de rate limiting estricto (5 intentos / 15 min) y redirecciones seguras.
 */
class AuthController extends Controller {

    /**
     * GET /admin/login
     * Renderiza el formulario de inicio de sesión administrativo
     */
    public function showLogin(): void {
        if (Security::isAuthenticated()) {
            $this->redirect('/evaluacion-habilidades/resultados');
        }

        $this->render('admin-login', [
            'pageTitle'       => 'Acceso Administrativo | PMO Solutions',
            'metaDescription' => 'Panel de autenticación administrativa de PMO Solutions.',
            'activeNav'       => 'admin',
        ]);
    }

    /**
     * POST /admin/login
     * Procesa la solicitud de autenticación
     */
    public function login(): void {
        Security::initHeaders($this->config['security']['allowed_origins'] ?? []);
        Security::requireMethod('POST');

        // Rate limiting estricto: 5 intentos cada 15 minutos (900s) por IP
        $actionKey = 'login_' . Security::getClientIp();
        if (!Security::checkRateLimit($actionKey, 5, 900)) {
            $retryAfter = Security::getLastRetryAfter();
            header("Retry-After: {$retryAfter}");
            $this->json(false, "Demasiados intentos fallidos. Por favor espera {$retryAfter} segundos antes de volver a intentar.", [
                'retry_after' => $retryAfter
            ], 429);
        }

        $data = Security::getRequestData();

        if (Security::hasJsonSyntaxError()) {
            $this->json(false, 'Cuerpo de la solicitud JSON inválido o corrupto.', [], 400);
        }

        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if (empty($username) || empty($password)) {
            $this->json(false, 'Usuario y contraseña son requeridos.', [
                'errors' => [
                    'username' => empty($username) ? 'El usuario es obligatorio.' : null,
                    'password' => empty($password) ? 'La contraseña es obligatoria.' : null,
                ]
            ], 422);
        }

        $authenticated = Security::login($username, $password);

        if (!$authenticated) {
            $this->json(false, 'Credenciales incorrectas.', [], 401);
        }

        // Reseteo de rate limit tras login exitoso
        Security::resetRateLimit($actionKey);

        $this->json(true, 'Autenticación exitosa.', [
            'redirect' => '/evaluacion-habilidades/resultados'
        ], 200);
    }

    /**
     * POST /admin/logout
     * Cierra la sesión administrativa activa
     */
    public function logout(): void {
        Security::initHeaders($this->config['security']['allowed_origins'] ?? []);
        Security::requireMethod('POST');

        Security::logout();

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || stripos($contentType, 'application/json') !== false;

        if ($isAjax) {
            $this->json(true, 'Sesión cerrada correctamente.', [
                'redirect' => '/admin/login'
            ], 200);
        }

        $this->redirect('/admin/login');
    }
}

