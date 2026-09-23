<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * PMO SOLUTIONS - Controlador de Errores y Páginas No Encontradas (ErrorController)
 */
class ErrorController extends Controller {

    /**
     * Muestra la página 404 interactiva o emite respuesta JSON para endpoints de API
     */
    public function notFound(): void {
        http_response_code(404);

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

        $isApi = str_starts_with($uri, '/api/') || $isAjax;
        $wantsJson = stripos($accept, 'application/json') !== false && stripos($accept, 'text/html') === false;

        if ($isApi || $wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Ruta o recurso no encontrado (404).'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->render('404', [
            'pageTitle'       => '404: Desvío en la Ruta Crítica | PMO Solutions',
            'metaDescription' => 'Error 404 - Página no encontrada. Ocurrió un desvío no planificado en la obra.',
            'activeNav'       => ''
        ], 'error');
    }
}

