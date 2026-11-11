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
        $categories = $this->courseModel->getCategories();

        $this->render('capacitaciones', [
            'pageTitle'       => 'Catálogo de Capacitaciones 2026 | PMO Solutions',
            'metaDescription' => 'Conoce nuestros programas de especialización ejecutiva e in-house en Contratos NEC4, Juntas de Resolución (DAB), Peritajes Forenses, Calidad, Oficina Técnica, Primavera P6 y VDC-BIM.',
            'activeNav'       => 'capacitaciones',
            'courses'         => $courses,
            'categories'      => $categories,
        ]);
    }

    /**
     * Muestra la página corporativa de Capacitaciones In-House
     */
    public function inHouse(): void {
        $this->render('in-house', [
            'pageTitle'       => 'Capacitaciones In-House | Formación a la Medida para Empresas - PMO Solutions',
            'metaDescription' => 'Programas de capacitación técnica corporativa in-house diseñados a medida para empresas de ingeniería y construcción. Formación práctica en Contratos NEC4/FIDIC, Dispute Boards, Análisis Forense, VDC-BIM y Gestión de Riesgos.',
            'activeNav'       => 'in-house',
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

        $viewsBase = defined('APP_PATH') ? (APP_PATH . '/Views') : dirname(__DIR__) . '/Views';
        $customViewRel = "courses/{$slug}";
        $customViewAbs = $viewsBase . '/pages/' . $customViewRel . '.php';
        $viewName = is_file($customViewAbs) ? $customViewRel : 'courses/course-detail';

        $this->render($viewName, [
            'pageTitle'       => $course['meta_title'] ?? ($course['title'] . ' | PMO Solutions'),
            'metaDescription' => $course['meta_description'] ?? ($course['short_description'] ?? $course['description']),
            'canonical'       => $course['canonical'] ?? ('https://pmo-solutions.com/curso/' . $slug),
            'ogImage'         => $course['image'] ?? 'img/LogoPMO.png',
            'activeNav'       => 'capacitaciones',
            'course'          => $course,
            'relatedCourses'  => $this->courseModel->getRelated($slug, 3),
        ]);
    }

    // Métodos directos para rutas heredadas (mantiene compatibilidad y consolida canonical en /curso/{slug})
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
