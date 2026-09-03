<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * PMO SOLUTIONS - Controlador Legal e Institucional (LegalController)
 * 
 * Gestiona el despliegue de páginas legales, normativas y de cumplimiento
 * como Términos y Condiciones, Políticas de Privacidad y avisos legales.
 */
class LegalController extends Controller {

    /**
     * Muestra la página de Términos y Condiciones
     */
    public function terms(): void {
        $this->render('terminos-y-condiciones', [
            'pageTitle'       => 'Términos y Condiciones | PMO Solutions',
            'metaDescription' => 'Términos y Condiciones de uso del sitio web y contratación de servicios de consultoría y capacitación técnica especializada de PMO Solutions.',
            'activeNav'       => 'terminos'
        ]);
    }
}

