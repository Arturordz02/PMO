<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * PMO SOLUTIONS - Controlador de Errores y Páginas No Encontradas (ErrorController)
 */
class ErrorController extends Controller {

    /**
     * Muestra la página 404 interactiva con temática de ingeniería y construcción
     */
    public function notFound(): void {
        http_response_code(404);
        $this->render('404', [
            'pageTitle'       => '404: Desvío en la Ruta Crítica | PMO Solutions',
            'metaDescription' => 'Error 404 - Página no encontrada. Ocurrió un desvío no planificado en la obra.',
            'activeNav'       => ''
        ], 'error');
    }

    /**
     * Muestra la página 500 para errores internos o excepciones capturadas
     */
    public function serverError(): void {
        http_response_code(500);
        $this->render('500', [
            'pageTitle'       => '500: Evento Imprevisto en la Obra | PMO Solutions',
            'metaDescription' => 'Error 500 - Error interno del servidor.',
            'activeNav'       => ''
        ], 'error');
    }
}

