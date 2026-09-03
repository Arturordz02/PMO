<?php
/**
 * PMO SOLUTIONS - Componente Parcial: Tarjeta Flotante de Términos y Condiciones
 */
?>
<!-- Tarjeta Flotante Discreta de Términos y Condiciones -->
<aside id="pmoTermsBanner" class="pmo-terms-banner d-none" role="region" aria-label="Aviso de Términos y Condiciones">
  
  <!-- Vista 1: Aviso Principal -->
  <div id="pmoTermsMainView" class="pmo-terms-banner-content">
    <div class="d-flex align-items-center gap-2 mb-2">
      <span class="pmo-terms-badge"><i class="fas fa-shield-alt text-warning"></i></span>
      <span class="fw-bold text-dark small" style="letter-spacing: 0.3px;">Términos y Condiciones</span>
    </div>
    <p class="pmo-terms-banner-text mb-2">
      Antes de continuar, revisa nuestros Términos y Condiciones.
    </p>
    <div class="mb-3">
      <a href="terminos-y-condiciones" target="_blank" rel="noopener noreferrer" class="pmo-terms-banner-link">
        Ver Términos y Condiciones <i class="fas fa-external-link-alt ms-1 small"></i>
      </a>
    </div>
    <div class="d-flex align-items-center justify-content-end gap-2 pt-1">
      <button type="button" id="pmoTermsDeclineBtn" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2 rounded-pill">
        Rechazar
      </button>
      <button type="button" id="pmoTermsAcceptBtn" class="btn btn-warning btn-sm fw-bold text-white px-4 py-2 rounded-pill shadow-sm">
        Aceptar
      </button>
    </div>
  </div>

  <!-- Vista 2: Confirmación de Rechazo -->
  <div id="pmoTermsRejectConfirm" class="pmo-terms-banner-content d-none">
    <div class="d-flex align-items-center gap-2 mb-2 text-danger">
      <span class="pmo-terms-badge bg-danger bg-opacity-10"><i class="fas fa-exclamation-triangle text-danger"></i></span>
      <span class="fw-bold small" style="letter-spacing: 0.3px;">Confirmar Rechazo</span>
    </div>
    <p class="pmo-terms-banner-text mb-3 text-secondary">
      Si rechazas los Términos y Condiciones no podrás continuar utilizando este sitio.
    </p>
    <div class="d-flex align-items-center justify-content-end gap-2 pt-1">
      <button type="button" id="pmoTermsBackBtn" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2 rounded-pill">
        Volver
      </button>
      <button type="button" id="pmoTermsConfirmRejectBtn" class="btn btn-danger btn-sm fw-bold text-white px-3 py-2 rounded-pill shadow-sm">
        Confirmar rechazo
      </button>
    </div>
  </div>

</aside>
