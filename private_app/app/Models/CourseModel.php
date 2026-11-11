<?php
namespace App\Models;

use App\Core\Cache;
use App\Core\Model;

/**
 * PMO SOLUTIONS - Modelo de Cursos y Capacitaciones (CourseModel)
 * Catálogo centralizado con los 10 programas base de PMO Solutions y
 * los 33 cursos migrados desde Quality Consulting Solutions (total: 43).
 */
class CourseModel extends Model {

    public const CACHE_KEY = 'courses_catalog_v3';
    private const CANONICAL_BASE = 'https://pmo-solutions.com/curso/';

    /**
     * Taxonomía académica normalizada (7 áreas reales que cubren exactamente los 43 cursos).
     */
    private const CATEGORY_META = [
        'contratos-disputas' => [
            'label' => 'Contratos, Claims y Disputas',
            'icon'  => 'fas fa-file-contract',
        ],
        'proyectos-pmo' => [
            'label' => 'Gestión de Proyectos, Lean y PMO',
            'icon'  => 'fas fa-project-diagram',
        ],
        'bim-tecnologia' => [
            'label' => 'BIM, VDC y Analítica de Datos',
            'icon'  => 'fas fa-cubes',
        ],
        'oficina-tecnica' => [
            'label' => 'Oficina Técnica y Control de Obra',
            'icon'  => 'fas fa-drafting-compass',
        ],
        'riesgos' => [
            'label' => 'Gestión de Riesgos',
            'icon'  => 'fas fa-shield-alt',
        ],
        'calidad' => [
            'label' => 'Calidad',
            'icon'  => 'fas fa-award',
        ],
        'estado-compliance' => [
            'label' => 'Contratación Pública y Compliance',
            'icon'  => 'fas fa-balance-scale',
        ],
    ];

