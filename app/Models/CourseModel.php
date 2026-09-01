<?php
namespace App\Models;

use App\Core\Model;

/**
 * PMO SOLUTIONS - Modelo de Cursos y Especializaciones (CourseModel)
 * 
 * Centraliza la metadata, temarios y categorización del catálogo educativo.
 */
class CourseModel extends Model {

    /**
     * Catálogo estructurado de programas de formación
     */
    public function getAll(): array {
        return [
            'dab-jrd' => [
                'slug'        => 'dab-jrd',
                'title'       => 'Juntas de Resolución de Disputas & DAB-JRD',
                'category'    => 'legal',
                'category_label' => 'Legal & Arbitraje',
                'icon'        => 'fas fa-gavel',
                'badge'       => 'Certificación OSCE / FIDIC',
                'badge_class' => 'bg-danger text-white',
                'hours'       => '36 Horas Lectivas',
                'description' => 'Prevención y resolución técnica de controversias en obras públicas y contratos internacionales bajo enfoque Dispute Boards.',
                'image'       => 'img/JuntaResolucion.png',
                'url'         => 'dab-jrd'
            ],
            'analisis-forense' => [
                'slug'        => 'analisis-forense',
                'title'       => 'Análisis Forense de Atrasos & Delay Claims',
                'category'    => 'forense',
                'category_label' => 'Peritajes & Reclamos',
                'icon'        => 'fas fa-search-plus',
                'badge'       => 'Metodologías SCL / AACE',
                'badge_class' => 'bg-primary text-white',
                'hours'       => '40 Horas Lectivas',
                'description' => 'Peritaje técnico de demoras, disrupción de productividad y sustentación de ampliaciones de plazo e indemnizaciones.',
                'image'       => 'img/AnalisisForense.png',
                'url'         => 'analisis-forense'
            ],
            'nec4' => [
                'slug'        => 'nec4',
                'title'       => 'Contratos NEC4: ECC y Servicios Profesionales',
                'category'    => 'legal',
                'category_label' => 'Contratos Colaborativos',
                'icon'        => 'fas fa-file-contract',
                'badge'       => 'Estándar UK / PEIP Escuelas',
                'badge_class' => 'bg-warning text-dark',
                'hours'       => '48 Horas Lectivas',
                'description' => 'Administración integral de contratos colaborativos NEC4, gestión de alertas tempranas y sustentación de Eventos Compensables.',
                'image'       => 'img/picture1b_orig.png',
                'url'         => 'nec4'
            ],
            'vdc-bim' => [
                'slug'        => 'vdc-bim',
                'title'       => 'Virtual Design and Construction (VDC) & BIM',
                'category'    => 'tecnologia',
                'category_label' => 'Tecnología BIM',
                'icon'        => 'fas fa-cubes',
                'badge'       => 'Enfoque Stanford CIFE',
                'badge_class' => 'bg-info text-dark',
                'hours'       => '32 Horas Lectivas',
                'description' => 'Integración de modelos BIM, métricas PPM e ingeniería concurrente (ICE) para reducir RFI y sobrecostos en obra.',
                'image'       => 'img/vdc.png',
                'url'         => 'vdc-bim'
            ],
            'contratos-estado' => [
                'slug'        => 'contratos-estado',
                'title'       => 'Gestión del Cambio & Contrataciones del Estado',
                'category'    => 'legal',
                'category_label' => 'Ley 30225 / OSCE',
                'icon'        => 'fas fa-landmark',
                'badge'       => 'Ley N° 30225 & Modificatorias',
                'badge_class' => 'bg-secondary text-white',
                'hours'       => '36 Horas Lectivas',
                'description' => 'Tratamiento de adicionales de obra, deductivos, ampliaciones de plazo y liquidaciones técnicas conforme a normativa nacional.',
                'image'       => 'img/contratosestado.png',
                'url'         => 'contratos-estado'
            ],
            'compliance' => [
                'slug'        => 'compliance',
                'title'       => 'Compliance Técnico y Legal en la Construcción',
                'category'    => 'legal',
                'category_label' => 'Compliance & Ética',
                'icon'        => 'fas fa-balance-scale',
                'badge'       => 'ISO 37001 / Buenas Prácticas',
                'badge_class' => 'bg-success text-white',
                'hours'       => '24 Horas Lectivas',
                'description' => 'Auditoría preventiva, control antifraude y mitigación de riesgos de inhabilitación en contrataciones de obras civiles.',
                'image'       => 'img/picture2b_orig.png',
                'url'         => 'compliance'
            ],
            'primavera-p6' => [
                'slug'        => 'primavera-p6',
                'title'       => 'Oracle Primavera P6 & Tableros Power BI',
                'category'    => 'gestion',
                'category_label' => 'Control de Proyectos',
                'icon'        => 'fas fa-chart-line',
                'badge'       => 'Oracle Primavera Professional',
                'badge_class' => 'bg-warning text-dark',
                'hours'       => '40 Horas Lectivas',
                'description' => 'Programación de cronogramas integrados, líneas base, curvas S y dashboards ejecutivos en Power BI.',
                'image'       => 'img/controlproyectos.png',
                'url'         => 'primavera-p6'
            ],
            'riesgos-pmi' => [
                'slug'        => 'riesgos-pmi',
                'title'       => 'Gestión Integral de Riesgos bajo Estándares PMI®',
                'category'    => 'gestion',
                'category_label' => 'Estándar PMI®',
                'icon'        => 'fas fa-shield-alt',
                'badge'       => 'PMBOK® 7ma Edición',
                'badge_class' => 'bg-primary text-white',
                'hours'       => '30 Horas Lectivas',
                'description' => 'Identificación, matrices cualitativas, planes de respuesta y monitoreo continuo de contingencias en megaproyectos.',
                'image'       => 'img/gestionriesgos.png',
                'url'         => 'riesgos-pmi'
            ],
            'analisis-cuantitativo' => [
                'slug'        => 'analisis-cuantitativo',
                'title'       => 'Análisis Cuantitativo de Riesgos (Monte Carlo)',
                'category'    => 'gestion',
                'category_label' => 'Simulación Estadística',
                'icon'        => 'fas fa-calculator',
                'badge'       => '@RISK / Primavera Risk',
                'badge_class' => 'bg-info text-dark',
                'hours'       => '32 Horas Lectivas',
                'description' => 'Simulación probabilística de plazos y costos con curvas S probabilísticas P50/P80 para fijar reservas de contingencia.',
                'image'       => 'img/analisiscuantitativo.png',
                'url'         => 'analisis-cuantitativo'
            ],
            'eventos-compensables' => [
                'slug'        => 'eventos-compensables',
                'title'       => 'Sustentación de Eventos Compensables en NEC4',
                'category'    => 'legal',
                'category_label' => 'Claims Contractuales',
                'icon'        => 'fas fa-file-invoice-dollar',
                'badge'       => 'Cláusula 60 / NEC4',
                'badge_class' => 'bg-danger text-white',
                'hours'       => '28 Horas Lectivas',
                'description' => 'Mecanismos de notificación, cuantificación de impacto en tiempo y costo (Quotations) y resolución ágil de discrepancias.',
                'image'       => 'img/gestioneventos.png',
                'url'         => 'eventos-compensables'
            ]
        ];
    }

    /**
     * Obtiene un curso por su identificador slug
     */
    public function getBySlug(string $slug): ?array {
        $courses = $this->getAll();
        return $courses[$slug] ?? null;
    }
}

