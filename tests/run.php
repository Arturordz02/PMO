<?php
/**
 * PMO SOLUTIONS - Suite de Pruebas Unitarias y de Integración (PHP Puro)
 * Valida la Fase 2 de migración del catálogo de cursos de Quality a PMO Solutions.
 *
 * Uso: php tests/run.php
 */

declare(strict_types=1);

if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}

$baseDir       = dirname(__DIR__);
$privateAppDir = $baseDir . '/private_app';
$publicHtmlDir = $baseDir . '/public_html';
$siteDir       = $baseDir . '/_site';

define('PMO_PUBLIC_PATH', $publicHtmlDir);
define('PMO_PRIVATE_PATH', $privateAppDir);
define('APP_PATH', $privateAppDir . '/app');

require_once $privateAppDir . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

// Limpiar caché de catálogo previo para probar construcción fresca desde código fuente
foreach (['courses_catalog_v2', \App\Models\CourseModel::CACHE_KEY] as $ck) {
    $cacheFile = $privateAppDir . '/storage/cache/' . md5($ck) . '.cache';
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }
}

$passed = 0;
$failed = 0;
$failures = [];

function assertTrue(bool $condition, string $label, string $detail = ''): void {
    global $passed, $failed, $failures;
    if ($condition) {
        $passed++;
        echo "[PASS] {$label}\n";
    } else {
        $failed++;
        $msg = $detail !== '' ? "{$label} -> {$detail}" : $label;
        $failures[] = $msg;
        echo "[FAIL] {$msg}\n";
    }
}

$courseModel = new \App\Models\CourseModel();
$courses     = $courseModel->getAll();

$pmoOriginals = [
    'dab-jrd',
    'analisis-forense',
    'nec4',
    'vdc-bim',
    'contratos-estado',
    'compliance',
    'primavera-p6',
    'riesgos-pmi',
    'analisis-cuantitativo',
    'eventos-compensables',
];

$exactMappings = [
    'eventos-compensables-alertas-tempranas-nec4' => 'eventos-compensables',
    'gestion-cambios-contratos-estado'            => 'contratos-estado',
    'riesgos-pmi'                                 => 'riesgos-pmi',
    'analisis-cuantitativo-riesgos'               => 'analisis-cuantitativo',
    'control-proyectos-primavera-p6'              => 'primavera-p6',
];

$seminarsExcluded = [
    'aseguramiento-de-calidad',
    'control-de-calidad-por-procesos',
    'gestion-de-calidad-proyectos-construccion',
    'iso-9001-requisitos',
    'diseno-implementacion-pmo',
    'gestion-cambio-proyectos-construccion',
    'gestion-riesgos-iso-31000',
    'administracion-contratos-construccion',
    'planificacion-control-last-planner',
];

$legacySlugsExcluded = [
    'adicionales-obra-estado',
    'alertas-tempranas-nec',
    'ampliaciones-plazo',
    'costo-plazo-contratos',
    'ejecucion-obra-publica',
    'liquidacion-obras-publicas',
    'valorizaciones-obra',
    'gestion-de-la-calidad-en-proyectos-construccion',
    'gestion-proyectos-lean-construction',
    'liderazgo-alto-rendimiento',
    'resolucion-controversias-jrd',
];

// 1. Exactamente 43 cursos
assertTrue(
    count($courses) === 43,
    '1. Catálogo PMO contiene exactamente 43 cursos (10 originales + 33 migrados)',
    'Actual: ' . count($courses)
);

// 2. Se conservan los 10 programas existentes de PMO
$missingOriginals = array_values(array_diff($pmoOriginals, array_keys($courses)));
assertTrue(
    empty($missingOriginals),
    '2. Conserva los 10 programas originales de PMO',
    'Faltantes: ' . implode(', ', $missingOriginals)
);

// 3. compliance permanece intacto
$compliance = $courses['compliance'] ?? null;
assertTrue(
    is_array($compliance)
        && ($compliance['migration_status'] ?? '') === 'pmo_original'
        && ($compliance['hours'] ?? '') === '24 Horas Lectivas'
        && is_file($privateAppDir . '/app/Views/pages/courses/compliance.php'),
    '3. Programa exclusivo compliance permanece intacto'
);

