<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * PMO SOLUTIONS — Controlador de Páginas Legales e Institucionales (LegalController)
 * 
 * Gestiona el despliegue de Términos y Condiciones y Política de Privacidad.
 * Rutas públicas, fuera de cualquier contexto administrativo y sin requerir sesión activa.
 */
class LegalController extends Controller {

    /**
     * Muestra la página de Términos y Condiciones de Uso
     */
    public function terminos(): void {
        $this->render('terminos-y-condiciones', [
            'pageTitle'       => 'Términos y Condiciones de Uso | PMO Solutions',
            'metaDescription' => 'Términos y Condiciones de Uso de la plataforma digital y servicios formativos y de consultoría de PMO Solutions.',
            'activeNav'       => 'terminos'
        ]);
    }

    /**
     * Muestra la página de Política de Privacidad y Protección de Datos Personales
     */
    public function privacidad(): void {
        $this->render('politica-de-privacidad', [
            'pageTitle'       => 'Política de Privacidad y Protección de Datos | PMO Solutions',
            'metaDescription' => 'Política de Privacidad y Tratamiento de Datos Personales de PMO Solutions conforme a la legislación aplicable.',
            'activeNav'       => 'privacidad'
        ]);
    }
}

