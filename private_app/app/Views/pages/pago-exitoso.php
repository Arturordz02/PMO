<?php
declare(strict_types=1);
use App\Core\View;

$title = (string)($courseTitle ?? 'Capacitación');
$slug = (string)($courseSlug ?? 'capacitaciones');
$claseNum = (int)($clase ?? 1);
$priceInfo = $prices ?? ['pen' => 10, 'usd' => 3];
$pen = (int)($priceInfo['pen'] ?? 10);
$usd = (int)($priceInfo['usd'] ?? 3);
?>

<section class="py-5 bg-dark text-white position-relative" style="background: linear-gradient(180deg, #071326 0%, #0c1b33 100%); min-height: 80vh;">
  <div class="container py-4 py-md-5">
    <div class="row justify-content-center">
      <div class="col-12 col-md-9 col-lg-7 col-xl-6">

        <div class="card border border-warning border-opacity-30 rounded-4 shadow-lg text-center overflow-hidden p-4 p-md-5" style="background: rgba(15, 39, 68, 0.95);">

          <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success text-white mx-auto mb-4 shadow" style="width: 72px; height: 72px;">
            <i class="fas fa-check fs-2"></i>
          </div>

          <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3 d-inline-block">
            <i class="fas fa-check-circle me-1"></i> REGISTRO RECIBIDO CON ÉXITO
          </span>

          <h1 class="h3 fw-bold text-white mb-3">¡Gracias por completar tu registro!</h1>

          <p class="text-white-50 mb-4">
            Hemos recibido los datos de tu comprobante de pago para la <strong class="text-warning">Clase <?= $claseNum ?></strong> del curso <strong class="text-white"><?= View::e($title) ?></strong> (US$ <?= $usd ?> — S/ <?= $pen ?>).
          </p>

          <div class="alert alert-warning bg-warning bg-opacity-10 border-warning text-white small text-start mb-4" role="status">
            <div class="d-flex align-items-start gap-2">
              <i class="fas fa-info-circle text-warning fs-5 mt-1"></i>
              <div>
                <strong>Verificación manual en curso:</strong> Nuestro equipo de administración y coordinación académica verificará la transacción en la pasarela Niubiz y se comunicará contigo vía WhatsApp y correo electrónico para entregarte el enlace de acceso, usuario de plataforma y material de estudio.
              </div>
            </div>
          </div>

          <div class="d-flex flex-column gap-2">
            <a href="https://api.whatsapp.com/send?phone=51993463118&text=Hola%20PMO,%20acabo%20de%20registrar%20mi%20pago%20para%20la%20Clase%20<?= $claseNum ?>%20del%20curso%20<?= urlencode($title) ?>"
               target="_blank"
               rel="noopener noreferrer"
               class="btn btn-success text-white fw-bold py-3 shadow">
              <i class="fab fa-whatsapp me-2"></i> Contactar a Coordinación por WhatsApp
            </a>

            <a href="/curso/<?= View::e($slug) ?>" class="btn btn-outline-light py-2 mt-2">
              <i class="fas fa-book-open me-1"></i> Volver a la página del curso
            </a>

            <a href="/capacitaciones" class="btn btn-link text-white-50 small text-decoration-none mt-1">
              Ver otras capacitaciones de PMO Solutions
            </a>
          </div>

        </div>

      </div>
    </div>
  </div>
</section>