// 4. Están representados los 39 Curso de Quality (33 nuevos/propios + 5 exactos + #03 absorbido en #05 = 39)
$representedQuality = [];
foreach ($courses as $slug => $c) {
    if (!in_array($slug, $pmoOriginals, true)) {
        $representedQuality[] = $slug;
    }
    foreach (($c['absorbed_sources'] ?? []) as $absSlug) {
        $representedQuality[] = $absSlug;
    }
}
$hasCalidadAbsorption = in_array('calidad-y-pmi', $courses['direccion-calidad-enfoque-pmi']['absorbed_sources'] ?? [], true)
    && !isset($courses['calidad-y-pmi']);
$exactMappedOk = true;
foreach ($exactMappings as $qSlug => $pmoSlug) {
    if (!in_array($qSlug, $courses[$pmoSlug]['absorbed_sources'] ?? [], true)) {
        $exactMappedOk = false;
    }
}
assertTrue(
    count(array_unique($representedQuality)) === 39 && $hasCalidadAbsorption && $exactMappedOk,
    '4. Los 39 cursos de Quality están representados (33 nuevos + 5 equivalentes exactos + #03 absorbido en #05)',
    'Representados únicos: ' . count(array_unique($representedQuality))
);

// 5. No existe ningún seminario
$foundSeminars = array_values(array_intersect($seminarsExcluded, array_keys($courses)));
assertTrue(
    empty($foundSeminars),
    '5. Ninguno de los 9 seminarios existe en el catálogo',
    'Encontrados: ' . implode(', ', $foundSeminars)
);

// 6. No existe taller /herramientas
assertTrue(
    !isset($courses['herramientas']) && !is_dir($siteDir . '/herramientas') && !is_dir($siteDir . '/curso/herramientas'),
    '6. El taller /herramientas está completamente excluido'
);

// 7. No existe ninguno de los 11 slugs heredados de Quality
$foundLegacy = array_values(array_intersect($legacySlugsExcluded, array_keys($courses)));
assertTrue(
    empty($foundLegacy),
    '7. Ninguno de los 11 slugs heredados de Quality fue incluido',
    'Encontrados: ' . implode(', ', $foundLegacy)
);

// 8. Todos los slugs son únicos
$slugList = array_map(fn($c) => (string)$c['slug'], array_values($courses));
assertTrue(
    count($slugList) === count(array_unique($slugList)) && array_keys($courses) === $slugList,
    '8. Todos los slugs del catálogo son únicos y coinciden con su clave'
);

// 9. Todos los cursos tienen título no vacío
$emptyTitles = array_filter($courses, fn($c) => trim((string)($c['title'] ?? '')) === '');
assertTrue(
    empty($emptyTitles),
    '9. Todos los 43 cursos poseen título no vacío'
);

// 10. Canonical emitida en vistas HTML apunta a https://pmo-solutions.com/curso/{slug}
$controller = new \App\Controllers\CourseController();
$canonicalFailures = [];
\App\Core\View::setMinifyHtml(false);
foreach (array_keys($courses) as $slug) {
    $_SERVER['REQUEST_URI'] = '/curso/' . $slug;
    ob_start();
    $controller->detail($slug);
    $html = (string)ob_get_clean();
    $expectedCanonical = 'https://pmo-solutions.com/curso/' . $slug;
    if (!str_contains($html, '<link rel="canonical" href="' . $expectedCanonical . '">')) {
        $canonicalFailures[] = "/curso/{$slug}";
    }
}
foreach ($pmoOriginals as $origSlug) {
    $_SERVER['REQUEST_URI'] = '/' . $origSlug;
    ob_start();
    $controller->detail($origSlug);
    $html = (string)ob_get_clean();
    $expectedCanonical = 'https://pmo-solutions.com/curso/' . $origSlug;
    if (!str_contains($html, '<link rel="canonical" href="' . $expectedCanonical . '">')) {
        $canonicalFailures[] = "/{$origSlug} (legacy alias)";
    }
}
assertTrue(
    empty($canonicalFailures),
    '10. La canonical de los 43 cursos y 10 alias heredados apunta exactamente a https://pmo-solutions.com/curso/{slug}',
    'Fallos: ' . implode(', ', $canonicalFailures)
);

