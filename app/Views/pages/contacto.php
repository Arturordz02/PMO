<!-- 1. Encabezado Hero Estilizado (Corporate Helpdesk Style) -->
  <header class="contact-hero-section">
    <div class="hero-grid-overlay"></div>
    <div class="container position-relative">
      <div class="row align-items-center gy-5">
        <div class="col-12 col-lg-7">
          <div class="mb-2">
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2 hero-badge shadow-sm">
              <i class="fas fa-headset me-2"></i> COMUNICACIÓN DIRECTA & ASESORÍA TÉCNICA
            </span>
          </div>
          <h1 class="hero-title mb-1 animate__animated animate__fadeInDown">CONTÁCTANOS</h1>
          <h2 class="h5 text-warning fw-bold my-2">"Construimos Soluciones."</h2>
          <p class="hero-subtitle mb-4 animate__animated animate__fadeInUp animate__delay-1s">
            Ponte en contacto con nuestros consultores y asesores académicos. Estamos listos para atender tus consultas sobre programas in-house, capacitaciones y servicios de consultoría.
          </p>
          
          <div class="hero-actions d-flex flex-wrap gap-2 pt-2">
            <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20realizar%20una%20consulta" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-lg fw-bold shadow hvr-grow">
              <i class="fab fa-whatsapp me-2"></i> Chatear por WhatsApp
            </a>
            <a href="mailto:comercial@pmo-solutions.com" class="btn btn-warning btn-lg fw-bold shadow text-white hvr-grow">
              <i class="fas fa-envelope me-2"></i> Escribir Correo
            </a>
          </div>

          <!-- Feature badges -->
          <div class="d-flex flex-wrap gap-3 gap-md-4 mt-4 mt-md-5 pt-3 border-top border-white border-opacity-10 text-white-50">
            <div class="d-flex align-items-center gap-2">
              <i class="fas fa-clock text-warning fs-5"></i>
              <span class="text-white small fw-semibold">Respuesta Comercial Inmediata</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <i class="fas fa-user-tie text-info fs-5"></i>
              <span class="text-white small fw-semibold">Asesoría Técnica Personalizada</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <i class="fas fa-building text-success fs-5"></i>
              <span class="text-white small fw-semibold">Sede en Magdalena, Lima</span>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <!-- Grid de 3 Tarjetas de Respuesta Rápida en el Hero -->
          <div class="d-flex flex-column gap-3">
            
            <div class="risk-metric-box d-flex align-items-center gap-3 text-white shadow-sm">
              <div class="p-3 rounded-3 bg-success text-white fs-4">
                <i class="fab fa-whatsapp"></i>
              </div>
              <div>
                <span class="small text-white-50 d-block text-uppercase fw-bold">WhatsApp Comercial</span>
                <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20realizar%20una%20consulta" target="_blank" rel="noopener noreferrer" class="text-white fw-bold fs-5 text-decoration-none">
                  +51 944 276 649
                </a>
              </div>
            </div>

            <div class="risk-metric-box d-flex align-items-center gap-3 text-white shadow-sm">
              <div class="p-3 rounded-3 bg-warning text-dark fs-4">
                <i class="fas fa-envelope"></i>
              </div>
              <div>
                <span class="small text-white-50 d-block text-uppercase fw-bold">Correo Corporativo</span>
                <a href="mailto:comercial@pmo-solutions.com" class="text-white fw-bold text-decoration-none">
                  comercial@pmo-solutions.com
                </a>
              </div>
            </div>

            <div class="risk-metric-box d-flex align-items-center gap-3 text-white shadow-sm">
              <div class="p-3 rounded-3 bg-info text-white fs-4">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div>
                <span class="small text-white-50 d-block text-uppercase fw-bold">Sede Principal</span>
                <span class="text-white fw-bold">Av. Javier Prado 757, piso 10 Magdalena, Lima 17</span>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- 2. Layout Principal de 2 Columnas (Formulario Interactivo + Card Comercial) -->
  <section class="py-5 my-lg-4">
    <div class="container">
      <div class="row g-4 g-lg-5">
        
        <!-- Columna Izquierda: Formulario de Contacto Flotante -->
        <div class="col-12 col-lg-7">
          <div class="contact-floating-form p-4 p-md-5">
            <div class="mb-4">
              <span class="section-badge mb-2">Formulario Oficial</span>
              <h3 class="fw-bold text-primary mb-1">BRÍNDANOS TUS DATOS</h3>
              <p class="text-muted small mb-0">Los campos marcados con (<span class="text-danger">*</span>) son obligatorios.</p>
            </div>

            <div id="contactFormFeedback" class="mb-3" style="display:none;"></div>

            <form id="contactForm" action="backend/send-contact.php" method="POST" novalidate>
              <!-- Token de Seguridad CSRF -->
              <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateCsrfToken() ?>">

              <!-- Honeypot anti-spam (invisible para usuarios reales) -->
              <div class="d-none" aria-hidden="true">
                <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
              </div>

              <!-- Nombre Completo -->
              <div class="mb-3">
                <label class="form-label fw-bold small text-secondary" for="contact_nombre">Nombre Completo <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                  <input type="text" class="form-control border-start-0 ps-0" id="contact_nombre" name="nombre" placeholder="Ingresa tu nombre y apellido" required>
                </div>
              </div>

              <!-- Teléfono / WhatsApp -->
              <div class="mb-3">
                <label class="form-label fw-bold small text-secondary" for="contact_telefono">WhatsApp / Teléfono <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="fab fa-whatsapp text-success"></i></span>
                  <input type="tel" class="form-control border-start-0 ps-0" id="contact_telefono" name="telefono" placeholder="+51 999 999 999" required>
                </div>
              </div>

              <!-- Correo Electrónico -->
              <div class="mb-3">
                <label class="form-label fw-bold small text-secondary" for="contact_email">Correo Electrónico <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                  <input type="email" class="form-control border-start-0 ps-0" id="contact_email" name="email" placeholder="ejemplo@empresa.com" required>
                </div>
              </div>

              <!-- Asunto / Servicio de Interés -->
              <div class="mb-3">
                <label class="form-label fw-bold small text-secondary" for="contact_servicio">Servicio de Interés</label>
                <select class="form-select" id="contact_servicio" name="servicio">
                  <option value="Capacitación Profesional">Capacitación Profesional / Cursos Especializados</option>
                  <option value="Consultoría PMO Corporativa">Consultoría PMO Corporativa</option>
                  <option value="Asesoría Contractual / Peritaje Forense">Asesoría Contractual / Peritaje Forense</option>
                  <option value="Capacitación In-House">Capacitación In-House a Medida</option>
                  <option value="Otro">Otro Asunto</option>
                </select>
              </div>

              <!-- Comentario o Consulta -->
              <div class="mb-4">
                <label class="form-label fw-bold small text-secondary" for="contact_mensaje">Comentario o Consulta <span class="text-danger">*</span></label>
                <textarea class="form-control" id="contact_mensaje" name="mensaje" rows="4" placeholder="Escribe aquí los detalles de tu consulta sobre cursos, fechas, inscripciones o consultoría..." required></textarea>
              </div>

              <!-- Botón de Envío Destacado -->
              <button type="submit" id="contactSubmitBtn" class="btn btn-warning btn-lg w-100 py-3 fw-bold rounded-pill shadow text-white hvr-grow">
                <i class="fas fa-paper-plane me-2"></i> ENVIAR MENSAJE
              </button>
            </form>
          </div>
        </div>

        <!-- Columna Derecha: Canales Directos & Redes Oficiales -->
        <div class="col-12 col-lg-5">
          <div class="contact-info-card p-4 p-md-5 text-white h-100 d-flex flex-column justify-content-between hvr-box-shadow-outset">
            <div>
              <span class="badge bg-success text-white px-3 py-2 rounded-pill fw-bold mb-3">ATENCIÓN INMEDIATA</span>
              <h3 class="fw-bold text-white mb-3">¿Necesitas una respuesta al instante?</h3>
              <p class="text-white-50 mb-4" style="line-height: 1.65;">
                Escríbenos directamente a través de WhatsApp para coordinar matrículas, resolver dudas académicas o solicitar cotizaciones corporativas sin demoras.
              </p>

              <!-- Botón WhatsApp Oficial -->
              <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20realizar%20una%20consulta" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-lg w-100 py-3 my-2 fw-bold rounded-pill shadow d-flex align-items-center justify-content-center hvr-grow">
                <i class="fab fa-whatsapp me-2 fs-4"></i> Chatear por WhatsApp
              </a>

              <!-- Horario de Atención -->
              <div class="p-3 bg-white bg-opacity-10 rounded-4 my-4 border border-white border-opacity-10">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <i class="fas fa-calendar-alt text-warning"></i>
                  <span class="small fw-bold text-white">Horario de Atención Comercial:</span>
                </div>
                <p class="small text-white-50 mb-0 ps-4">
                  Lunes a Viernes: 8:30 am – 6:30 pm (PET)
                </p>
              </div>
            </div>

            <!-- Redes Sociales Oficiales -->
            <div class="pt-3 border-top border-white border-opacity-10">
              <span class="small text-white-50 d-block mb-3 fw-semibold">Canales y Redes Sociales Oficiales:</span>
              <div class="d-flex flex-wrap gap-2">
                <a href="https://www.facebook.com/people/PMO-Solutions/100070284155015/#" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-sm rounded-pill px-3 hvr-grow">
                  <i class="fab fa-facebook me-1 text-primary"></i> Facebook
                </a>
                <a href="https://www.youtube.com/@pmosolutions/videos" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-sm rounded-pill px-3 hvr-grow">
                  <i class="fab fa-youtube me-1 text-danger"></i> YouTube
                </a>
                <a href="mailto:comercial@pmo-solutions.com" class="btn btn-outline-light btn-sm rounded-pill px-3 hvr-grow">
                  <i class="fas fa-envelope me-1 text-warning"></i> Correo
                </a>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- 3. Mapa Geolocalizado de Ubicación Central (Google Maps Embed) -->
  <section class="py-5">
    <div class="container">
      <div class="text-center mb-4">
        <span class="section-badge">Ubicación Estratégica</span>
        <h2 class="section-title">NUESTRA UBICACIÓN CORPORATIVA</h2>
        <p class="section-subtitle">
          Av. Javier Prado 757, piso 10 Magdalena, Lima 17, Perú.
        </p>
      </div>

      <div class="ratio ratio-21x9 shadow-lg rounded-4 overflow-hidden my-4 border">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3901.365448381531!2d-77.0698142!3d-12.0905096!2m3!1f0!1f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9105c8e22c365b21%3A0x600b651030e46b0!2sAv.%20Javier%20Prado%20Oeste%20757%2C%20Magdalena%20del%20Mar%2015076!5e0!3m2!1ses!2spe!4v1700000000000!5m2!1ses!2spe" title="Ubicación de PMO Solutions en Magdalena, Lima" loading="lazy" allowfullscreen></iframe>
      </div>
    </div>
  </section>

  <!-- 5. Footer Corporativo Oficial (Con Logo Limpio en Colores Reales) -->