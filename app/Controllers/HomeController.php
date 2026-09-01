<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CourseModel;

/**
 * PMO SOLUTIONS - Controlador de Portada Principal (HomeController)
 */
class HomeController extends Controller {

    /**
     * Muestra la página principal de PMO Solutions
     */
    public function index(): void {
        $courseModel = new CourseModel();
        $courses = $courseModel->getAll();

        $this->render('home', [
            'pageTitle'       => 'PMO Solutions | Construimos Soluciones en Gestión de Proyectos de Construcción',
            'metaDescription' => 'PMO Solutions: Tu socio estratégico en consultoría, asesoría y capacitación de alta ingeniería en construcción (DAB NEC, Análisis Forense, Contratos NEC4, VDC-BIM).',
            'activeNav'       => 'home',
            'courses'         => $courses
        ]);
    }
}