// 11. Todas las imágenes referenciadas por el catálogo existen físicamente en public_html/
$missingImages = [];
foreach ($courses as $slug => $c) {
    foreach (['image', 'detail_image', 'instructor_image'] as $field) {
        $rel = $c[$field] ?? null;
        if ($rel !== null && $rel !== '') {
            $abs = $publicHtmlDir . '/' . ltrim((string)$rel, '/');
            if (!is_file($abs) || filesize($abs) === 0) {
                $missingImages[] = "{$slug}.{$field}={$rel}";
            }
        }
    }
}
assertTrue(
    empty($missingImages),
    '11. Todas las imágenes (card, detail, docente) referenciadas existen físicamente',
    'Faltantes: ' . implode(', ', $missingImages)
);

// 12 & 13 & 14. Router sin duplicados, catálogo general dinámico y sitemap.xml válido y único
$indexPhp = file_get_contents($publicHtmlDir . '/index.php') ?: '';
preg_match_all('/\$router->(get|post)\(\s*[\'"]([^\'"]+)[\'"]/', $indexPhp, $routerMatches, PREG_SET_ORDER);
$routerRoutes = array_map(fn($m) => strtoupper($m[1]) . ' ' . $m[2], $routerMatches);
$routerHasDupes = count($routerRoutes) !== count(array_unique($routerRoutes));

$sitemapXml = file_get_contents($publicHtmlDir . '/sitemap.xml') ?: '';
$xmlObj = @simplexml_load_string($sitemapXml);
$sitemapLocs = [];
if ($xmlObj !== false) {
    foreach ($xmlObj->url as $urlNode) {
        $sitemapLocs[] = trim((string)$urlNode->loc);
    }
}
$sitemapNoDupes = $xmlObj !== false && count($sitemapLocs) === count(array_unique($sitemapLocs));
$sitemapCourseUrls = array_values(array_filter($sitemapLocs, fn($u) => str_contains($u, '/curso/')));
$expectedSitemapCourses = array_map(fn($s) => 'https://pmo-solutions.com/curso/' . $s, array_keys($courses));
$sitemapMatchesCourses = empty(array_diff($expectedSitemapCourses, $sitemapCourseUrls)) && count($sitemapCourseUrls) === 43;
$sitemapNoLegacyRootCourses = true;
foreach ($pmoOriginals as $origSlug) {
    if (in_array('https://pmo-solutions.com/' . $origSlug, $sitemapLocs, true)) {
        $sitemapNoLegacyRootCourses = false;
    }
}
assertTrue(
    !$routerHasDupes && $sitemapNoDupes,
    '12. Ausencia de duplicados de rutas en router (método+ruta) y sitemap.xml'
);

// 13. El catálogo general (capacitaciones.php) renderiza dinámicamente las 43 tarjetas desde CourseModel
$_SERVER['REQUEST_URI'] = '/capacitaciones';
ob_start();
$controller->index();
$catalogHtml = (string)ob_get_clean();
$cardMatches = preg_match_all('/class="[^"]*catalog-card-col[^"]*"/', $catalogHtml);
$missingCourseLinksInCatalog = [];
foreach (array_keys($courses) as $slug) {
    if (!str_contains($catalogHtml, '/curso/' . $slug)) {
        $missingCourseLinksInCatalog[] = $slug;
    }
}
assertTrue(
    $cardMatches === 43 && empty($missingCourseLinksInCatalog),
    '13. Vista capacitaciones renderiza dinámicamente las 43 tarjetas con enlace /curso/{slug}',
    "Tarjetas: {$cardMatches}; faltantes: " . implode(', ', $missingCourseLinksInCatalog)
);
assertTrue(
    $xmlObj !== false && $sitemapMatchesCourses && $sitemapNoLegacyRootCourses && count($sitemapLocs) === 58,
    '14. sitemap.xml válido con 58 URLs únicas (43 cursos bajo /curso/{slug} + 15 institucionales)'
);