    /**
     * Asignación centralizada de categoría única y palabras clave verificadas por slug.
     */
    private const COURSE_TAXONOMY = [
        // 1. Calidad (4 cursos)
        'gerencia-de-calidad' => [
            'category' => 'calidad',
            'keywords' => ['calidad', 'construccion', 'last planner', 'indicadores', 'ia', 'inteligencia artificial', 'pmi'],
        ],
        'iso-9001' => [
            'category' => 'calidad',
            'keywords' => ['iso 9001', 'calidad', 'sistema de gestion de calidad', 'sgc', 'auditoria', 'ia', 'inteligencia artificial'],
        ],
        'control-de-calidad-en-tuneleria' => [
            'category' => 'calidad',
            'keywords' => ['tuneleria', 'tuneles', 'obras subterraneas', 'control de calidad', 'concreto lanzado', 'shotcrete'],
        ],
        'direccion-calidad-enfoque-pmi' => [
            'category' => 'calidad',
            'keywords' => ['calidad', 'pmi', 'pmbok', 'direccion de proyectos', 'aseguramiento', 'control de calidad'],
        ],

        // 2. Oficina Técnica y Control de Obra (6 cursos)
        'oficina-tecnica-edificaciones-panel-control' => [
            'category' => 'oficina-tecnica',
            'keywords' => ['oficina tecnica', 'edificaciones', 'panel de control', 'real time', 'metrados', 'valorizaciones', 'presupuestos'],
        ],
        'oficina-tecnica-hospitales-edificaciones' => [
            'category' => 'oficina-tecnica',
            'keywords' => ['oficina tecnica', 'hospitales', 'edificaciones', 'infraestructura hospitalaria', 'instalaciones'],
        ],
        'oficina-tecnica-obras-viales' => [
            'category' => 'oficina-tecnica',
            'keywords' => ['oficina tecnica', 'obras viales', 'carreteras', 'movimiento de tierras', 'pavimentos', 'valorizaciones'],
        ],
        'oficina-tecnica-infraestructura-minera' => [
            'category' => 'oficina-tecnica',
            'keywords' => ['oficina tecnica', 'mineria', 'infraestructura minera', 'pads', 'presas de relave', 'geosinteticos'],
        ],
        'resultado-operativo-obra' => [
            'category' => 'oficina-tecnica',
            'keywords' => ['resultado operativo', 'ro', 'costos', 'margen', 'rentabilidad', 'control economico', 'obra'],
        ],
        'control-proyectos-construccion' => [
            'category' => 'oficina-tecnica',
            'keywords' => ['control de proyectos', 'construccion', 'programacion', 'cronograma', 'costos', 'avances'],
        ],

        // 3. Contratos, Claims y Disputas (13 cursos)
        'dab-jrd' => [
            'category' => 'contratos-disputas',
            'keywords' => ['dab', 'jrd', 'junta de resolucion de disputas', 'dispute boards', 'osce', 'fidic', 'controversias'],
        ],
        'nec4' => [
            'category' => 'contratos-disputas',
            'keywords' => ['nec4', 'nec 4', 'ecc', 'psc', 'servicios profesionales', 'contratos colaborativos'],
        ],
        'eventos-compensables' => [
            'category' => 'contratos-disputas',
            'keywords' => ['eventos compensables', 'compensation events', 'nec4', 'clausula 60', 'quotations', 'alertas tempranas', 'early warning'],
        ],
        'analisis-forense' => [
            'category' => 'contratos-disputas',
            'keywords' => ['analisis forense', 'delay claims', 'reclamos de plazo', 'atrasos', 'peritaje', 'ruta critica', 'cpm'],
        ],
        'analisis-forense-cronograma-primavera-p6' => [
            'category' => 'contratos-disputas',
            'keywords' => ['analisis forense', 'cronograma', 'primavera p6', 'oracle primavera', 'aace 29r-03', 'scl', 'delay claims', 'peritaje'],
        ],
        'contratos-colaborativos-nec3-f' => [
            'category' => 'contratos-disputas',
            'keywords' => ['nec3', 'nec3 opcion f', 'nec3-f', 'contratos colaborativos', 'management contract'],
        ],
        'contrato-nec4' => [
            'category' => 'contratos-disputas',
            'keywords' => ['nec4', 'nec 4', 'fundamentos', 'administracion contractual', 'contratos colaborativos'],
        ],
        'casos-practicos-contratos-nec3-f' => [
            'category' => 'contratos-disputas',
            'keywords' => ['nec3', 'opcion f', 'management contract', 'casos practicos', 'contratos colaborativos'],
        ],
        'junta-resolucion-disputas-dab' => [
            'category' => 'contratos-disputas',
            'keywords' => ['jrd', 'dab', 'junta de resolucion de disputas', 'dispute boards', 'controversias', 'obras'],
        ],
        'contrato-colaborativo-nec-3-4' => [
            'category' => 'contratos-disputas',
            'keywords' => ['nec3', 'nec4', 'nec 3/4', 'contrato colaborativo', 'reconstruccion'],
        ],
        'contratos-fidic-epc-construccion' => [
            'category' => 'contratos-disputas',
            'keywords' => ['fidic', 'epc', 'libro rojo', 'libro amarillo', 'libro plata', 'contratos internacionales'],
        ],
        'gestion-reclamos-nec-fidic' => [
            'category' => 'contratos-disputas',
            'keywords' => ['reclamos', 'claims', 'nec', 'nec4', 'fidic', 'controversias', 'disputas'],
        ],
        'gestion-contractual-megaproyectos-mineros' => [
            'category' => 'contratos-disputas',
            'keywords' => ['gestion contractual', 'megaproyectos', 'mineria', 'mineros', 'claims', 'administracion de contratos'],
        ],

        // 4. Gestión de Riesgos (5 cursos)
        'riesgos-pmi' => [
            'category' => 'riesgos',
            'keywords' => ['riesgos', 'pmi', 'pmbok', 'pmi-rmp', 'matriz de riesgos', 'mitigacion'],
        ],
        'analisis-cuantitativo' => [
            'category' => 'riesgos',
            'keywords' => ['analisis cuantitativo', 'monte carlo', 'riesgos', 'curvas s', 'p50', 'p80', 'contingencia'],
        ],
        'riesgos-tecnicos' => [
            'category' => 'riesgos',
            'keywords' => ['riesgos tecnicos', 'pmi', 'estandar pmi 2024', 'construccion', 'riesgos'],
        ],
        'gestion-de-riesgos' => [
            'category' => 'riesgos',
            'keywords' => ['riesgos', 'cambios', 'reclamos', 'plazo', 'alcance', 'construccion'],
        ],
        'riesgos-cadena-produccion' => [
            'category' => 'riesgos',
            'keywords' => ['riesgos', 'cadena de produccion', 'iso 31000', 'bim', 'suministros'],
        ],

        // 5. Gestión de Proyectos, Lean y PMO (7 cursos)
        'gestion-proyectos-pmi' => [
            'category' => 'proyectos-pmo',
            'keywords' => ['gestion de proyectos', 'pmi', 'pmbok', 'pmp', 'direccion de proyectos'],
        ],
        'gestion-pmo-vmo' => [
            'category' => 'proyectos-pmo',
            'keywords' => ['pmo', 'vmo', 'oficina de gestion de proyectos', 'value management office', 'gobernanza'],
        ],
        'brightline-initiative' => [
            'category' => 'proyectos-pmo',
            'keywords' => ['brightline', 'pmi', 'estrategia', 'transformacion', 'ejecucion estrategica'],
        ],
        'gruas-torre' => [
            'category' => 'proyectos-pmo',
            'keywords' => ['gruas torre', 'productividad', 'izaje', 'logistica de obra', 'planificacion'],
        ],
        'lean-construction-edificaciones' => [
            'category' => 'proyectos-pmo',
            'keywords' => ['lean construction', 'edificaciones', 'last planner', 'productividad', 'trenes de trabajo', 'lookahead'],
        ],
        'lean-construction-infraestructura' => [
            'category' => 'proyectos-pmo',
            'keywords' => ['lean construction', 'infraestructura', 'last planner', 'productividad', 'obras civiles'],
        ],
        'liderazgo-construccion' => [
            'category' => 'proyectos-pmo',
            'keywords' => ['liderazgo', 'habilidades blandas', 'equipos de obra', 'gestion de personas', 'construccion'],
        ],

        // 6. BIM, VDC y Analítica de Datos (6 cursos)
        'vdc-bim' => [
            'category' => 'bim-tecnologia',
            'keywords' => ['vdc', 'bim', 'virtual design and construction', 'ice', 'ppm', 'stanford'],
        ],
        'vdc' => [
            'category' => 'bim-tecnologia',
            'keywords' => ['vdc', 'stanford', 'ice', 'bim', 'ppm', 'virtual design and construction', 'cife'],
        ],
        'primavera-p6' => [
            'category' => 'bim-tecnologia',
            'keywords' => ['primavera p6', 'oracle primavera', 'power bi', 'cronograma', 'linea base', 'valor ganado'],
        ],
        'valor-ganado-ms-project-power-bi' => [
            'category' => 'bim-tecnologia',
            'keywords' => ['valor ganado', 'evm', 'ms project', 'microsoft project', 'power bi', 'cpi', 'spi'],
        ],
        'costos-edificaciones-power-bi' => [
            'category' => 'bim-tecnologia',
            'keywords' => ['costos', 'edificaciones', 'power bi', 'presupuestos', 'control de costos', 'dashboards'],
        ],
        'control-proyectos-bim-power-bi' => [
            'category' => 'bim-tecnologia',
            'keywords' => ['control de proyectos', 'bim', 'power bi', 'tableros', 'dashboards', 'metrados'],
        ],

        // 7. Contratación Pública y Compliance (2 cursos)
        'contratos-estado' => [
            'category' => 'estado-compliance',
            'keywords' => ['contrataciones del estado', 'obras publicas', 'osce', 'ley de contrataciones', 'adicionales', 'ampliaciones de plazo'],
        ],
        'compliance' => [
            'category' => 'estado-compliance',
            'keywords' => ['compliance', 'iso 37001', 'antisoborno', 'cumplimiento legal', 'etica corporativa', 'construccion'],
        ],
    ];

