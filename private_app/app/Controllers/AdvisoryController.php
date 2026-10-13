<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * PMO SOLUTIONS - Controlador de Asesorías Corporativas (AdvisoryController)
 * 
 * Gestiona el renderizado de los 7 servicios de asesoría y consultoría especializada:
 *  1. Gestión de Proyectos & PMO
 *  2. Headhunting Especializado para la Construcción
 *  3. Gestión Integral del Riesgo (Técnico y Operativo)
 *  4. Evaluación del Riesgo del Cumplimiento del Plazo (Curva de Liberación / Síndrome del 90%)
 *  5. Análisis Forense del Cronograma (Collapse As-Built / CPM)
 *  6. Gestión de la Calidad en Proyectos
 *  7. Universidad Corporativa & Formación Empresarial
 */
class AdvisoryController extends Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 1. GESTIÓN DE PROYECTOS & PMO
     */
    public function gestionDePmo(): void {
        $this->render('advisory/gestion-de-pmo', [
            'pageTitle'       => 'Gestión de Proyectos & PMO | PMO Solutions',
            'metaDescription' => 'Asesoría especializada para orientar el diseño, adopción y consolidación de Oficinas de Gestión de Proyectos (PMO) en organizaciones de ingeniería y construcción.',
            'activeNav'       => 'asesoria'
        ]);
    }

    /**
     * 2. HEADHUNTING ESPECIALIZADO PARA LA CONSTRUCCIÓN
     */
    public function headhunting(): void {
        $this->render('advisory/headhunting', [
            'pageTitle'       => 'Headhunting Especializado para la Construcción | PMO Solutions',
            'metaDescription' => 'Atracción y selección de talento senior de alto rendimiento para proyectos de ingeniería y construcción. Ingenieros evaluando ingenieros con rigor técnico.',
            'activeNav'       => 'asesoria'
        ]);
    }

    /**
     * 3. GESTIÓN DEL RIESGO
     */
    public function gestionDeRiesgos(): void {
        $this->render('advisory/gestion-de-riesgos', [
            'pageTitle'       => 'Gestión del Riesgo Técnico y Operativo | PMO Solutions',
            'metaDescription' => 'Identificación, análisis cualitativo/cuantitativo y monitoreo estratégico de riesgos técnicos en megaproyectos bajo estándares PMI® e ISO 31000.',
            'activeNav'       => 'asesoria'
        ]);
    }

    /**
     * 4. EVALUACIÓN DEL RIESGO DEL CUMPLIMIENTO DEL PLAZO (SÍNDROME DEL 90%)
     */
    public function riesgoDelPlazo(): void {
        $this->render('advisory/riesgo-del-plazo', [
            'pageTitle'       => 'Evaluación del Riesgo del Cumplimiento del Plazo | PMO Solutions',
            'metaDescription' => 'Metodología predictiva de la Curva de Liberación® para prevenir el Síndrome del 90% y asegurar la fecha real de entrega en obras de construcción.',
            'activeNav'       => 'asesoria'
        ]);
    }

    /**
     * 5. ANÁLISIS FORENSE DEL CRONOGRAMA
     */
    public function cronogramaForense(): void {
        $this->render('advisory/cronograma-forense', [
            'pageTitle'       => 'Análisis Forense del Cronograma & Collapse As-Built | PMO Solutions',
            'metaDescription' => 'Evaluación pericial retrospectiva de retrasos, impactos en la ruta crítica (CPM) y deslinde de responsabilidades contractuales según estándares AACE® y SCL.',
            'activeNav'       => 'asesoria'
        ]);
    }

    /**
     * 6. GESTIÓN DE LA CALIDAD
     */
    public function gestionDeLaCalidad(): void {
        $this->render('advisory/gestion-de-la-calidad', [
            'pageTitle'       => 'Gestión de la Calidad en Proyectos | PMO Solutions',
            'metaDescription' => 'Estandarización, aseguramiento, auditoría técnica y gestión de postventa para reducir la variabilidad y retrabajos en obras de ingeniería y edificación.',
            'activeNav'       => 'asesoria'
        ]);
    }

    /**
     * 7. UNIVERSIDAD CORPORATIVA
     */
    public function universidadCorporativa(): void {
        $this->render('advisory/universidad-corporativa', [
            'pageTitle'       => 'Universidad Corporativa & Formación Empresarial | PMO Solutions',
            'metaDescription' => 'Diseño y estructuración de academias corporativas y mallas de capacitación a medida para potenciar el talento técnico y la productividad en organizaciones.',
            'activeNav'       => 'asesoria'
        ]);
    }
}

