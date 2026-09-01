<?php
/**
 * Script de Verificación Integral de Rutas MVC
 */

$routes = [
    '/'                     => 'PMO Solutions | Construimos Soluciones',
    '/capacitaciones'       => 'Catálogo de Capacitaciones 2026',
    '/contacto'             => 'Contacto',
    '/libro-de-reclamaciones' => 'Libro de Reclamaciones Virtual',
    '/nec4'                 => 'Contratos NEC4',
    '/primavera-p6'         => 'Oracle Primavera P6',
    '/dab-jrd'              => 'Dispute Boards',
    '/vdc-bim'              => 'Virtual Design and Construction',
    '/contratos-estado'     => 'Gestión del Cambio',
    '/compliance'           => 'Compliance',
    '/riesgos-pmi'          => 'Gestión Integral de Riesgos',
    '/analisis-cuantitativo' => 'Análisis Cuantitativo',
    '/analisis-forense'     => 'Análisis Forense',
    '/eventos-compensables' => 'Eventos Compensables',
    '/ruta-inexistente-test' => '404: Desvío en la Ruta Crítica'
];

echo "\n======================================================================\n";
echo " VERIFICANDO RENDERIZADO DE RUTAS MVC (PMO SOLUTIONS)\n";
echo "======================================================================\n";
$allOk = true;

foreach ($routes as $uri => $expectedTitleSubstring) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';

    ob_start();
    require __DIR__ . '/../index.php';
    $output = ob_get_clean();

    $found = strpos($output, $expectedTitleSubstring) !== false;
    $status = $found ? "\033[32m✔ [OK]\033[0m" : "\033[31m✖ [FAIL]\033[0m";
    echo "  {$status} Ruta: {$uri} -> Contiene: '{$expectedTitleSubstring}'\n";

    if (!$found) {
        $allOk = false;
    }
}

if ($allOk) {
    echo "\n\033[32m✓ TODAS LAS RUTAS MVC SE RENDERIZARON CORRECTAMENTE AL 100%.\033[0m\n\n";
} else {
    echo "\n\033[31m✗ HUBO FALLOS EN ALGUNAS RUTAS.\033[0m\n\n";
}