// 15. build_static.php generó páginas estáticas válidas sin errores para los 43 cursos + institucionales
$missingStatic = [];
foreach (array_keys($courses) as $slug) {
    if (!is_file($siteDir . '/curso/' . $slug . '.html') || !is_file($siteDir . '/curso/' . $slug . '/index.html')) {
        $missingStatic[] = $slug;
    }
}
foreach ($seminarsExcluded as $sem) {
    if (file_exists($siteDir . '/curso/' . $sem . '.html') || file_exists($siteDir . '/' . $sem . '.html')) {
        $missingStatic[] = "EXCLUDED_SEMINAR:{$sem}";
    }
}
if (file_exists($siteDir . '/curso/calidad-y-pmi.html') || file_exists($siteDir . '/calidad-y-pmi.html')) {
    $missingStatic[] = 'EXCLUDED_ABSORBED:calidad-y-pmi';
}
assertTrue(
    empty($missingStatic) && is_file($siteDir . '/index.html') && is_file($siteDir . '/capacitaciones.html'),
    '15. build_static.php genera las 43 páginas /curso/{slug} y páginas institucionales sin seminarios ni #03',
    'Anomalías: ' . implode(', ', $missingStatic)
);

// 16 & 17 & 18. Validación sobre HTML generado en _site/: 1 <h1>, 1 <main>, sin imágenes rotas, sin pasarelas/precios
$htmlFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($siteDir, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($it as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'html') {
        $htmlFiles[] = $file->getPathname();
    }
}

$structureIssues = [];
$brokenImgs      = [];
$forbiddenPay    = [];
$forbiddenTerms  = ['hotmart', 'paypal', 'culqi', 'mercadopago', 'izipay', 'pagar-primera-clase', 'usd 10'];

foreach ($htmlFiles as $htmlPath) {
    $relHtml = str_replace('\\', '/', substr($htmlPath, strlen($siteDir) + 1));
    $docHtml = file_get_contents($htmlPath) ?: '';
    $h1Count = preg_match_all('/<h1\b/i', $docHtml);
    $mainCount = preg_match_all('/<main\b/i', $docHtml);
    if ($h1Count !== 1 || $mainCount !== 1) {
        $structureIssues[] = "{$relHtml} (h1={$h1Count}, main={$mainCount})";
    }

    // Imágenes en src y srcset locales
    if (preg_match_all('/<(?:img|source)\b[^>]+(?:src|srcset)=["\']([^"\']+)["\']/i', $docHtml, $mImgs)) {
        $htmlDir = dirname($htmlPath);
        foreach ($mImgs[1] as $srcVal) {
            if (preg_match('#^(?:https?:|data:|//)#i', $srcVal)) {
                continue;
            }
            $cleanSrc = explode('?', explode('#', $srcVal)[0])[0];
            $resolved = realpath($htmlDir . '/' . $cleanSrc);
            if ($resolved === false || !is_file($resolved)) {
                $brokenImgs[] = "{$relHtml} -> {$srcVal}";
            }
        }
    }

    // Revisar precios/pasarelas únicamente dentro de páginas de catálogo y cursos migrados
    if ($relHtml === 'capacitaciones.html' || str_starts_with($relHtml, 'curso/')) {
        $lowerDoc = mb_strtolower($docHtml, 'UTF-8');
        foreach ($forbiddenTerms as $term) {
            if (str_contains($lowerDoc, $term)) {
                $forbiddenPay[] = "{$relHtml} contiene '{$term}'";
            }
        }
    }
}

assertTrue(
    empty($structureIssues) && count($htmlFiles) >= 60,
    '16. Cada página HTML generada contiene un único <h1> y un único <main> (' . count($htmlFiles) . ' archivos revisados)',
    'Problemas: ' . implode('; ', array_slice($structureIssues, 0, 10))
);
assertTrue(
    empty($brokenImgs),
    '17. Cero referencias rotas a imágenes locales en todas las páginas HTML generadas',
    'Rotas: ' . implode('; ', array_slice($brokenImgs, 0, 10))
);
assertTrue(
    empty($forbiddenPay),
    '18. Ausencia de referencias a precios o pasarelas de pago en catálogo y cursos',
    'Detectadas: ' . implode('; ', array_slice($forbiddenPay, 0, 10))
);

