<?php
use App\Core\View;
use App\Core\Env;

/**
 * PMO SOLUTIONS - Layout Maestro (Main Layout)
 * 
 * Contenedor global HTML5 reutilizado por todas las vistas de la aplicación.
 */

// Cálculo seguro de URL canónica dinámica (SEO & Open Graph)
$canonicalBase = rtrim(Env::string('PMO_SITE_URL', 'https://pmo-solutions.com'), '/');
$rawRequestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath   = parse_url($rawRequestUri, PHP_URL_PATH);
if ($requestPath === null || $requestPath === false || $requestPath === '') {
    $requestPath = '/';
}
$trimmedPath   = trim((string)$requestPath, '/');
$canonicalUrl  = ($trimmedPath === '') ? ($canonicalBase . '/') : ($canonicalBase . '/' . $trimmedPath);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= View::e(\App\Core\Csrf::getToken()) ?>">
  <title><?= View::e($pageTitle ?? 'PMO Solutions | Construimos Soluciones') ?></title>
  <meta name="description" content="<?= View::e($metaDescription ?? 'PMO Solutions: Tu socio estratégico en consultoría y capacitación de alta ingeniería en construcción.') ?>">
  <link rel="canonical" href="<?= View::e($canonicalUrl) ?>">
  
  <!-- Open Graph / Meta Tags para Redes Sociales & WhatsApp -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= View::e($canonicalUrl) ?>">
  <meta property="og:title" content="<?= View::e($pageTitle ?? 'PMO Solutions') ?>">
  <meta property="og:description" content="<?= View::e($metaDescription ?? '') ?>">
  <meta property="og:image" content="img/LogoPMO.png">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= View::e($pageTitle ?? 'PMO Solutions') ?>">
  <meta name="twitter:description" content="<?= View::e($metaDescription ?? '') ?>">
  <meta name="twitter:image" content="img/LogoPMO.png">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= View::asset('/img/LogoPMO.png') ?>">
  
  <!-- Google Fonts: Plus Jakarta Sans & Montserrat -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 CSS (Fixed v5.3.3) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- FontAwesome 6 Icons (Fixed v6.5.1) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Animate.css (Fixed v4.1.1) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  
  <!-- AOS - Animate On Scroll (Fixed v2.3.4) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css"/>
  
  <!-- Hover.css (Fixed v2.3.1) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/hover.css/2.3.1/css/hover-min.css"/>
  
  <!-- Custom Corporate CSS (Versioned) -->
  <link rel="stylesheet" href="<?= View::asset('/css/styles.css') ?>">

  <!-- Versioned Asset Metadata for Dynamic Module & Library Loaders -->
  <meta name="asset-utils" content="<?= View::asset('/js/core/utils.js') ?>">
  <meta name="asset-chartjs" content="<?= View::asset('/js/vendor/chart.umd.min.js') ?>">
  <meta name="asset-jspdf" content="<?= View::asset('/js/vendor/jspdf.umd.min.js') ?>">
  <meta name="asset-styles" content="<?= View::asset('/css/styles.css') ?>">

  <?php if (!empty($extraCss)): ?>
    <?php foreach ($extraCss as $css): ?>
      <link rel="stylesheet" href="<?= View::e(str_starts_with($css, 'http') ? $css : View::asset($css)) ?>">
    <?php endforeach; ?>
  <?php endif; ?>
</head>
<body>

  <!-- Barra de Anuncios y Navegación Principal -->
  <?php View::partial('navbar', ['activeNav' => $activeNav ?? '']); ?>

  <!-- Contenido Dinámico de la Página -->
  <main id="main-content">
    <?= $content ?>
  </main>

  <!-- Toast Emergente de Matrículas 2026 -->
  <?php View::partial('toast'); ?>

  <!-- Pie de Página Global -->
  <?php View::partial('footer'); ?>

  <!-- Chatbot Guiado PMO Solutions (Módulo Aislado) -->
  <?php
  $chatbotView = dirname(__DIR__, 3) . '/modules/chatbot/chatbot-view.php';
  if (file_exists($chatbotView)) {
      require $chatbotView;
  }
  ?>

  <!-- Bootstrap 5 JS Bundle (Fixed v5.3.3) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- AOS JS Script (Fixed v2.3.4) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
  
  <!-- Custom JavaScript (Versioned) -->
  <script src="<?= View::asset('/js/main.js') ?>"></script>

  <?php if (!empty($extraJs)): ?>
    <?php foreach ($extraJs as $script): ?>
      <?php if (is_array($script)): ?>
        <?php $scriptSrc = str_starts_with($script['src'], 'http') ? $script['src'] : View::asset($script['src']); ?>
        <script src="<?= View::e($scriptSrc) ?>"<?= !empty($script['type']) ? ' type="' . View::e($script['type']) . '"' : '' ?><?= !empty($script['defer']) ? ' defer' : '' ?>></script>
      <?php else: ?>
        <?php $scriptSrc = str_starts_with($script, 'http') ? $script : View::asset($script); ?>
        <script src="<?= View::e($scriptSrc) ?>"></script>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>

