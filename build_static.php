<?php
/**
 * PMO SOLUTIONS - PREVIEW STATIC GENERATOR (GitHub Pages)
 * Compila las vistas del sistema MVC a archivos estáticos HTML para GitHub Pages.
 *
 * Este generador permite que el repositorio se previsualice en GitHub Pages
 * sin alterar la estructura MVC ni el funcionamiento de GoDaddy en producción.
 */

declare(strict_types=1);

if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}

$baseDir       = __DIR__;
$privateAppDir = $baseDir . '/private_app';
$publicHtmlDir = $baseDir . '/public_html';
$outputDir     = $baseDir . '/_site';

define('PMO_PUBLIC_PATH', $publicHtmlDir);
define('PMO_PRIVATE_PATH', $privateAppDir);

// 1. Configurar entorno para preview estático
putenv('PMO_SITE_URL=https://arturordz02.github.io/PMO');
$_ENV['PMO_SITE_URL'] = 'https://arturordz02.github.io/PMO';

// Cargar Autoloader PSR-4 nativo de PMO
require_once $privateAppDir . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

// Cargar configuración de la aplicación
$configFile = $privateAppDir . '/config/config.php';
$config = file_exists($configFile) ? require $configFile : [];
date_default_timezone_set($config['app']['timezone'] ?? 'America/Lima');

// Desactivar minificación agresiva durante la compilación para transformaciones limpias
\App\Core\View::setMinifyHtml(false);

echo "========================================================\n";
echo " PMO SOLUTIONS - GENERADOR ESTÁTICO DE GITHUB PAGES\n";
echo "========================================================\n\n";