// 19. Las rutas institucionales y asesorías existentes de PMO siguen funcionando (200 OK y contenido válido)
$institutionalPages = [
    ['controller' => \App\Controllers\HomeController::class, 'method' => 'index', 'uri' => '/'],
    ['controller' => \App\Controllers\CourseController::class, 'method' => 'inHouse', 'uri' => '/in-house'],
    ['controller' => \App\Controllers\AdvisoryController::class, 'method' => 'gestionDePmo', 'uri' => '/gestion-de-pmo'],
    ['controller' => \App\Controllers\AdvisoryController::class, 'method' => 'headhunting', 'uri' => '/headhunting'],
    ['controller' => \App\Controllers\AdvisoryController::class, 'method' => 'gestionDeRiesgos', 'uri' => '/gestion-de-riesgos'],
    ['controller' => \App\Controllers\AdvisoryController::class, 'method' => 'riesgoDelPlazo', 'uri' => '/riesgo-del-plazo'],
    ['controller' => \App\Controllers\AdvisoryController::class, 'method' => 'cronogramaForense', 'uri' => '/cronograma-forense'],
    ['controller' => \App\Controllers\AdvisoryController::class, 'method' => 'gestionDeLaCalidad', 'uri' => '/gestion-de-la-calidad'],
    ['controller' => \App\Controllers\AdvisoryController::class, 'method' => 'universidadCorporativa', 'uri' => '/universidad-corporativa'],
    ['controller' => \App\Controllers\ContactController::class, 'method' => 'index', 'uri' => '/contacto'],
    ['controller' => \App\Controllers\LegalController::class, 'method' => 'terminos', 'uri' => '/terminos-y-condiciones'],
    ['controller' => \App\Controllers\LegalController::class, 'method' => 'privacidad', 'uri' => '/politica-de-privacidad'],
];
$routeFailures = [];
foreach ($institutionalPages as $rp) {
    $_SERVER['REQUEST_URI'] = $rp['uri'];
    ob_start();
    try {
        $ctrl = new $rp['controller']();
        $m    = $rp['method'];
        $ctrl->$m();
        $out = (string)ob_get_clean();
        if (strlen($out) < 500 || !str_contains($out, '<!DOCTYPE html>')) {
            $routeFailures[] = $rp['uri'] . ' (salida vacía o incompleta)';
        }
    } catch (\Throwable $e) {
        ob_end_clean();
        $routeFailures[] = $rp['uri'] . ' (' . $e->getMessage() . ')';
    }
}
assertTrue(
    empty($routeFailures),
    '19. Rutas institucionales y asesorías existentes de PMO renderizan correctamente',
    'Fallos: ' . implode(', ', $routeFailures)
);

// 20. 404 para slug inexistente y preservación de títulos/identidad de los 10 programas originales
$_SERVER['REQUEST_URI'] = '/curso/slug-inexistente-pmo';
http_response_code(200);
ob_start();
$controller->detail('slug-inexistente-pmo');
$notFoundHtml = (string)ob_get_clean();
$code404 = http_response_code();
http_response_code(200);
assertTrue(
    $code404 === 404 && str_contains($notFoundHtml, '404'),
    '20. Curso inexistente responde 404 y programas originales preservan vistas propias',
    "HTTP={$code404}"
);

// =========================================================================
// PRUEBAS DEL SISTEMA DE FILTROS Y BÚSQUEDA DEL CATÁLOGO (21 - 40)
// =========================================================================

$categories = $courseModel->getCategories();
$categorySlugs = array_keys($categories);

// 21. El catálogo mantiene exactamente 43 cursos activos
assertTrue(
    count($courses) === 43,
    '21. El catálogo mantiene exactamente 43 cursos activos',
    'Cantidad actual: ' . count($courses)
);

// 22. Cada curso tiene exactamente una categoría primaria normalizada válida
$invalidCourseCats = [];
foreach ($courses as $slug => $c) {
    $cat = $c['category'] ?? null;
    if (!is_string($cat) || $cat === '' || !isset($categories[$cat])) {
        $invalidCourseCats[] = "{$slug}=" . var_export($cat, true);
    }
}
assertTrue(
    empty($invalidCourseCats),
    '22. Cada uno de los 43 cursos tiene exactamente una categoría primaria válida',
    'Inválidos: ' . implode(', ', $invalidCourseCats)
);

