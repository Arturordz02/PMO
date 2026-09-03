<?php
use App\Core\View;

/**
 * PMO SOLUTIONS - Layout Maestro (Main Layout)
 * 
 * Contenedor global HTML5 reutilizado por todas las vistas de la aplicación.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= View::e($pageTitle ?? 'PMO Solutions | Construimos Soluciones') ?></title>
  <meta name="description" content="<?= View::e($metaDescription ?? 'PMO Solutions: Tu socio estratégico en consultoría y capacitación de alta ingeniería en construcción.') ?>">
  
  <!-- Open Graph / Meta Tags para Redes Sociales & WhatsApp -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://pmo-solutions.com/">
  <meta property="og:title" content="<?= View::e($pageTitle ?? 'PMO Solutions') ?>">
  <meta property="og:description" content="<?= View::e($metaDescription ?? '') ?>">
  <meta property="og:image" content="img/LogoPMO.png">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= View::e($pageTitle ?? 'PMO Solutions') ?>">
  <meta name="twitter:description" content="<?= View::e($metaDescription ?? '') ?>">
  <meta name="twitter:image" content="img/LogoPMO.png">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="img/LogoPMO.png">
  
  <!-- Google Fonts: Plus Jakarta Sans & Montserrat -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- FontAwesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  
  <!-- AOS - Animate On Scroll -->
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css"/>
  
  <!-- Hover.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/hover.css/2.3.1/css/hover-min.css"/>
  
  <!-- Custom Corporate CSS -->
  <link rel="stylesheet" href="css/styles.css">
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

  <!-- Modal Obligatorio de Términos y Condiciones -->
  <?php View::partial('terms-modal'); ?>

  <!-- Pie de Página Global -->
  <?php View::partial('footer'); ?>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- AOS JS Script & Initialization -->
  <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      if (typeof AOS !== 'undefined') {
        AOS.init({
          duration: 800,
          once: true,
          offset: 80
        });
      }
    });
  </script>
  
  <!-- Custom JavaScript -->
  <script src="js/main.js"></script>
</body>
</html>

