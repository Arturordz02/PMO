<?php
/**
 * PMO SOLUTIONS - Componente Parcial: Footer Corporativo
 */
?>
<!-- Footer Principal -->
<footer class="footer-corporate">
  <div class="container">
    <div class="row g-4 g-lg-5">
      
      <!-- Columna 1: Identidad PMO Solutions -->
      <div class="col-12 col-md-6 col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="footer-brand-circle me-2">
            <img src="/img/LogoPMO.png" alt="Logo PMO Solutions" width="44" height="44" class="d-block" style="object-fit: contain;">
          </div>
          <div>
            <span class="fs-4 fw-bold text-white">PMO <span class="text-warning">SOLUTIONS</span></span>
            <span class="d-block small text-warning fw-bold" style="letter-spacing: 0.8px;">"Construimos Soluciones."</span>
          </div>
        </div>
        <p class="text-secondary small mb-4">
          Tu socio para crear soluciones en gestión de proyectos de construcción. Consultoría estratégica, peritaje técnico y capacitación ejecutiva.
        </p>
        <div class="d-flex gap-3">
          <a href="https://www.facebook.com/people/PMO-Solutions/100070284155015/#" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary rounded-circle text-white d-inline-flex align-items-center justify-content-center" style="width:36px; height:36px;" title="Facebook PMO Solutions">
            <i class="fab fa-facebook-f"></i>
          </a>
          <a href="https://www.youtube.com/@pmosolutions/videos" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary rounded-circle text-white d-inline-flex align-items-center justify-content-center" style="width:36px; height:36px;" title="YouTube PMO Solutions">
            <i class="fab fa-youtube"></i>
          </a>
          <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary rounded-circle text-white d-inline-flex align-items-center justify-content-center" style="width:36px; height:36px;" title="WhatsApp PMO Solutions">
            <i class="fab fa-whatsapp"></i>
          </a>
        </div>
      </div>

      <!-- Columna 2: Navegación & Servicios -->
      <div class="col-12 col-md-6 col-lg-4">
        <h5>Navegación & Servicios</h5>
        <ul class="footer-links mb-4">
          <li><a href="/"><i class="fas fa-chevron-right"></i> Inicio (Home)</a></li>
          <li><a href="/capacitaciones"><i class="fas fa-chevron-right"></i> Catálogo de Capacitaciones</a></li>
          <li><a href="/dab-jrd"><i class="fas fa-chevron-right"></i> Dispute Boards (DAB NEC)</a></li>
          <li><a href="/analisis-forense"><i class="fas fa-chevron-right"></i> Análisis Forense de Atrasos</a></li>
          <li><a href="/nec4"><i class="fas fa-chevron-right"></i> Contratos NEC4</a></li>
          <li><a href="/contacto"><i class="fas fa-chevron-right"></i> Contacto y Asesoría</a></li>
        </ul>
        <div class="mt-2">
          <a href="/libro-de-reclamaciones" class="text-white-50 text-decoration-none small d-inline-flex align-items-center opacity-75 opacity-100-hover" style="font-size: 0.85rem;">
            <i class="fas fa-book-open me-1 text-warning"></i> Libro de Reclamaciones
          </a>
        </div>
      </div>

      <!-- Columna 3: Contacto Directo Confirmado -->
      <div class="col-12 col-md-12 col-lg-4">
        <h5>Contacto Directo</h5>
        <p class="small text-secondary mb-3">
          <i class="fas fa-map-marker-alt text-warning me-2 fs-6"></i>
          <strong>Av. Javier Prado 757, piso 10 Magdalena, Lima 17</strong>, Perú.
        </p>
        <p class="small text-secondary mb-3">
          <i class="fas fa-envelope text-warning me-2 fs-6"></i>
          <a href="mailto:comercial@pmo-solutions.com" class="text-white fw-semibold text-decoration-none">comercial@pmo-solutions.com</a>
        </p>
        <p class="small text-secondary mb-4">
          <i class="fab fa-whatsapp text-success me-2 fs-5"></i>
          <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n" target="_blank" rel="noopener noreferrer" class="text-white fw-bold text-decoration-none">
            +51 944 276 649
          </a>
        </p>
        <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-white border-opacity-10">
          <span class="small text-white-50 d-block mb-1">Horario de Atención Comercial:</span>
          <span class="small text-white fw-semibold">Lunes a Viernes: 8:30 AM - 6:30 PM (PET)</span>
        </div>
      </div>

    </div>
  </div>

  <!-- Copyright Subfooter -->
  <div class="footer-bottom">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
      <div class="text-white-50 small text-center text-md-start">
        &copy; <?= date('Y') ?> <strong>PMO Solutions</strong> Todos los derechos reservados.
      </div>
      <div class="d-flex flex-wrap justify-content-center gap-3 gap-md-4 small">
        <a href="/politica-de-privacidad" class="text-white-50 text-decoration-none">Políticas de Privacidad</a>
        <a href="/terminos-y-condiciones" class="text-white-50 text-decoration-none">Términos de Servicio</a>
        <a href="/libro-de-reclamaciones" class="text-white-50 text-decoration-none">Libro de Reclamaciones Virtual</a>
      </div>
    </div>
  </div>
</footer>

<!-- Botón Flotante de WhatsApp -->
<div class="floating-wa-container">
  <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n" target="_blank" rel="noopener noreferrer" class="floating-wa-btn shadow-lg" title="Consultar por WhatsApp">
    <i class="fab fa-whatsapp"></i>
    <span class="floating-wa-tooltip">¿Consultas? Escríbenos</span>
  </a>
</div>

<!-- Botón Volver Arriba -->
<button type="button" id="backToTop" class="back-to-top-btn" title="Volver arriba" aria-label="Volver arriba">
  <i class="fas fa-chevron-up"></i>
</button>