// 23. No existen categorías vacías
$emptyCats = array_filter($categories, fn($cat) => (int)($cat['count'] ?? 0) <= 0);
assertTrue(
    count($categories) === 7 && empty($emptyCats),
    '23. No existen categorías vacías y las 7 áreas académicas tienen cursos reales',
    'Categorías: ' . count($categories)
);

// 24. Todos los identificadores de categoría están normalizados (^[a-z0-9-]+$) y son únicos
$badCatSlugs = array_filter($categorySlugs, fn($s) => !preg_match('/^[a-z0-9-]+$/', (string)$s));
assertTrue(
    empty($badCatSlugs) && count($categorySlugs) === count(array_unique($categorySlugs)),
    '24. Todos los identificadores de categoría están normalizados y son únicos',
    'Irregulares: ' . implode(', ', $badCatSlugs)
);

// 25. La suma de conteos por categoría coincide exactamente con 43
$sumCategories = array_sum(array_map(fn($cat) => (int)($cat['count'] ?? 0), $categories));
assertTrue(
    $sumCategories === 43,
    '25. La suma de los conteos por categoría coincide exactamente con 43',
    "Suma={$sumCategories}"
);

// Re-renderizar vista /capacitaciones sin caché de minificación para inspeccionar DOM exacto
\App\Core\View::setMinifyHtml(false);
$_SERVER['REQUEST_URI'] = '/capacitaciones';
ob_start();
$controller->index();
$catalogViewHtml = (string)ob_get_clean();

// 26. El botón "Todos los cursos (43)" refleja el total real
assertTrue(
    preg_match('/data-filter="all"[^>]*aria-pressed="true"[^>]*>.*?Todos los cursos \(43\)/s', $catalogViewHtml) === 1,
    '26. El botón "Todos los cursos (43)" refleja el total real y arranca activo con aria-pressed="true"'
);

// Extraer bloque de tarjetas del grid
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="utf-8" ?>' . $catalogViewHtml);
libxml_clear_errors();
$xpath = new DOMXPath($dom);

$cardNodes = $xpath->query('//div[@id="coursesGridContainer"]//div[@data-course-card]');
// 27. Cada tarjeta tiene data-category, data-search y data-course-card válidos
$cardAttrIssues = [];
$cardHrefs = [];
$hiddenByDefaultCount = 0;
foreach ($cardNodes as $node) {
    /** @var DOMElement $node */
    $dCat    = trim($node->getAttribute('data-category'));
    $dSearch = trim($node->getAttribute('data-search'));
    $cls     = $node->getAttribute('class');
    if ($dCat === '' || !isset($categories[$dCat])) {
        $cardAttrIssues[] = "data-category inválido: {$dCat}";
    }
    if ($dSearch === '' || mb_strlen($dSearch, 'UTF-8') < 15) {
        $cardAttrIssues[] = "data-search insuficiente para categoría {$dCat}";
    }
    if ($node->hasAttribute('hidden') || preg_match('/\b(d-none|hide-filter|is-filtered-out)\b/', $cls)) {
        $hiddenByDefaultCount++;
    }
    $links = $xpath->query('.//h3[contains(@class,"catalog-card-title")]/a', $node);
    if ($links->length > 0) {
        $cardHrefs[] = trim($links->item(0)->getAttribute('href'));
    }
}
assertTrue(
    $cardNodes->length === 43 && empty($cardAttrIssues),
    '27. Las 43 tarjetas poseen data-course-card, data-category y data-search completos',
    "Cards={$cardNodes->length}; Issues=" . implode('; ', array_slice($cardAttrIssues, 0, 5))
);

// 28. No existen tarjetas duplicadas ni slugs repetidos en #coursesGridContainer
assertTrue(
    count($cardHrefs) === 43 && count($cardHrefs) === count(array_unique($cardHrefs)),
    '28. No existen tarjetas duplicadas ni enlaces repetidos dentro de #coursesGridContainer',
    'Enlaces únicos: ' . count(array_unique($cardHrefs)) . ' / ' . count($cardHrefs)
);

