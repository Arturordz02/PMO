<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CourseModel;

/**
 * PMO SOLUTIONS - Controlador de Cursos y Capacitaciones (CourseController)
 */
class CourseController extends Controller {

    private CourseModel $courseModel;

    public function __construct() {
        parent::__construct();
        $this->courseModel = new CourseModel();
    }

    /**
     * Muestra el catálogo general interactivo de capacitaciones
     */
    public function index(): void {
        $courses = $this->courseModel->getAll();

        $this->render('capacitaciones', [
            'pageTitle'       => 'Catálogo de Capacitaciones 2026 | PMO Solutions',
            'metaDescription' => 'Conoce nuestros programas de especialización ejecutiva e in-house en Contratos NEC4, Juntas de Resolución (DAB), Peritajes Forenses, Primavera P6 y VDC-BIM.',
            'activeNav'       => 'capacitaciones',
            'courses'         => $courses
        ]);
    }

    /**
     * Muestra la landing de un curso específico por su slug
     */
    public function detail(string $slug): void {
        $course = $this->courseModel->getBySlug($slug);

        if (!$course) {
            $errorController = new ErrorController();
            $errorController->notFound();
            return;
        }

        $this->render("courses/{$slug}", [
            'pageTitle'       => $course['title'] . ' | PMO Solutions',
            'metaDescription' => $course['description'],
            'activeNav'       => 'capacitaciones',
            'course'          => $course
        ]);
    }

    // Métodos directos para rutas individuales
    public function dabJrd(): void { $this->detail('dab-jrd'); }
    public function analisisForense(): void { $this->detail('analisis-forense'); }
    public function nec4(): void { $this->detail('nec4'); }
    public function vdcBim(): void { $this->detail('vdc-bim'); }
    public function contratosEstado(): void { $this->detail('contratos-estado'); }
    public function compliance(): void { $this->detail('compliance'); }
    public function primaveraP6(): void { $this->detail('primavera-p6'); }
    public function riesgosPmi(): void { $this->detail('riesgos-pmi'); }
    public function analisisCuantitativo(): void { $this->detail('analisis-cuantitativo'); }
    public function eventosCompensables(): void { $this->detail('eventos-compensables'); }
}