// 2. Limpiar y preparar directorio de salida _site/
echo "--> [1/5] Preparando directorio temporal _site/...\n";
if (is_dir($outputDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($outputDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
} else {
    mkdir($outputDir, 0777, true);
}

// 3. Copiar assets estáticos (CSS, JS, Imágenes, Favicons, Fuentes, etc.)
echo "--> [2/5] Copiando assets estáticos desde public_html/...\n";

function copyStaticDirectory(string $src, string $dst): void {
    $dir = opendir($src);
    @mkdir($dst, 0777, true);
    while (false !== ($file = readdir($dir))) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $srcPath = $src . '/' . $file;
        $dstPath = $dst . '/' . $file;

        if (is_dir($srcPath)) {
            if (in_array($file, ['backend', 'private_app', 'storage', '.git'], true)) {
                continue;
            }
            copyStaticDirectory($srcPath, $dstPath);
        } else {
            if (
                str_ends_with($file, '.php') ||
                str_ends_with($file, '.htaccess') ||
                str_starts_with($file, '.env') ||
                $file === 'env.php'
            ) {
                continue;
            }
            copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}

copyStaticDirectory($publicHtmlDir, $outputDir);

// Copiar archivo knowledge.json del chatbot para fallback estático
$chatbotKnowledgeSrc = $privateAppDir . '/modules/chatbot/knowledge.json';
$chatbotKnowledgeDst = $outputDir . '/modules/chatbot/knowledge.json';
if (file_exists($chatbotKnowledgeSrc)) {
    @mkdir(dirname($chatbotKnowledgeDst), 0777, true);
    copy($chatbotKnowledgeSrc, $chatbotKnowledgeDst);
}

// Crear archivo .nojekyll en el output
file_put_contents($outputDir . '/.nojekyll', '');

// 4. Descubrir todas las rutas públicas del sistema desde modelos y vistas
echo "--> [3/5] Descubriendo rutas públicas de PMO...\n";

$courseModel       = new \App\Models\CourseModel();
$allCourses        = $courseModel->getAll();
$courseCategories  = $courseModel->getCategories();
$softSkillsModel   = new \App\Models\SoftSkillsModel();
$softSkillsCatalog = $softSkillsModel->getCatalog();

$routes = [];

// A. Páginas generales en Views/pages/
$generalFiles = glob($privateAppDir . '/app/Views/pages/*.php') ?: [];
foreach ($generalFiles as $file) {
    $slug = basename($file, '.php');
    if ($slug === '.gitkeep') {
        continue;
    }
    $routes[$slug] = [
        'type'   => 'page',
        'view'   => $slug,
        'slug'   => $slug,
        'layout' => ($slug === '404') ? 'error' : 'main',
    ];
}

// B. Asesorías corporativas en Views/pages/advisory/ (7 asesorías clave)
$advisoryFiles = glob($privateAppDir . '/app/Views/pages/advisory/*.php') ?: [];
foreach ($advisoryFiles as $file) {
    $slug = basename($file, '.php');
    if ($slug === '.gitkeep') {
        continue;
    }
    $routes[$slug] = [
        'type'   => 'advisory',
        'view'   => 'advisory/' . $slug,
        'slug'   => $slug,
        'layout' => 'main',
    ];
}

// C. Cursos oficiales en /curso/{slug} generados dinámicamente desde CourseModel
foreach ($allCourses as $courseSlug => $courseInfo) {
    $customViewFile = $privateAppDir . '/app/Views/pages/courses/' . $courseSlug . '.php';
    $courseView     = is_file($customViewFile) ? ('courses/' . $courseSlug) : 'courses/course-detail';
    $routeKey       = 'curso/' . $courseSlug;

    $routes[$routeKey] = [
        'type'        => 'course',
        'view'        => $courseView,
        'slug'        => $routeKey,
        'course_slug' => $courseSlug,
        'layout'      => 'main',
    ];

    // Conservar rutas heredadas directas para las 10 vistas originales de PMO
    if (is_file($customViewFile)) {
        $routes[$courseSlug] = [
            'type'        => 'course_legacy',
            'view'        => 'courses/' . $courseSlug,
            'slug'        => $courseSlug,
            'course_slug' => $courseSlug,
            'layout'      => 'main',
        ];
    }
}

// Metadata de las 7 Asesorías Corporativas
$advisoryMetadata = [
    'gestion-de-pmo' => [
        'pageTitle'       => 'Gestión de Proyectos & PMO | PMO Solutions',
        'metaDescription' => 'Asesoría especializada para orientar el diseño, adopción y consolidación de Oficinas de Gestión de Proyectos (PMO) en organizaciones de ingeniería y construcción.',
    ],
    'headhunting' => [
        'pageTitle'       => 'Headhunting Especializado para la Construcción | PMO Solutions',
        'metaDescription' => 'Atracción y selección de talento senior de alto rendimiento para proyectos de ingeniería y construcción. Ingenieros evaluando ingenieros con rigor técnico.',
    ],
    'gestion-de-riesgos' => [
        'pageTitle'       => 'Gestión del Riesgo Técnico y Operativo | PMO Solutions',
        'metaDescription' => 'Identificación, análisis cualitativo/cuantitativo y monitoreo estratégico de riesgos técnicos en megaproyectos bajo estándares PMI® e ISO 31000.',
    ],
    'riesgo-del-plazo' => [
        'pageTitle'       => 'Evaluación del Riesgo del Cumplimiento del Plazo | PMO Solutions',
        'metaDescription' => 'Metodología predictiva de la Curva de Liberación® para prevenir el Síndrome del 90% y asegurar la fecha real de entrega en obras de construcción.',
    ],
    'cronograma-forense' => [
        'pageTitle'       => 'Análisis Forense del Cronograma & Collapse As-Built | PMO Solutions',
        'metaDescription' => 'Evaluación pericial retrospectiva de retrasos, impactos en la ruta crítica (CPM) y deslinde de responsabilidades contractuales según estándares AACE® y SCL.',
    ],
    'gestion-de-la-calidad' => [
        'pageTitle'       => 'Gestión de la Calidad en Proyectos | PMO Solutions',
        'metaDescription' => 'Estandarización, aseguramiento, auditoría técnica y gestión de postventa para reducir la variabilidad y retrabajos en obras de ingeniería y edificación.',
    ],
    'universidad-corporativa' => [
        'pageTitle'       => 'Universidad Corporativa & Formación Empresarial | PMO Solutions',
        'metaDescription' => 'Diseño y estructuración de academias corporativas y mallas de capacitación a medida para potenciar el talento técnico y la productividad en organizaciones.',
    ],
];

// Lista de slugs conocidos para transformar enlaces internos (asegurar string para claves numéricas como '404')
$knownSlugs = array_map(fn($s) => (string)$s, array_keys($routes));

// Función de transformación de HTML para preview estático
function transformHtml(string $html, string $prefix, array $knownSlugs): string {
    // 1. Reemplazar enlaces a Home ('/' o './')
    $html = preg_replace('/(href=["\'])(\/|\.\/)((?:#[a-zA-Z0-9_\-]+)?)(["\'])/', '$1' . $prefix . 'index.html$3$4', $html);

    // 2. Reemplazar enlaces internos conocidos a sus archivos .html
    $escapedSlugs = array_map(fn($s) => preg_quote((string)$s, '/'), $knownSlugs);
    usort($escapedSlugs, fn($a, $b) => strlen($b) <=> strlen($a));
    $slugPattern = implode('|', $escapedSlugs);

    // Enlaces con barra inicial: href="/slug" o href="/curso/slug"
    $html = preg_replace_callback(
        '/(href=["\'])\/(' . $slugPattern . ')((?:#[a-zA-Z0-9_\-]+|\?[^"\'\s>]*)?)(["\'])/',
        function ($matches) use ($prefix) {
            $slug = $matches[2];
            $target = ($slug === 'home') ? 'index.html' : ($slug . '.html');
            return $matches[1] . $prefix . $target . $matches[3] . $matches[4];
        },
        $html
    );

    // Enlaces relativos sin barra inicial: href="slug" o href="curso/slug"
    $html = preg_replace_callback(
        '/(href=["\'])(' . $slugPattern . ')((?:#[a-zA-Z0-9_\-]+|\?[^"\'\s>]*)?)(["\'])/',
        function ($matches) use ($prefix) {
            $slug = $matches[2];
            $target = ($slug === 'home') ? 'index.html' : ($slug . '.html');
            return $matches[1] . $prefix . $target . $matches[3] . $matches[4];
        },
        $html
    );

    // 3. Normalizar rutas de assets estáticos (CSS, JS, Imágenes, Módulos)
    $html = preg_replace_callback(
        '/(href|src|srcset|content)=(["\'])(\/?)(css|js|img|modules)\//',
        function ($matches) use ($prefix) {
            $attr  = $matches[1];
            $quote = $matches[2];
            $dir   = $matches[4];
            return "{$attr}={$quote}{$prefix}{$dir}/";
        },
        $html
    );

    // 4. Limpieza de cualquier mención a localhost en enlaces/recursos
    $html = str_replace(
        ['http://localhost', 'https://localhost', 'http://127.0.0.1', 'https://127.0.0.1'],
        'https://arturordz02.github.io/PMO',
        $html
    );

    return $html;
}

// 5. Compilar vistas a HTML
echo "--> [4/5] Compilando vistas de PMO a archivos estáticos...\n";

$renderedCount = 0;

foreach ($routes as $routePath => $route) {
    $routePath = (string)$routePath;
    $_SERVER['REQUEST_URI'] = ($routePath === 'home') ? '/' : ('/' . $routePath);

    $viewData = [];

    if ($routePath === 'home') {
        $viewData = [
            'pageTitle'       => 'PMO Solutions | Construimos Soluciones en Gestión de Proyectos de Construcción',
            'metaDescription' => 'PMO Solutions: Tu socio estratégico en consultoría, asesoría y capacitación de alta ingeniería en construcción (DAB NEC, Análisis Forense, Contratos NEC4, VDC-BIM).',
            'activeNav'       => 'home',
            'courses'         => $allCourses,
        ];
    } elseif ($routePath === 'in-house') {
        $viewData = [
            'pageTitle'       => 'Capacitaciones In-House | Formación a la Medida para Empresas - PMO Solutions',
            'metaDescription' => 'Programas de capacitación técnica corporativa in-house diseñados a medida para empresas de ingeniería y construcción. Formación práctica en Contratos NEC4/FIDIC, Dispute Boards, Análisis Forense, VDC-BIM y Gestión de Riesgos.',
            'activeNav'       => 'in-house',
        ];
    } elseif ($routePath === 'capacitaciones') {
        $viewData = [
            'pageTitle'       => 'Catálogo de Capacitaciones 2026 | PMO Solutions',
            'metaDescription' => 'Conoce nuestros programas de especialización ejecutiva e in-house en Contratos NEC4, Juntas de Resolución (DAB), Peritajes Forenses, Calidad, Oficina Técnica, Primavera P6 y VDC-BIM.',
            'activeNav'       => 'capacitaciones',
            'courses'         => $allCourses,
            'categories'      => $courseCategories,
            'extraJs'         => ['/js/courses-filter.js'],
        ];
    } elseif ($route['type'] === 'course' || $route['type'] === 'course_legacy') {
        $cSlug      = (string)$route['course_slug'];
        $courseInfo = $allCourses[$cSlug];
        $viewData   = [
            'pageTitle'       => $courseInfo['meta_title'] ?? ($courseInfo['title'] . ' | PMO Solutions'),
            'metaDescription' => $courseInfo['meta_description'] ?? ($courseInfo['short_description'] ?? $courseInfo['description']),
            'canonical'       => $courseInfo['canonical'] ?? ('https://pmo-solutions.com/curso/' . $cSlug),
            'ogImage'         => $courseInfo['image'] ?? 'img/LogoPMO.png',
            'activeNav'       => 'capacitaciones',
            'course'          => $courseInfo,
            'relatedCourses'  => $courseModel->getRelated($cSlug, 3),
        ];
    } elseif ($route['type'] === 'advisory') {
        $meta = $advisoryMetadata[$routePath] ?? [];
        $viewData = [
            'pageTitle'       => $meta['pageTitle'] ?? (ucwords(str_replace('-', ' ', $routePath)) . ' | PMO Solutions'),
            'metaDescription' => $meta['metaDescription'] ?? 'Asesoría técnica y consultoría estratégica de alto rendimiento en ingeniería de construcción.',
            'activeNav'       => 'asesoria',
        ];
    } elseif ($routePath === 'contacto') {
        $viewData = [
            'pageTitle'       => 'Contacto & Asesoría Corporativa | PMO Solutions',
            'metaDescription' => 'Ponte en contacto con PMO Solutions. Asesoría técnica, consultas sobre cursos in-house, peritajes, capacitaciones y servicios de consultoría en ingeniería.',
            'activeNav'       => 'contacto',
        ];
    } elseif ($routePath === 'evaluacion-habilidades') {
        $viewData = [
            'pageTitle'       => 'Evaluación de Habilidades Blandas | PMO Solutions',
            'metaDescription' => 'Evalúa tus competencias blandas: comunicación, trabajo en equipo, resolución de problemas y adaptabilidad. Recibe un reporte personalizado con recomendaciones.',
            'activeNav'       => 'capacitaciones',
            'catalog'         => $softSkillsCatalog,
            'total_questions' => count($softSkillsCatalog) * \App\Models\SoftSkillsModel::QUESTIONS_PER_COMPETENCY,
            'extraJs'         => [
                ['src' => '/js/modules/soft-skills.js', 'type' => 'module'],
            ],
        ];
    } elseif ($routePath === 'libro-de-reclamaciones') {
        $viewData = [
            'pageTitle'       => 'Libro de Reclamaciones Virtual | PMO Solutions',
            'metaDescription' => 'Libro de Reclamaciones Virtual de PMO Solutions conforme a la Ley N° 29571 (Código de Protección y Defensa del Consumidor) y directivas de INDECOPI.',
            'activeNav'       => 'reclamos',
        ];
    } elseif ($routePath === 'terminos-y-condiciones') {
        $viewData = [
            'pageTitle'       => 'Términos y Condiciones de Uso | PMO Solutions',
            'metaDescription' => 'Términos y Condiciones de Uso de la plataforma digital y servicios formativos y de consultoría de PMO Solutions.',
            'activeNav'       => 'terminos',
        ];
    } elseif ($routePath === 'politica-de-privacidad') {
        $viewData = [
            'pageTitle'       => 'Política de Privacidad y Protección de Datos | PMO Solutions',
            'metaDescription' => 'Política de Privacidad y Tratamiento de Datos Personales de PMO Solutions conforme a la legislación aplicable.',
            'activeNav'       => 'privacidad',
        ];
    } elseif ($routePath === '404') {
        $viewData = [
            'pageTitle'       => '404: Desvío en la Ruta Crítica | PMO Solutions',
            'metaDescription' => 'Error 404 - Página no encontrada. Ocurrió un desvío no planificado en la obra.',
            'activeNav'       => '',
        ];
    }

    try {
        ob_start();
        \App\Core\View::render($route['view'], $viewData, $route['layout']);
        $rawHtml = (string)ob_get_clean();

        $depth      = substr_count($routePath, '/');
        $rootPrefix = $depth > 0 ? str_repeat('../', $depth) : './';
        $subPrefix  = str_repeat('../', $depth + 1);

        $rootHtml = transformHtml($rawHtml, $rootPrefix, $knownSlugs);

        if ($routePath === 'home') {
            file_put_contents($outputDir . '/index.html', $rootHtml);
        } elseif ($routePath === '404') {
            file_put_contents($outputDir . '/404.html', $rootHtml);
        } else {
            $htmlFile = $outputDir . '/' . $routePath . '.html';
            if (!is_dir(dirname($htmlFile))) {
                mkdir(dirname($htmlFile), 0777, true);
            }
            file_put_contents($htmlFile, $rootHtml);

            $subDirHtml = transformHtml($rawHtml, $subPrefix, $knownSlugs);
            $subDir     = $outputDir . '/' . $routePath;
            if (!is_dir($subDir)) {
                mkdir($subDir, 0777, true);
            }
            file_put_contents($subDir . '/index.html', $subDirHtml);
        }

        $renderedCount++;
        $typeLabel = strtoupper($route['type']);
        echo "  [OK] ({$typeLabel}) /{$routePath}\n";
    } catch (\Throwable $e) {
        echo "  [ERROR] Falló al compilar {$routePath}: " . $e->getMessage() . "\n";
    }
}

if (!file_exists($outputDir . '/404.html')) {
    echo "  [AVISO] Generando 404.html de respaldo...\n";
}

echo "\n--> [5/5] Resumen de Generación Estática:\n";
echo "  - Total de rutas compiladas: {$renderedCount}\n";
echo "  - Salida generada en: {$outputDir}\n";
echo "  - .nojekyll: OK\n";
echo "  - Compatibilidad: URLs .html y subcarpetas /curso/{slug}/index.html\n";
echo "\n¡Compilación estática completada exitosamente!\n";