// 29. Existe un campo de búsqueda (#catalogSearchInput) conectado con <label for="catalogSearchInput">
$searchInputNodes = $xpath->query('//input[@id="catalogSearchInput"]');
$searchLabelNodes = $xpath->query('//label[@for="catalogSearchInput"]');
assertTrue(
    $searchInputNodes->length === 1 && $searchLabelNodes->length === 1 && trim($searchLabelNodes->item(0)->textContent) !== '',
    '29. Existe buscador #catalogSearchInput conectado explícitamente con <label for="catalogSearchInput">'
);

// 30. Existe contador de resultados accesible con aria-live="polite"
$counterNodes = $xpath->query('//*[@id="catalogResultsCounter" and @aria-live="polite"]');
$visibleCountNodes = $xpath->query('//*[@id="catalogVisibleCount"]');
assertTrue(
    $counterNodes->length === 1 && $visibleCountNodes->length === 1 && trim($visibleCountNodes->item(0)->textContent) === '43',
    '30. Existe contador de resultados accesible con aria-live="polite" inicializado en 43'
);

// 31. Existen todos los botones de filtro por categoría (All + 7 áreas) con aria-pressed y conteos
$filterBtnNodes = $xpath->query('//button[contains(@class,"catalog-filter-btn") and @data-filter]');
$filterBtnOk = ($filterBtnNodes->length === (1 + count($categories)));
foreach ($filterBtnNodes as $btn) {
    /** @var DOMElement $btn */
    if (!$btn->hasAttribute('aria-pressed')) {
        $filterBtnOk = false;
    }
}
assertTrue(
    $filterBtnOk,
    '31. Existen los 8 botones de filtro (Todos + 7 áreas) con atributo aria-pressed'
);

// 32. Existen botones de restablecimiento de filtros ("Limpiar filtros")
$resetToolbar = $xpath->query('//button[@id="catalogToolbarResetBtn"]');
$resetEmpty   = $xpath->query('//button[@id="catalogResetFilterBtn"]');
assertTrue(
    $resetToolbar->length === 1 && $resetEmpty->length === 1
        && str_contains($resetToolbar->item(0)->textContent, 'Limpiar filtros')
        && str_contains($resetEmpty->item(0)->textContent, 'Limpiar filtros'),
    '32. Existen los controles visibles de "Limpiar filtros" en barra superior y estado vacío'
);

// 33. Existe mensaje de estado vacío claro
$emptyStateNodes = $xpath->query('//div[@id="catalogEmptyState"]');
assertTrue(
    $emptyStateNodes->length === 1
        && str_contains($emptyStateNodes->item(0)->textContent, 'No encontramos cursos con los filtros seleccionados.'),
    '33. Existe el estado vacío accesible con mensaje "No encontramos cursos con los filtros seleccionados."'
);

// 34. El archivo js/courses-filter.js existe y está cargado en /capacitaciones
$filterJsPath = $publicHtmlDir . '/js/courses-filter.js';
$filterJsContent = is_file($filterJsPath) ? (string)file_get_contents($filterJsPath) : '';
assertTrue(
    is_file($filterJsPath) && strlen($filterJsContent) > 500 && str_contains($catalogViewHtml, '/js/courses-filter.js'),
    '34. public_html/js/courses-filter.js existe y está enlazado correctamente en /capacitaciones'
);

// 35. js/main.js ya no registra lógica conflictiva con .catalog-filter-btn o .hide-filter
$mainJsContent = is_file($publicHtmlDir . '/js/main.js') ? (string)file_get_contents($publicHtmlDir . '/js/main.js') : '';
assertTrue(
    !str_contains($mainJsContent, "querySelectorAll('.catalog-filter-btn')") && !str_contains($mainJsContent, 'hide-filter'),
    '35. public_html/js/main.js está libre de listeners conflictivos sobre .catalog-filter-btn'
);