    /**
     * Construye el texto unificado de búsqueda para un curso a partir de sus metadatos reales.
     */
    public static function buildSearchText(array $course): string {
        $parts = [
            (string)($course['title'] ?? ''),
            (string)($course['short_description'] ?? ($course['description'] ?? '')),
            (string)($course['category_label'] ?? ''),
            (string)($course['category'] ?? ''),
            str_replace('-', ' ', (string)($course['category'] ?? '')),
            (string)($course['instructor'] ?? ''),
            (string)($course['instructor_title'] ?? ''),
            (string)($course['slug'] ?? ''),
            str_replace('-', ' ', (string)($course['slug'] ?? '')),
            (string)($course['badge'] ?? ''),
        ];

        if (!empty($course['keywords']) && is_array($course['keywords'])) {
            foreach ($course['keywords'] as $kw) {
                $parts[] = (string)$kw;
            }
        }

        $joined = implode(' ', array_filter($parts, static fn($v) => trim($v) !== ''));
        $collapsed = preg_replace('/\s+/u', ' ', trim($joined)) ?? $joined;
        return mb_strtolower($collapsed, 'UTF-8');
    }

    /**
     * Retorna el listado completo de cursos indexado por slug (cacheado en disco).
     */
    public function getAll(): array {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            return $this->buildCatalog();
        });
    }

    /**
     * Obtiene un curso específico por su slug oficial.
     */
    public function getBySlug(string $slug): ?array {
        $courses = $this->getAll();
        return $courses[$slug] ?? null;
    }

    /**
     * Retorna las categorías disponibles dinámicamente con etiqueta, ícono y conteo real.
     */
    public function getCategories(): array {
        $counts = [];
        foreach ($this->getAll() as $course) {
            $cat = (string)($course['category'] ?? '');
            if ($cat !== '') {
                $counts[$cat] = ($counts[$cat] ?? 0) + 1;
            }
        }

        $result = [];
        foreach (self::CATEGORY_META as $key => $info) {
            if (!empty($counts[$key])) {
                $result[$key] = [
                    'slug'  => $key,
                    'label' => $info['label'],
                    'icon'  => $info['icon'],
                    'count' => $counts[$key],
                ];
            }
        }
        return $result;
    }

    /**
     * Retorna cursos relacionados pertenecientes preferentemente a la misma categoría.
     */
    public function getRelated(string $slug, int $limit = 3): array {
        $all = $this->getAll();
        $current = $all[$slug] ?? null;
        if (!$current) {
            return [];
        }

        $sameCategory = [];
        $others = [];
        foreach ($all as $candidateSlug => $candidate) {
            if ($candidateSlug === $slug) {
                continue;
            }
            if ($candidate['category'] === $current['category']) {
                $sameCategory[$candidateSlug] = $candidate;
            } else {
                $others[$candidateSlug] = $candidate;
            }
        }

        return array_slice($sameCategory + $others, 0, $limit, true);
    }

    /**
     * Construye el catálogo completo combinando los programas base PMO y los cursos migrados.
     */
    private function buildCatalog(): array {
        $pmoOriginals = [
            'dab-jrd' => [
                'id'                   => 'pmo-01-dab-jrd',
                'slug'                 => 'dab-jrd',
                'title'                => 'Juntas de Resolución de Disputas & DAB-JRD',
                'type'                 => 'Curso',
                'category'             => 'contratos',
                'category_label'       => 'Contratos & Dispute Boards',
                'icon'                 => 'fas fa-gavel',
                'badge'                => 'Enfoque OSCE / FIDIC',
                'badge_class'          => 'bg-danger text-white',
                'hours'                => '36 Horas Lectivas',
                'duration'             => '36 Horas Lectivas',
                'schedule'             => null,
                'modality'             => 'Online Asíncrono / Clases Grabadas 24/7',
                'description'          => 'Prevención y resolución técnica de controversias en obras públicas y contratos internacionales bajo enfoque Dispute Boards.',
                'short_description'    => 'Prevención y resolución técnica de controversias en obras públicas y contratos internacionales bajo enfoque Dispute Boards.',
                'full_description'     => 'Prevención y resolución técnica de controversias en obras públicas y contratos internacionales bajo enfoque Dispute Boards.',
                'instructor'           => 'Dr. Luis Ruiz',
                'instructor_title'     => 'Especialista en Dispute Boards & Arbitraje en Obras',
                'instructor_credentials' => [],
                'instructor_bio'       => [],
                'instructor_image'     => 'img/LuisRuiz.jpg',
                'image'                => 'img/picture1b_orig.png',
                'detail_image'         => 'img/JuntaResolucion.png',
                'image_fallback'       => null,
                'syllabus'             => [],
                'learning_outcomes'    => [],
                'benefits'             => [],
                'target_audience'      => null,
                'certification'        => 'Certificado PMO Solutions',
                'brochure_url'         => '/dab-jrd',
                'enrollment_slug'      => 'dab-jrd',
                'url'                  => 'curso/dab-jrd',
                'meta_title'           => 'Juntas de Resolución de Disputas & DAB-JRD | PMO Solutions',
                'meta_description'     => 'Prevención y resolución técnica de controversias en obras públicas y contratos internacionales bajo enfoque Dispute Boards.',
                'canonical'            => self::CANONICAL_BASE . 'dab-jrd',
                'source_reference'     => 'PMO Original #01 (dab-jrd)',
                'absorbed_sources'     => [],
                'migration_status'     => 'pmo_original',
                'quality_number'       => null,
            ],
            'analisis-forense' => [
                'id'                   => 'pmo-02-analisis-forense',
                'slug'                 => 'analisis-forense',
                'title'                => 'Análisis Forense de Atrasos & Delay Claims',
                'type'                 => 'Curso',
                'category'             => 'riesgos',
                'category_label'       => 'Riesgos & Forense',
                'icon'                 => 'fas fa-search-plus',
                'badge'                => 'Metodologías SCL / AACE',
                'badge_class'          => 'bg-primary text-white',
                'hours'                => '40 Horas Lectivas',
                'duration'             => '40 Horas Lectivas',
                'schedule'             => null,
                'modality'             => 'Online Asíncrono / Clases Grabadas 24/7',
                'description'          => 'Peritaje técnico de demoras, disrupción de productividad y sustentación de ampliaciones de plazo e indemnizaciones.',
                'short_description'    => 'Peritaje técnico de demoras, disrupción de productividad y sustentación de ampliaciones de plazo e indemnizaciones.',
                'full_description'     => 'Peritaje técnico de demoras, disrupción de productividad y sustentación de ampliaciones de plazo e indemnizaciones.',
                'instructor'           => 'Ing. Edward Marcelo',
                'instructor_title'     => 'Especialista en Análisis Forense de Cronogramas',
                'instructor_credentials' => [],
                'instructor_bio'       => [],
                'instructor_image'     => 'img/edwardmarcelo.png',
                'image'                => 'img/picture2b_orig.png',
                'detail_image'         => 'img/AnalisisForense.png',
                'image_fallback'       => null,
                'syllabus'             => [],
                'learning_outcomes'    => [],
                'benefits'             => [],
                'target_audience'      => null,
                'certification'        => 'Certificado PMO Solutions',
                'brochure_url'         => '/analisis-forense',
                'enrollment_slug'      => 'analisis-forense',
                'url'                  => 'curso/analisis-forense',
                'meta_title'           => 'Análisis Forense de Atrasos & Delay Claims | PMO Solutions',
                'meta_description'     => 'Peritaje técnico de demoras, disrupción de productividad y sustentación de ampliaciones de plazo e indemnizaciones.',
                'canonical'            => self::CANONICAL_BASE . 'analisis-forense',
                'source_reference'     => 'PMO Original #02 (analisis-forense)',
                'absorbed_sources'     => [],
                'migration_status'     => 'pmo_original',
                'quality_number'       => null,
            ],
            'nec4' => [
                'id'                   => 'pmo-03-nec4',
                'slug'                 => 'nec4',
                'title'                => 'Contratos NEC4: ECC y Servicios Profesionales',
                'type'                 => 'Curso',
                'category'             => 'contratos',
                'category_label'       => 'Contratos & Dispute Boards',
                'icon'                 => 'fas fa-file-contract',
                'badge'                => 'Estándar UK / PEIP Escuelas',
                'badge_class'          => 'bg-warning text-dark',
                'hours'                => '48 Horas Lectivas',
                'duration'             => '48 Horas Lectivas',
                'schedule'             => null,
                'modality'             => 'Online Asíncrono / Clases Grabadas 24/7',
                'description'          => 'Administración integral de contratos colaborativos NEC4, gestión de alertas tempranas y sustentación de Eventos Compensables.',
                'short_description'    => 'Administración integral de contratos colaborativos NEC4, gestión de alertas tempranas y sustentación de Eventos Compensables.',
                'full_description'     => 'Administración integral de contratos colaborativos NEC4, gestión de alertas tempranas y sustentación de Eventos Compensables.',
                'instructor'           => 'Ing. Iván Benavides',
                'instructor_title'     => 'Especialista en Contratos Colaborativos NEC',
                'instructor_credentials' => [],
                'instructor_bio'       => [],
                'instructor_image'     => 'img/ivanbenavides.jpg',
                'image'                => 'img/picture3b_orig.png',
                'detail_image'         => 'img/casosaplicacion.png',
                'image_fallback'       => null,
                'syllabus'             => [],
                'learning_outcomes'    => [],
                'benefits'             => [],
                'target_audience'      => null,
                'certification'        => 'Certificado PMO Solutions',
                'brochure_url'         => '/nec4',
                'enrollment_slug'      => 'nec4',
                'url'                  => 'curso/nec4',
                'meta_title'           => 'Contratos NEC4: ECC y Servicios Profesionales | PMO Solutions',
                'meta_description'     => 'Administración integral de contratos colaborativos NEC4, gestión de alertas tempranas y sustentación de Eventos Compensables.',
                'canonical'            => self::CANONICAL_BASE . 'nec4',
                'source_reference'     => 'PMO Original #03 (nec4)',
                'absorbed_sources'     => [],
                'migration_status'     => 'pmo_original',
                'quality_number'       => null,
            ],
            'vdc-bim' => [
                'id'                   => 'pmo-04-vdc-bim',
                'slug'                 => 'vdc-bim',
                'title'                => 'Virtual Design and Construction (VDC) & BIM',
                'type'                 => 'Curso',
                'category'             => 'gestion-proyectos',
                'category_label'       => 'Gestión de Proyectos, BIM & Control',
                'icon'                 => 'fas fa-cubes',
                'badge'                => 'Enfoque Stanford CIFE',
                'badge_class'          => 'bg-info text-dark',
                'hours'                => '32 Horas Lectivas',
                'duration'             => '32 Horas Lectivas',
                'schedule'             => null,
                'modality'             => 'Online Asíncrono / Clases Grabadas 24/7',
                'description'          => 'Integración de modelos BIM, métricas PPM e ingeniería concurrente (ICE) para reducir RFI y sobrecostos en obra.',
                'short_description'    => 'Integración de modelos BIM, métricas PPM e ingeniería concurrente (ICE) para reducir RFI y sobrecostos en obra.',
                'full_description'     => 'Integración de modelos BIM, métricas PPM e ingeniería concurrente (ICE) para reducir RFI y sobrecostos en obra.',
                'instructor'           => 'Ing. César',
                'instructor_title'     => 'Especialista VDC Stanford & BIM',
                'instructor_credentials' => [],
                'instructor_bio'       => [],
                'instructor_image'     => 'img/cesar.png',
                'image'                => 'img/picture4b_orig.png',
                'detail_image'         => 'img/vdc.png',
                'image_fallback'       => null,
                'syllabus'             => [],
                'learning_outcomes'    => [],
                'benefits'             => [],
                'target_audience'      => null,
                'certification'        => 'Certificado PMO Solutions',
                'brochure_url'         => '/vdc-bim',
                'enrollment_slug'      => 'vdc-bim',
                'url'                  => 'curso/vdc-bim',
                'meta_title'           => 'Virtual Design and Construction (VDC) & BIM | PMO Solutions',
                'meta_description'     => 'Integración de modelos BIM, métricas PPM e ingeniería concurrente (ICE) para reducir RFI y sobrecostos en obra.',
                'canonical'            => self::CANONICAL_BASE . 'vdc-bim',
                'source_reference'     => 'PMO Original #04 (vdc-bim)',
                'absorbed_sources'     => [],
                'migration_status'     => 'pmo_original',
                'quality_number'       => null,
            ],
            'contratos-estado' => [
                'id'                   => 'pmo-05-contratos-estado',
                'slug'                 => 'contratos-estado',
                'title'                => 'Gestión del Cambio & Contrataciones del Estado',
                'type'                 => 'Curso',
                'category'             => 'contratos',
                'category_label'       => 'Contratos & Dispute Boards',
                'icon'                 => 'fas fa-landmark',
                'badge'                => 'Ley N° 30225 & Modificatorias',
                'badge_class'          => 'bg-secondary text-white',
                'hours'                => '36 Horas Lectivas',
                'duration'             => '36 Horas Lectivas',
                'schedule'             => null,
                'modality'             => 'Online Asíncrono / Clases Grabadas 24/7',
                'description'          => 'Tratamiento de adicionales de obra, deductivos, ampliaciones de plazo y liquidaciones técnicas conforme a normativa nacional.',
                'short_description'    => 'Tratamiento de adicionales de obra, deductivos, ampliaciones de plazo y liquidaciones técnicas conforme a normativa nacional.',
                'full_description'     => 'Tratamiento de adicionales de obra, deductivos, ampliaciones de plazo y liquidaciones técnicas conforme a normativa nacional.',
                'instructor'           => 'Ing. Luis Condori Hospina',
                'instructor_title'     => 'Perito INNOVA PUCP · Miembro Adjudicador JRD CIP-LIMA',
                'instructor_credentials' => ['CIP', 'JRD CIP-LIMA', 'PERITO'],
                'instructor_bio'       => [],
                'instructor_image'     => 'img/Luis.png',
                'image'                => 'img/picture5b_orig.png',
                'detail_image'         => 'img/contratosestado.png',
                'image_fallback'       => null,
                'syllabus'             => [],
                'learning_outcomes'    => [],
                'benefits'             => [],
                'target_audience'      => null,
                'certification'        => 'Certificado PMO Solutions (Equivalente Quality: 16 PDU)',
                'brochure_url'         => '/contratos-estado',
                'enrollment_slug'      => 'contratos-estado',
                'url'                  => 'curso/contratos-estado',
                'meta_title'           => 'Gestión del Cambio & Contrataciones del Estado | PMO Solutions',
                'meta_description'     => 'Tratamiento de adicionales de obra, deductivos, ampliaciones de plazo y liquidaciones técnicas conforme a normativa nacional.',
                'canonical'            => self::CANONICAL_BASE . 'contratos-estado',
                'source_reference'     => 'PMO Original #05 + Quality Curso #24 (gestion-cambios-contratos-estado)',
                'absorbed_sources'     => ['gestion-cambios-contratos-estado'],
                'migration_status'     => 'exact_equivalent_enriched',
                'quality_number'       => 24,
            ],
            'compliance' => [
                'id'                   => 'pmo-06-compliance',
                'slug'                 => 'compliance',
                'title'                => 'Compliance Técnico y Legal en la Construcción',
                'type'                 => 'Curso',
                'category'             => 'contratos',
                'category_label'       => 'Contratos & Dispute Boards',
                'icon'                 => 'fas fa-balance-scale',
                'badge'                => 'ISO 37001 / Buenas Prácticas',
                'badge_class'          => 'bg-success text-white',
                'hours'                => '24 Horas Lectivas',
                'duration'             => '24 Horas Lectivas',
                'schedule'             => null,
                'modality'             => 'Online Asíncrono / Clases Grabadas 24/7',
                'description'          => 'Auditoría preventiva, control antifraude y mitigación de riesgos de inhabilitación en contrataciones de obras civiles.',
                'short_description'    => 'Auditoría preventiva, control antifraude y mitigación de riesgos de inhabilitación en contrataciones de obras civiles.',
                'full_description'     => 'Auditoría preventiva, control antifraude y mitigación de riesgos de inhabilitación en contrataciones de obras civiles.',
                'instructor'           => 'Renzo Valera',
                'instructor_title'     => 'Especialista en Compliance & Sistemas Antisoborno ISO 37001',
                'instructor_credentials' => ['ISO 37001'],
                'instructor_bio'       => [],
                'instructor_image'     => 'img/RenzoValera.png',
                'image'                => 'img/picture6b_orig.png',
                'detail_image'         => 'img/picture6b_orig.png',
                'image_fallback'       => null,
                'syllabus'             => [],
                'learning_outcomes'    => [],
                'benefits'             => [],
                'target_audience'      => null,
                'certification'        => 'Certificado PMO Solutions',
                'brochure_url'         => '/compliance',
                'enrollment_slug'      => 'compliance',
                'url'                  => 'curso/compliance',
                'meta_title'           => 'Compliance Técnico y Legal en la Construcción | PMO Solutions',
                'meta_description'     => 'Auditoría preventiva, control antifraude y mitigación de riesgos de inhabilitación en contrataciones de obras civiles.',
                'canonical'            => self::CANONICAL_BASE . 'compliance',
                'source_reference'     => 'PMO Exclusivo #06 (compliance)',
                'absorbed_sources'     => [],
                'migration_status'     => 'pmo_original',
                'quality_number'       => null,
            ],
            'primavera-p6' => [
                'id'                   => 'pmo-07-primavera-p6',
                'slug'                 => 'primavera-p6',
                'title'                => 'Oracle Primavera P6 & Tableros Power BI',
                'type'                 => 'Curso',
                'category'             => 'gestion-proyectos',
                'category_label'       => 'Gestión de Proyectos, BIM & Control',
                'icon'                 => 'fas fa-chart-line',
                'badge'                => 'Oracle Primavera Professional',
                'badge_class'          => 'bg-warning text-dark',
                'hours'                => '40 Horas Lectivas',
                'duration'             => '40 Horas Lectivas',
                'schedule'             => null,
                'modality'             => 'Online Asíncrono / Clases Grabadas 24/7',
                'description'          => 'Programación de cronogramas integrados, líneas base, curvas S y dashboards ejecutivos en Power BI.',
                'short_description'    => 'Programación de cronogramas integrados, líneas base, curvas S y dashboards ejecutivos en Power BI.',
                'full_description'     => 'Programación de cronogramas integrados, líneas base, curvas S y dashboards ejecutivos en Power BI.',
                'instructor'           => 'Ing. Fernando Barrero',
                'instructor_title'     => 'MBA, PMP®, Scrum Master · Especialista Senior en Primavera P6 & Power BI',
                'instructor_credentials' => ['MBA', 'PMP®', 'Scrum Master'],
                'instructor_bio'       => [],
                'instructor_image'     => 'img/Persona1.png',
                'image'                => 'img/controlproyectos.png',
                'detail_image'         => 'img/controlproyectos.png',
                'image_fallback'       => null,
                'syllabus'             => [],
                'learning_outcomes'    => [],
                'benefits'             => [],
                'target_audience'      => null,
                'certification'        => 'Certificado PMO Solutions',
                'brochure_url'         => '/primavera-p6',
                'enrollment_slug'      => 'primavera-p6',
                'url'                  => 'curso/primavera-p6',
                'meta_title'           => 'Oracle Primavera P6 & Tableros Power BI | PMO Solutions',
                'meta_description'     => 'Programación de cronogramas integrados, líneas base, curvas S y dashboards ejecutivos en Power BI.',
                'canonical'            => self::CANONICAL_BASE . 'primavera-p6',
                'source_reference'     => 'PMO Original #07 + Quality Curso #43 (control-proyectos-primavera-p6)',
                'absorbed_sources'     => ['control-proyectos-primavera-p6'],
                'migration_status'     => 'exact_equivalent_enriched',
                'quality_number'       => 43,
            ],
            'riesgos-pmi' => [
                'id'                   => 'pmo-08-riesgos-pmi',
                'slug'                 => 'riesgos-pmi',
                'title'                => 'Gestión Integral de Riesgos bajo Estándares PMI®',
                'type'                 => 'Curso',
                'category'             => 'riesgos',
                'category_label'       => 'Riesgos & Forense',
                'icon'                 => 'fas fa-shield-alt',
                'badge'                => 'PMBOK® 7ma Edición',
                'badge_class'          => 'bg-primary text-white',
                'hours'                => '30 Horas Lectivas',
                'duration'             => '30 Horas Lectivas',
                'schedule'             => null,
                'modality'             => 'Online Asíncrono / Clases Grabadas 24/7',
                'description'          => 'Identificación, matrices cualitativas, planes de respuesta y monitoreo continuo de contingencias en megaproyectos.',
                'short_description'    => 'Identificación, matrices cualitativas, planes de respuesta y monitoreo continuo de contingencias en megaproyectos.',
                'full_description'     => 'Identificación, matrices cualitativas, planes de respuesta y monitoreo continuo de contingencias en megaproyectos.',
                'instructor'           => 'Ing. Félix Valdez-Torero',
                'instructor_title'     => 'PhD(c), MSc, PMP® · Especialista Senior en Gestión de Riesgos',
                'instructor_credentials' => ['PhD(c)', 'MSc', 'PMP®'],
                'instructor_bio'       => [],
                'instructor_image'     => 'img/felixvaldez.jpg',
                'image'                => 'img/gestionriesgos.png',
                'detail_image'         => 'img/gestionriesgos.png',
                'image_fallback'       => null,
                'syllabus'             => [],
                'learning_outcomes'    => [],
                'benefits'             => [],
                'target_audience'      => null,
                'certification'        => 'Certificado PMO Solutions',
                'brochure_url'         => '/riesgos-pmi',
                'enrollment_slug'      => 'riesgos-pmi',
                'url'                  => 'curso/riesgos-pmi',
                'meta_title'           => 'Gestión Integral de Riesgos bajo Estándares PMI® | PMO Solutions',
                'meta_description'     => 'Identificación, matrices cualitativas, planes de respuesta y monitoreo continuo de contingencias en megaproyectos.',
                'canonical'            => self::CANONICAL_BASE . 'riesgos-pmi',
                'source_reference'     => 'PMO Original #08 + Quality Curso #29 (riesgos-pmi)',
                'absorbed_sources'     => ['riesgos-pmi'],
                'migration_status'     => 'exact_equivalent_enriched',
                'quality_number'       => 29,
            ],
            'analisis-cuantitativo' => [
                'id'                   => 'pmo-09-analisis-cuantitativo',
                'slug'                 => 'analisis-cuantitativo',
                'title'                => 'Análisis Cuantitativo de Riesgos (Monte Carlo)',
                'type'                 => 'Curso',
                'category'             => 'riesgos',
                'category_label'       => 'Riesgos & Forense',
                'icon'                 => 'fas fa-calculator',
                'badge'                => '@RISK / Primavera Risk',
                'badge_class'          => 'bg-info text-dark',
                'hours'                => '32 Horas Lectivas',
                'duration'             => '32 Horas Lectivas',
                'schedule'             => null,
                'modality'             => 'Online Asíncrono / Clases Grabadas 24/7',
                'description'          => 'Simulación probabilística de plazos y costos con curvas S probabilísticas P50/P80 para fijar reservas de contingencia.',
                'short_description'    => 'Simulación probabilística de plazos y costos con curvas S probabilísticas P50/P80 para fijar reservas de contingencia.',
                'full_description'     => 'Simulación probabilística de plazos y costos con curvas S probabilísticas P50/P80 para fijar reservas de contingencia.',
                'instructor'           => 'Ing. Edwar Marcelo',
                'instructor_title'     => 'PMP®, PMI-SP®, PMI-RMP®, ECCDA, PRINCE2 · Simulación Monte Carlo',
                'instructor_credentials' => ['PMP®', 'PMI-SP®', 'PMI-RMP®', 'PRINCE2'],
                'instructor_bio'       => [],
                'instructor_image'     => 'img/edwardmarcelo.png',
                'image'                => 'img/analisiscuantitativo.png',
                'detail_image'         => 'img/analisiscuantitativo.png',
                'image_fallback'       => null,
                'syllabus'             => [],
                'learning_outcomes'    => [],
                'benefits'             => [],
                'target_audience'      => null,
                'certification'        => 'Certificado PMO Solutions',
                'brochure_url'         => '/analisis-cuantitativo',
                'enrollment_slug'      => 'analisis-cuantitativo',
                'url'                  => 'curso/analisis-cuantitativo',
                'meta_title'           => 'Análisis Cuantitativo de Riesgos (Monte Carlo) | PMO Solutions',
                'meta_description'     => 'Simulación probabilística de plazos y costos con curvas S probabilísticas P50/P80 para fijar reservas de contingencia.',
                'canonical'            => self::CANONICAL_BASE . 'analisis-cuantitativo',
                'source_reference'     => 'PMO Original #09 + Quality Curso #30 (analisis-cuantitativo-riesgos)',
                'absorbed_sources'     => ['analisis-cuantitativo-riesgos'],
                'migration_status'     => 'exact_equivalent_enriched',
                'quality_number'       => 30,
            ],
            'eventos-compensables' => [
                'id'                   => 'pmo-10-eventos-compensables',
                'slug'                 => 'eventos-compensables',
                'title'                => 'Sustentación de Eventos Compensables en NEC4',
                'type'                 => 'Curso',
                'category'             => 'contratos',
                'category_label'       => 'Contratos & Dispute Boards',
                'icon'                 => 'fas fa-file-invoice-dollar',
                'badge'                => 'Cláusula 60 / NEC4',
                'badge_class'          => 'bg-danger text-white',
                'hours'                => '28 Horas Lectivas',
                'duration'             => '28 Horas Lectivas',
                'schedule'             => null,
                'modality'             => 'Online Asíncrono / Clases Grabadas 24/7',
                'description'          => 'Mecanismos de notificación, cuantificación de impacto en tiempo y costo (Quotations) y resolución ágil de discrepancias.',
                'short_description'    => 'Mecanismos de notificación, cuantificación de impacto en tiempo y costo (Quotations) y resolución ágil de discrepancias.',
                'full_description'     => 'Mecanismos de notificación, cuantificación de impacto en tiempo y costo (Quotations) y resolución ágil de discrepancias.',
                'instructor'           => 'Ing. Víctor Cruz',
                'instructor_title'     => 'PMP® · Especialista en Contratos NEC, Gestión Contractual y Eventos Compensables',
                'instructor_credentials' => ['PMP®', 'NEC3 / NEC4', 'LCE / OxI'],
                'instructor_bio'       => [],
                'instructor_image'     => 'img/victor.png',
                'image'                => 'img/gestioneventos.png',
                'detail_image'         => 'img/gestioneventos.png',
                'image_fallback'       => null,
                'syllabus'             => [],
                'learning_outcomes'    => [],
                'benefits'             => [],
                'target_audience'      => null,
                'certification'        => 'Certificado PMO Solutions (Equivalente Quality: 16 PDU)',
                'brochure_url'         => '/eventos-compensables',
                'enrollment_slug'      => 'eventos-compensables',
                'url'                  => 'curso/eventos-compensables',
                'meta_title'           => 'Sustentación de Eventos Compensables en NEC4 | PMO Solutions',
                'meta_description'     => 'Mecanismos de notificación, cuantificación de impacto en tiempo y costo (Quotations) y resolución ágil de discrepancias.',
                'canonical'            => self::CANONICAL_BASE . 'eventos-compensables',
                'source_reference'     => 'PMO Original #10 + Quality Curso #20 (eventos-compensables-alertas-tempranas-nec4)',
                'absorbed_sources'     => ['eventos-compensables-alertas-tempranas-nec4'],
                'migration_status'     => 'exact_equivalent_enriched',
                'quality_number'       => 20,
            ],
        ];

        $migrated = require dirname(__DIR__) . '/Data/migrated_courses.php';
        $merged = array_merge($pmoOriginals, $migrated);

        foreach ($merged as $slug => &$course) {
            $meta = self::COURSE_TAXONOMY[$slug] ?? null;
            if ($meta !== null) {
                $catSlug = $meta['category'];
                $course['category'] = $catSlug;
                $course['category_label'] = self::CATEGORY_META[$catSlug]['label'];
                $course['category_icon'] = self::CATEGORY_META[$catSlug]['icon'];
                $course['keywords'] = $meta['keywords'];
            } else {
                $fallbackCat = (string)($course['category'] ?? 'proyectos-pmo');
                if (!isset(self::CATEGORY_META[$fallbackCat])) {
                    $fallbackCat = 'proyectos-pmo';
                }
                $course['category'] = $fallbackCat;
                $course['category_label'] = self::CATEGORY_META[$fallbackCat]['label'];
                $course['category_icon'] = self::CATEGORY_META[$fallbackCat]['icon'];
                $course['keywords'] = $course['keywords'] ?? [];
            }

            $course['search_text'] = self::buildSearchText($course);
        }
        unset($course);

        return $merged;
    }
}
