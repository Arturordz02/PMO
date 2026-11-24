<?php
declare(strict_types=1);
use App\Core\View;

$courseTitle = (string)($course['title'] ?? 'Capacitación Profesional');
$slug = (string)($courseSlug ?? 'nec4');
$initialClassNum = (int)($initialClass ?? 1);
if ($initialClassNum < 1 || $initialClassNum > 8) {
    $initialClassNum = 1;
}
$prices = $classPrices ?? \App\Controllers\PaymentController::CLASS_PRICES;
$currentPrice = $prices[$initialClassNum] ?? ['pen' => 10, 'usd' => 3];
$niubizLink = (string)($niubizUrl ?? \App\Controllers\PaymentController::NIUBIZ_URL);
?>

<section class="py-5 bg-dark text-white position-relative" style="background: linear-gradient(180deg, #071326 0%, #0c1b33 100%);">
  <div class="container py-2 py-md-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="/" class="text-white-50 text-decoration-none">Inicio</a></li>
        <li class="breadcrumb-item"><a href="/capacitaciones" class="text-white-50 text-decoration-none">Capacitaciones</a></li>
        <li class="breadcrumb-item"><a href="/curso/<?= View::e($slug) ?>" class="text-white-50 text-decoration-none"><?= View::e($courseTitle) ?></a></li>
        <li class="breadcrumb-item active text-warning" aria-current="page">Inscripción por Clase</li>
      </ol>
    </nav>

    <div class="row justify-content-center">
      <div class="col-12 col-lg-10 col-xl-9">

        <!-- Tarjeta Principal de Pago -->
        <div class="card border border-warning border-opacity-25 rounded-4 shadow-lg overflow-hidden" style="background: rgba(15, 39, 68, 0.95);">

          <!-- Encabezado -->
          <div class="card-header bg-black bg-opacity-30 border-bottom border-white border-opacity-10 p-4 p-md-5 text-center">
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3 shadow-sm">
              <i class="fas fa-credit-card me-2"></i> PAGO SEGURO POR CLASE VÍA NIUBIZ
            </span>
            <h1 class="h2 fw-bold text-white mb-2"><?= View::e($courseTitle) ?></h1>
            <p class="text-white-50 mb-0">
              Selecciona libremente la clase que deseas cursar y realiza el abono correspondiente en la pasarela oficial de Niubiz.
            </p>
          </div>

          <div class="card-body p-4 p-md-5">

            <!-- Selector de Clases (1 a 8) -->
            <div class="mb-4">
              <label class="form-label text-warning small fw-bold text-uppercase mb-3">
                <i class="fas fa-calendar-check me-1"></i> Paso 1: Selecciona la Clase a la que deseas inscribirte
              </label>

              <div class="row g-2 g-md-3" id="classSelectorGrid">
                <?php foreach ($prices as $cNum => $p): ?>
                  <?php $isSelected = ($cNum === $initialClassNum); ?>
                  <div class="col-6 col-md-3">
                    <button type="button"
                            class="btn w-100 p-3 rounded-3 text-start class-select-btn <?= $isSelected ? 'btn-warning text-dark active-class' : 'btn-outline-light' ?>"
                            data-class-num="<?= $cNum ?>"
                            data-pen="<?= $p['pen'] ?>"
                            data-usd="<?= $p['usd'] ?>"
                            style="transition: all 0.2s ease;">
                      <div class="small fw-bold text-uppercase opacity-75">Clase <?= $cNum ?></div>
                      <div class="fs-5 fw-extrabold my-1">US$ <?= $p['usd'] ?></div>
                      <div class="small opacity-75">S/ <?= $p['pen'] ?></div>
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Resumen de Tarifa Seleccionada -->
            <div class="p-4 rounded-3 mb-4 border border-warning border-opacity-25" style="background: rgba(0, 0, 0, 0.25);">
              <div class="row align-items-center gy-3">
                <div class="col-12 col-md-7">
                  <div class="small text-white-50 text-uppercase fw-semibold mb-1">Sesión seleccionada:</div>
                  <h3 class="h4 text-warning fw-bold mb-1" id="selectedClassTitle">Clase <?= $initialClassNum ?></h3>
                  <div class="text-white small">
                    Monto autorizado: <strong class="text-white" id="selectedPriceSummary">S/ <?= $currentPrice['pen'] ?> — US$ <?= $currentPrice['usd'] ?></strong>
                  </div>
                </div>
                <div class="col-12 col-md-5 text-md-end">
                  <div class="display-6 fw-bold text-white mb-0" id="displayUsd">US$ <?= $currentPrice['usd'] ?></div>
                  <small class="text-white-50">o su equivalente S/ <span id="displayPen"><?= $currentPrice['pen'] ?></span></small>
                </div>
              </div>
            </div>

            <!-- Aviso de Verificación Manual -->
            <div class="alert alert-warning bg-warning bg-opacity-10 border-warning text-white small d-flex align-items-start gap-2 mb-4" role="status">
              <i class="fas fa-info-circle text-warning fs-5 mt-1"></i>
              <div>
                <strong>Aviso importante:</strong> El pago quedará pendiente de verificación manual. En la pasarela de Niubiz deberás ingresar manualmente el monto en Dólares Americanos (<strong><span id="instructionUsd">US$ <?= $currentPrice['usd'] ?></span></strong>) correspondiente a tu clase. Una vez efectuado el abono, registra tu número de operación para validar tu acceso.
              </div>
            </div>

            <?php if (!empty($isStatic)): ?>
              <div class="alert alert-secondary bg-opacity-10 border-secondary text-white-50 small mb-3 text-center" role="status">
                <i class="fas fa-info-circle me-1"></i> Vista previa de demostración. El registro de comprobantes y validación se procesan en el entorno oficial de PMO Solutions.
              </div>
            <?php endif; ?>

            <!-- Botones de Acción -->
            <div class="d-flex flex-column gap-3">
              <!-- Enlace Oficial Niubiz -->
              <a href="<?= View::e($niubizLink) ?>"
                 target="_blank"
                 rel="noopener noreferrer"
                 id="btnNiubizPay"
                 class="btn btn-warning text-dark fw-bold btn-lg w-100 py-3 shadow hvr-grow text-center">
                <i class="fas fa-lock me-2"></i> Pagar en Niubiz (<span id="btnPayAmount">US$ <?= $currentPrice['usd'] ?></span>)
              </a>

              <!-- Siguiente Paso: Registro de Datos -->
              <a href="/registro-pago?curso=<?= View::e($slug) ?>&clase=<?= $initialClassNum ?>"
                 id="btnGoRegister"
                 class="btn btn-outline-light btn-lg w-100 py-3 fw-bold text-center">
                <i class="fas fa-user-check me-2"></i> Ya realicé el pago — Completar mis datos
              </a>
            </div>

            <!-- Oferta de Curso Completo / Descuento Especial -->
            <div class="mt-5 pt-4 border-top border-white border-opacity-10 text-center">
              <p class="small text-white-50 mb-2">
                ¿Deseas llevar el curso completo? Obtén un descuento especial comunicándote con PMO.
              </p>
              <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="https://api.whatsapp.com/send?phone=51993463118&text=Hola%20PMO,%20deseo%20informaci%C3%B3n%20con%20descuento%20especial%20del%20curso%20completo%20de%20<?= urlencode($courseTitle) ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn btn-sm btn-outline-warning text-warning">
                  <i class="fab fa-whatsapp me-1"></i> Consultar por WhatsApp
                </a>
                <a href="/curso/<?= View::e($slug) ?>" class="btn btn-sm btn-outline-secondary text-white-50">
                  <i class="fas fa-arrow-left me-1"></i> Volver al curso
                </a>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const buttons = document.querySelectorAll('.class-select-btn');
  const classTitle = document.getElementById('selectedClassTitle');
  const priceSummary = document.getElementById('selectedPriceSummary');
  const displayUsd = document.getElementById('displayUsd');
  const displayPen = document.getElementById('displayPen');
  const instructionUsd = document.getElementById('instructionUsd');
  const btnPayAmount = document.getElementById('btnPayAmount');
  const btnGoRegister = document.getElementById('btnGoRegister');
  const courseSlug = <?= json_encode($slug) ?>;

  buttons.forEach(function(btn) {
    btn.addEventListener('click', function() {
      buttons.forEach(function(b) {
        b.classList.remove('btn-warning', 'text-dark', 'active-class');
        b.classList.add('btn-outline-light');
      });
      btn.classList.remove('btn-outline-light');
      btn.classList.add('btn-warning', 'text-dark', 'active-class');

      const classNum = btn.getAttribute('data-class-num');
      const pen = btn.getAttribute('data-pen');
      const usd = btn.getAttribute('data-usd');

      if (classTitle) classTitle.textContent = 'Clase ' + classNum;
      if (priceSummary) priceSummary.textContent = 'S/ ' + pen + ' — US$ ' + usd;
      if (displayUsd) displayUsd.textContent = 'US$ ' + usd;
      if (displayPen) displayPen.textContent = pen;
      if (instructionUsd) instructionUsd.textContent = 'US$ ' + usd;
      if (btnPayAmount) btnPayAmount.textContent = 'US$ ' + usd;
      if (btnGoRegister) {
        btnGoRegister.href = '/registro-pago?curso=' + encodeURIComponent(courseSlug) + '&clase=' + classNum;
      }
    });
  });
});
</script>
