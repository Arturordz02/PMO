<?php
/**
 * PMO SOLUTIONS - Componente Parcial: Modal de Pago Niubiz y Transferencia B2B
 */
?>
<!-- Modal de Checkout / Pasarela Niubiz & Pagos -->
<div class="modal fade" id="niubizCheckoutModal" tabindex="-1" aria-labelledby="niubizCheckoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      
      <div class="modal-header bg-dark text-white border-0 py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <img src="img/LogoPMO.png" alt="PMO Solutions" height="32">
          <h5 class="modal-title fw-bold mb-0" id="niubizCheckoutModalLabel">Centro de Pago Seguro PMO Solutions</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4 bg-light">
        <!-- Pestañas de Métodos de Pago -->
        <ul class="nav nav-pills nav-justified mb-4 gap-2 payment-method-nav" id="pills-tab-payment" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-niubiz-tab" data-bs-toggle="pill" data-bs-target="#pills-niubiz" type="button" role="tab" aria-controls="pills-niubiz" aria-selected="true">
              <i class="fas fa-credit-card me-2"></i> Tarjetas & Yape (Niubiz)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-transfer-tab" data-bs-toggle="pill" data-bs-target="#pills-transfer" type="button" role="tab" aria-controls="pills-transfer" aria-selected="false">
              <i class="fas fa-university me-2"></i> Transferencia & Factura B2B
            </button>
          </li>
        </ul>

        <div class="tab-content" id="pills-tabContent-payment">
          
          <!-- 1. Opción Niubiz Pago Web / Yape -->
          <div class="tab-pane fade show active" id="pills-niubiz" role="tabpanel" aria-labelledby="pills-niubiz-tab">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-white mb-3">
              <div class="text-center mb-3">
                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-bold mb-2">
                  <i class="fas fa-shield-alt me-1"></i> Transacción Encriptada PCI-DSS
                </span>
                <h5 class="fw-bold text-primary mb-1">Pago en Línea con Tarjetas y Billeteras Digitales</h5>
                <p class="text-muted small mb-3">Procesado de forma 100% segura por <strong>Niubiz</strong></p>
                
                <!-- Marcas Aceptadas Niubiz -->
                <div class="payment-brand-logos d-flex flex-wrap justify-content-center align-items-center gap-2 mb-3">
                  <img src="img/logo-visa.png" alt="Visa" height="28">
                  <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" height="28">
                  <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/American_Express_logo_%282018%29.svg" alt="Amex" height="28">
                  <img src="img/diners-logo.png" alt="Diners Club" height="28">
                  <img src="img/IconoYape.png" alt="Yape" height="28">
                  <img src="img/IconoPlin.png" alt="Plin" height="28">
                </div>
              </div>

              <div class="bg-light p-3 rounded-3 mb-4 border">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="text-secondary small fw-semibold">Beneficios de Pago Niubiz:</span>
                  <span class="badge bg-primary">Acreditación Inmediata</span>
                </div>
                <ul class="small text-secondary mb-0 ps-3" style="line-height: 1.6;">
                  <li>Acceso instantáneo a la plataforma y aula virtual tras confirmar la transacción.</li>
                  <li>Acepta tarjetas de crédito y débito nacionales e internacionales.</li>
                  <li>Emisión automática de comprobante electrónico (Boleta o Factura).</li>
                </ul>
              </div>

              <!-- Botón Principal Niubiz -->
              <button type="button" class="btn btn-warning btn-lg w-100 py-3 fw-bold rounded-pill shadow hvr-grow" id="btn-pagar-niubiz">
                <i class="fas fa-lock me-2"></i> PAGAR EN LÍNEA CON NIUBIZ / YAPE
              </button>
            </div>
          </div>

          <!-- 2. Opción Transferencia Bancaria & Factura -->
          <div class="tab-pane fade" id="pills-transfer" role="tabpanel" aria-labelledby="pills-transfer-tab">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-white mb-3 text-center">
              <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
                <i class="fas fa-university fs-3"></i>
              </div>
              <h5 class="fw-bold text-primary mb-3">Transferencia Bancaria & Facturación Corporativa</h5>
              <div class="alert alert-light border border-primary-subtle text-secondary small p-3 mb-4 mx-auto rounded-3" style="max-width: 600px; line-height: 1.7;">
                <i class="fas fa-info-circle text-primary me-1 fs-6"></i>
                Para pagos por transferencia bancaria directa (BCP, BBVA, Interbank) o emisión de Factura Electrónica B2B, solicita los datos de cuenta y RUC escribiéndonos a nuestro WhatsApp Comercial <strong>+51 944 276 649</strong>.
              </div>
              <div class="d-flex justify-content-center">
                <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20los%20datos%20de%20cuenta%20bancaria%20y%20RUC%20para%20transferencia%20/%20factura%20B2B" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-lg w-100 py-3 fw-bold rounded-pill shadow my-2 hvr-grow" style="max-width: 500px;">
                  <i class="fab fa-whatsapp me-2 fs-5 align-middle"></i> SOLICITAR DATOS POR WHATSAPP
                </a>
              </div>
            </div>
          </div>

        </div>

        <!-- Insignias de Seguridad Niubiz & CyberSource -->
        <div class="d-flex flex-wrap justify-content-center gap-2 pt-3 border-top">
          <span class="badge bg-light text-dark border p-2 me-1"><i class="fas fa-shield-alt text-success me-1"></i> Certificado PCI DSS & CyberSource</span>
          <span class="badge bg-light text-dark border p-2 me-1"><i class="fas fa-file-invoice text-primary me-1"></i> Boleta o Factura Electrónica</span>
          <span class="badge bg-light text-dark border p-2"><i class="fas fa-lock text-warning me-1"></i> Encriptación SSL 256-bit</span>
        </div>

      </div>
    </div>
  </div>
</div>