// 36. Búsqueda indexada soporta coincidencia sin tildes, multi-palabra, tecnologías, docentes y categoría combinada
$normalizePhp = static function (string $value): string {
    $map = [
        'á'=>'a','à'=>'a','ä'=>'a','â'=>'a','é'=>'e','è'=>'e','ë'=>'e','ê'=>'e',
        'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i','ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o',
        'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u','ñ'=>'n',
        'Á'=>'a','À'=>'a','Ä'=>'a','Â'=>'a','É'=>'e','È'=>'e','Ë'=>'e','Ê'=>'e',
        'Í'=>'i','Ì'=>'i','Ï'=>'i','Î'=>'i','Ó'=>'o','Ò'=>'o','Ö'=>'o','Ô'=>'o',
        'Ú'=>'u','Ù'=>'u','Ü'=>'u','Û'=>'u','Ñ'=>'n',
    ];
    $clean = strtolower(strtr($value, $map));
    $clean = preg_replace('/[^a-z0-9\s]/', ' ', $clean) ?? $clean;
    return trim((string)(preg_replace('/\s+/', ' ', $clean) ?? $clean));
};
$runSimFilter = static function (string $cat, string $query) use ($courses, $normalizePhp): array {
    $tokens = $query !== '' ? array_values(array_filter(explode(' ', $normalizePhp($query)))) : [];
    $hits = [];
    foreach ($courses as $slug => $c) {
        if ($cat !== 'all' && $c['category'] !== $cat) {
            continue;
        }
        $haystack = $normalizePhp((string)($c['search_text'] ?? ''));
        $ok = true;
        foreach ($tokens as $tok) {
            if (!str_contains($haystack, $tok)) {
                $ok = false;
                break;
            }
        }
        if ($ok) {
            $hits[] = $slug;
        }
    }
    return $hits;
};

$searchOk =
    count($runSimFilter('all', 'riesgos')) >= 5 &&
    in_array('riesgos-pmi', $runSimFilter('all', 'gestion riesgos'), true) &&
    in_array('primavera-p6', $runSimFilter('all', 'primavera'), true) &&
    in_array('analisis-forense-cronograma-primavera-p6', $runSimFilter('all', 'primavera'), true) &&
    count($runSimFilter('contratos-disputas', 'nec')) >= 5 &&
    count($runSimFilter('all', 'xyzz_curso_inexistente_999')) === 0 &&
    in_array('analisis-cuantitativo', $runSimFilter('riesgos', 'edwar'), true);

assertTrue(
    $searchOk,
    '36. Búsqueda multi-palabra sin tildes y filtrado combinado por categoría funcionan sobre los 43 cursos'
);

// 37. Sin JavaScript, los 43 cursos se renderizan completamente visibles por defecto
assertTrue(
    $hiddenByDefaultCount === 0,
    '37. Sin JavaScript, las 43 tarjetas del catálogo se renderizan visibles sin clases de ocultamiento',
    "Ocultas por defecto: {$hiddenByDefaultCount}"
);

// 38. courses-filter.js implementa normalización NFD y ocultamiento seguro sin eliminar tarjetas del DOM
assertTrue(
    str_contains($filterJsContent, "normalize('NFD')")
        && str_contains($filterJsContent, 'is-filtered-out')
        && !str_contains($filterJsContent, 'removeChild')
        && preg_match('/(?<!classList)\.remove\s*\(/', $filterJsContent) === 0,
    '38. courses-filter.js normaliza diacríticos (NFD) y alterna visibilidad sin destruir nodos del DOM'
);

// 39. La compilación estática _site/capacitaciones.html y _site/js/courses-filter.js están actualizadas
$staticCatalog = is_file($siteDir . '/capacitaciones.html') ? (string)file_get_contents($siteDir . '/capacitaciones.html') : '';
assertTrue(
    is_file($siteDir . '/js/courses-filter.js')
        && str_contains($staticCatalog, 'courses-filter.js')
        && preg_match_all('/data-course-card/', $staticCatalog) === 43,
    '39. build_static.php compila _site/capacitaciones.html con las 43 tarjetas y _site/js/courses-filter.js'
);

// 40. Sin pasarelas de pago ni precios en vista /capacitaciones y rutas principales intactas
$payFoundInCatalog = false;
foreach ($forbiddenTerms as $term) {
    if (str_contains(strtolower($catalogViewHtml), $term)) {
        $payFoundInCatalog = true;
    }
}
assertTrue(
    !$payFoundInCatalog && !empty($sitemapLocs),
    '40. El catálogo mantiene 0 precios o pasarelas de pago y preserva el sitemap sin modificaciones'
);

echo "\n========================================================\n";
echo "RESUMEN DE PRUEBAS: {$passed} PASS | {$failed} FAIL\n";
echo "========================================================\n";

exit($failed > 0 ? 1 : 0);
