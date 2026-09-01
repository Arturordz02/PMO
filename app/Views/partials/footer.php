<?php
/**
 * PMO SOLUTIONS - Componente Parcial: Footer & Multi-Country WhatsApp Selector
 */
?>
<!-- Footer Principal -->
<footer class="footer-custom pt-5 pb-4">
  <div class="container">
    <div class="row g-4 justify-content-between mb-5">
      
      <!-- Columna 1: Identidad & Propósito -->
      <div class="col-12 col-lg-4">
        <div class="footer-brand mb-3">
          <a href="./" class="d-inline-flex align-items-center text-decoration-none">
            <img src="img/LogoPMO.png" alt="PMO Solutions" height="42" class="me-2" style="mix-blend-mode: multiply;">
            <div class="brand-text-wrapper text-white">
              <span class="brand-title text-white">PMO <span class="text-warning">SOLUTIONS</span></span>
              <span class="brand-subtitle text-white-50">Construimos Soluciones</span>
            </div>
          </a>
        </div>
        <p class="text-white-50 small mb-4" style="line-height: 1.7;">
          Consultora y centro de alta formación ejecutiva especializado en dirección de proyectos de construcción, administración contractual NEC4/FIDIC, Dispute Boards (DAB-JRD) y peritajes forenses.
        </p>

        <!-- Selector de WhatsApp Multi-País -->
        <div class="footer-whatsapp-selector p-3 rounded-3 mb-3">
          <label for="footerCountrySelect" class="form-label text-warning small fw-bold mb-2">
            <i class="fab fa-whatsapp me-1"></i> Línea Comercial WhatsApp por País:
          </label>
          <div class="input-group input-group-sm mb-2">
            <select class="form-select bg-dark text-white border-secondary" id="footerCountrySelect" aria-label="Seleccionar País">
              <option value="pe" selected>🇵🇪 Perú (+51 944 276 649)</option>
              <option value="cl">🇨🇱 Chile (+56 9 8765 4321)</option>
              <option value="ec">🇪🇨 Ecuador (+593 9 8765 4321)</option>
              <option value="pa">🇵🇦 Panamá (+507 6123 4567)</option>
              <option value="mx">🇲🇽 México (+52 55 1234 5678)</option>
            </select>
          </div>
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-white-50 small" id="footerWaDisplay">🇵🇪 +51 944 276 649</span>
            <a href="https://wa.me/51944276649" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-sm fw-bold px-3 rounded-pill" id="footerWaBtn">
              Chatear <i class="fas fa-arrow-right ms-1"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Columna 2: Programas de Formación -->
      <div class="col-6 col-lg-3">
        <h5 class="text-white fw-bold mb-3 footer-heading">Especializaciones</h5>
        <ul class="list-unstyled footer-links">
          <li><a href="dab-jrd"><i class="fas fa-chevron-right me-1 text-warning"></i> Dispute Boards & DAB-JRD</a></li>
          <li><a href="analisis-forense"><i class="fas fa-chevron-right me-1 text-warning"></i> Análisis Forense de Atrasos</a></li>
          <li><a href="nec4"><i class="fas fa-chevron-right me-1 text-warning"></i> Contratos NEC4 (ECC/PSC)</a></li>
          <li><a href="vdc-bim"><i class="fas fa-chevron-right me-1 text-warning"></i> VDC - BIM Management</a></li>
          <li><a href="contratos-estado"><i class="fas fa-chevron-right me-1 text-warning"></i> Ley 30225 & Contrataciones</a></li>
          <li><a href="primavera-p6"><i class="fas fa-chevron-right me-1 text-warning"></i> Oracle Primavera P6</a></li>
          <li><a href="capacitaciones" class="text-warning fw-bold"><i class="fas fa-th-large me-1"></i> Ver Catálogo Completo</a></li>
        </ul>
      </div>

      <!-- Columna 3: Servicios & Legal -->
      <div class="col-6 col-lg-2">
        <h5 class="text-white fw-bold mb-3 footer-heading">Institucional</h5>
        <ul class="list-unstyled footer-links">
          <li><a href="contacto"><i class="fas fa-chevron-right me-1 text-warning"></i> Contacto y Sedes</a></li>
          <li><a href="compliance"><i class="fas fa-chevron-right me-1 text-warning"></i> Compliance Técnico</a></li>
          <li><a href="eventos-compensables"><i class="fas fa-chevron-right me-1 text-warning"></i> Claims Contractuales</a></li>
          <li><a href="riesgos-pmi"><i class="fas fa-chevron-right me-1 text-warning"></i> Gestión de Riesgos PMI</a></li>
          <li>
            <a href="libro-de-reclamaciones" class="d-inline-flex align-items-center text-warning mt-2 fw-semibold">
              <i class="fas fa-book-open me-2 fs-5"></i> Libro de Reclamaciones
            </a>
          </li>
        </ul>
      </div>

      <!-- Columna 4: Contacto Central -->
      <div class="col-12 col-lg-3">
        <h5 class="text-white fw-bold mb-3 footer-heading">Contacto Central</h5>
        <ul class="list-unstyled text-white-50 small mb-4" style="line-height: 1.9;">
          <li><i class="fas fa-map-marker-alt text-warning me-2"></i> Av. Javier Prado 757, piso 10, Magdalena, Lima 17, Perú</li>
          <li><i class="fas fa-envelope text-warning me-2"></i> <a href="mailto:comercial@pmo-solutions.com" class="text-white-50 text-decoration-none">comercial@pmo-solutions.com</a></li>
          <li><i class="fas fa-phone-alt text-warning me-2"></i> +51 944 276 649</li>
          <li><i class="fas fa-id-card text-warning me-2"></i> RUC: 20600000000</li>
        </ul>

        <!-- Redes Sociales -->
        <div class="footer-social-links d-flex gap-2">
          <a href="https://www.facebook.com/people/PMO-Solutions/100070284155015/#" target="_blank" rel="noopener noreferrer" class="btn-social" title="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="https://www.youtube.com/@pmosolutions/videos" target="_blank" rel="noopener noreferrer" class="btn-social" title="YouTube"><i class="fab fa-youtube"></i></a>
          <a href="https://api.whatsapp.com/send?phone=51944276649" target="_blank" rel="noopener noreferrer" class="btn-social" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>

    </div>

    <!-- Barra Inferior de Copyright & Métodos de Pago -->
    <div class="row pt-4 border-top border-white border-opacity-10 align-items-center text-center text-md-start">
      <div class="col-12 col-md-6 mb-3 mb-md-0">
        <small class="text-white-50">
          &copy; <?= date('Y') ?> <strong>PMO Solutions S.A.C.</strong> Todos los derechos reservados.
        </small>
      </div>
      <div class="col-12 col-md-6 text-md-end">
        <span class="text-white-50 small me-2">Pagos Seguros:</span>
        <img src="img/logo-visa.png" alt="Visa" height="20" class="me-1 bg-white p-1 rounded">
        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" height="20" class="me-1 bg-white p-1 rounded">
        <img src="img/IconoYape.png" alt="Yape" height="20" class="me-1 bg-white p-1 rounded">
        <img src="img/IconoPlin.png" alt="Plin" height="20" class="bg-white p-1 rounded">
      </div>
    </div>

  </div>
</footer>

