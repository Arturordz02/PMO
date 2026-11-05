<?php
/**
 * PMO SOLUTIONS - Vista de Página: Capacitaciones In-House para Empresas
 * Archivo: private_app/app/Views/pages/in-house.php
 */
use App\Core\View;
use App\Core\Csrf;
?>

<!-- 1. Encabezado Hero In-House PMO -->
<header class="inhouse-hero position-relative">
  <div class="hero-grid-overlay" aria-hidden="true"></div>
  <div class="container position-relative py-3 py-lg-4">
    <div class="row align-items-center gy-4">
      <div class="col-12 col-lg-8">
        <div class="mb-3">
          <span class="badge inhouse-hero-badge px-3 py-2 rounded-pill shadow-sm">
            <i class="fas fa-building me-2 text-warning"></i> Formación Corporativa para Empresas
          </span>
        </div>
        <h1 class="inhouse-hero-title mb-3 animate__animated animate__fadeInDown">
          CAPACITACIONES IN-HOUSE
        </h1>
        <p class="inhouse-hero-subtitle mb-3 animate__animated animate__fadeInUp">
          Formación especializada a la medida de tu empresa
        </p>
        <p class="inhouse-hero-desc mb-4 animate__animated animate__fadeInUp animate__delay-1s">
          Desarrollamos programas de capacitación técnica corporativa adaptados a las exigencias normativas, contractuales y operativas de tus proyectos. Potenciamos las competencias de tus equipos en campo y oficina técnica con metodologías aplicadas, estándares internacionales y enfoque en prevención de controversias.
        </p>
        <div class="d-flex flex-wrap gap-3 pt-2">
          <a href="#solicitar-propuesta" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
            <i class="fas fa-clipboard-check me-2"></i> Solicitar Propuesta
          </a>
          <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20una%20propuesta%20de%20Capacitaci%C3%B3n%20In-House" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light fw-bold px-4 py-3 shadow-sm hvr-grow">
            <i class="fab fa-whatsapp me-2 text-success"></i> Contactar por WhatsApp
          </a>
        </div>
      </div>

      <div class="col-12 col-lg-4 text-center d-none d-lg-block">
        <div class="p-4 rounded-4 bg-white bg-opacity-10 backdrop-blur border border-white border-opacity-15 shadow-lg">
          <div class="inhouse-pillar-icon mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
            <i class="fas fa-hard-hat text-warning"></i>
          </div>
          <h4 class="text-white fw-bold mb-2">Ingeniería en Acción</h4>
          <p class="text-white-50 small mb-0">Contenidos directamente aplicables a la realidad técnica y contractual de tus obras y proyectos de infraestructura.</p>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- 2. Cuatro Ventajas Clave del Enfoque In-House -->
<section class="py-5 bg-light" id="ventajas-inhouse">
  <div class="container py-lg-4">
    <div class="text-center mx-auto mb-5" style="max-width: 740px;">
      <span class="text-primary fw-bold text-uppercase small tracking-wide">
        <i class="fas fa-check-double me-1"></i> Propuesta de Valor Corporativa
      </span>
      <h2 class="h1 fw-bold text-dark mt-2 mb-3">FORTALECEMOS LAS COMPETENCIAS DE TUS EQUIPOS</h2>
      <div class="heading-line-sm mx-auto mb-3"></div>
      <p class="text-secondary">
        Cada organización y obra enfrenta condiciones contractuales y desafíos particulares. Adaptamos el contenido para impactar directamente en el desempeño operativo de tus profesionales.
      </p>
    </div>

    <div class="row g-4">
      <!-- Ventaja 1 -->
      <div class="col-12 col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
        <div class="inhouse-pillar-card">
          <div class="inhouse-pillar-icon">
            <i class="fas fa-sliders-h"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Diagnóstico y Personalización</h5>
          <p class="text-secondary small mb-0">
            Temarios estructurados a partir de los objetivos de la empresa, el perfil técnico del grupo y los requerimientos del proyecto.
          </p>
        </div>
      </div>

      <!-- Ventaja 2 -->
      <div class="col-12 col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
        <div class="inhouse-pillar-card">
          <div class="inhouse-pillar-icon">
            <i class="fas fa-briefcase"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Casos Reales Aplicados</h5>
          <p class="text-secondary small mb-0">
            Metodología fundamentada en casuística real de obra, administración de reclamos, análisis de riesgos y situaciones contractuales exigentes.
          </p>
        </div>
      </div>

      <!-- Ventaja 3 -->
      <div class="col-12 col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
        <div class="inhouse-pillar-card">
          <div class="inhouse-pillar-icon">
            <i class="fas fa-calendar-alt"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Flexibilidad y Modalidad</h5>
          <p class="text-secondary small mb-0">
            Sesiones virtuales en vivo, presenciales en tus oficinas o instalaciones de obra, en horarios compatibles con el ritmo laboral de tu equipo.
          </p>
        </div>
      </div>

      <!-- Ventaja 4 -->
      <div class="col-12 col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
        <div class="inhouse-pillar-card">
          <div class="inhouse-pillar-icon">
            <i class="fas fa-shield-alt"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Confidencialidad Corporativa</h5>
          <p class="text-secondary small mb-0">
            Estricto compromiso de reserva y protección sobre la información técnica, operativa y contractual tratada durante las capacitaciones.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 3. Áreas de Formación In-House Especializadas -->
<section class="py-5 bg-white" id="areas-formacion">
  <div class="container py-lg-4">
    <div class="text-center mx-auto mb-5" style="max-width: 760px;">
      <span class="text-primary fw-bold text-uppercase small tracking-wide">
        <i class="fas fa-layer-group me-1"></i> Especialidades Técnicas PMO
      </span>
      <h2 class="h1 fw-bold text-dark mt-2 mb-3">ÁREAS DE FORMACIÓN IN-HOUSE</h2>
      <div class="heading-line-sm mx-auto mb-3"></div>
      <p class="text-secondary">
        Programas modulares diseñados en las disciplinas neurálgicas de la ingeniería moderna, la infraestructura y la administración contractual de proyectos.
      </p>
    </div>

    <div class="row g-4">
      <!-- Área 1 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
        <div class="inhouse-area-card">
          <div class="inhouse-area-header">
            <div class="inhouse-area-icon">
              <i class="fas fa-file-contract"></i>
            </div>
            <div>
              <span class="inhouse-area-badge">Área 01</span>
              <h5 class="fw-bold text-dark mb-0 mt-1">Contratos NEC4 y FIDIC</h5>
            </div>
          </div>
          <p class="text-secondary small mb-3 flex-grow-1">
            Administración contractual proactiva bajo contratos estándar colaborativos. Alertas tempranas, gestión de eventos compensables, cláusulas de opciones secundarias y mitigación de disputas.
          </p>
          <div class="pt-3 border-top border-light-subtle small text-primary fw-semibold">
            <i class="fas fa-check-circle me-1 text-success"></i> Modelos ECC, PSC y prevención legal
          </div>
        </div>
      </div>

      <!-- Área 2 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
        <div class="inhouse-area-card">
          <div class="inhouse-area-header">
            <div class="inhouse-area-icon">
              <i class="fas fa-gavel"></i>
            </div>
            <div>
              <span class="inhouse-area-badge">Área 02</span>
              <h5 class="fw-bold text-dark mb-0 mt-1">Dispute Boards y DAB</h5>
            </div>
          </div>
          <p class="text-secondary small mb-3 flex-grow-1">
            Gestión y operatividad ante Juntas de Prevención y Resolución de Disputas (JRD / DAB). Preparación de posiciones técnicas, actas de discrepancia y sustento ante comités consultivos.
          </p>
          <div class="pt-3 border-top border-light-subtle small text-primary fw-semibold">
            <i class="fas fa-check-circle me-1 text-success"></i> Resolución temprana de discrepancias
          </div>
        </div>
      </div>

      <!-- Área 3 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
        <div class="inhouse-area-card">
          <div class="inhouse-area-header">
            <div class="inhouse-area-icon">
              <i class="fas fa-search-plus"></i>
            </div>
            <div>
              <span class="inhouse-area-badge">Área 03</span>
              <h5 class="fw-bold text-dark mb-0 mt-1">Análisis Forense de Atrasos</h5>
            </div>
          </div>
          <p class="text-secondary small mb-3 flex-grow-1">
            Aplicación práctica de metodologías forenses (AACE® 29R-03 y Protocolo SCL): As-Planned vs As-Built, Windows Analysis y Time Impact Analysis para sustentar o refutar reclamos de plazo.
          </p>
          <div class="pt-3 border-top border-light-subtle small text-primary fw-semibold">
            <i class="fas fa-check-circle me-1 text-success"></i> Determinación pericial de ruta crítica
          </div>
        </div>
      </div>

      <!-- Área 4 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400">
        <div class="inhouse-area-card">
          <div class="inhouse-area-header">
            <div class="inhouse-area-icon">
              <i class="fas fa-cubes"></i>
            </div>
            <div>
              <span class="inhouse-area-badge">Área 04</span>
              <h5 class="fw-bold text-dark mb-0 mt-1">VDC y BIM Management</h5>
            </div>
          </div>
          <p class="text-secondary small mb-3 flex-grow-1">
            Virtual Design and Construction y modelado BIM 3D/4D/5D. Compatibilización multidisciplinaria en ingeniería, detección temprana de interferencias y vinculación con la planificación de obra.
          </p>
          <div class="pt-3 border-top border-light-subtle small text-primary fw-semibold">
            <i class="fas fa-check-circle me-1 text-success"></i> Coordinación digital y constructabilidad
          </div>
        </div>
      </div>

      <!-- Área 5 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="500">
        <div class="inhouse-area-card">
          <div class="inhouse-area-header">
            <div class="inhouse-area-icon">
              <i class="fas fa-shield-alt"></i>
            </div>
            <div>
              <span class="inhouse-area-badge">Área 05</span>
              <h5 class="fw-bold text-dark mb-0 mt-1">Gestión Integral de Riesgos</h5>
            </div>
          </div>
          <p class="text-secondary small mb-3 flex-grow-1">
            Identificación sistemática, análisis cualitativo y cuantitativo (simulación Monte Carlo) bajo estándares PMI® e ISO 31000. Prevención del síndrome del 90% y curvas de probabilidad de cumplimiento.
          </p>
          <div class="pt-3 border-top border-light-subtle small text-primary fw-semibold">
            <i class="fas fa-check-circle me-1 text-success"></i> Control de incertidumbre en costo y plazo
          </div>
        </div>
      </div>

      <!-- Área 6 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="600">
        <div class="inhouse-area-card">
          <div class="inhouse-area-header">
            <div class="inhouse-area-icon">
              <i class="fas fa-sitemap"></i>
            </div>
            <div>
              <span class="inhouse-area-badge">Área 06</span>
              <h5 class="fw-bold text-dark mb-0 mt-1">PMO, Planificación y Control</h5>
            </div>
          </div>
          <p class="text-secondary small mb-3 flex-grow-1">
            Estructuración y consolidación de Oficinas de Gestión de Proyectos (PMO). Control avanzado con Primavera P6, Gestión de Valor Ganado (EVM) y tableros gerenciales de control de obras.
          </p>
          <div class="pt-3 border-top border-light-subtle small text-primary fw-semibold">
            <i class="fas fa-check-circle me-1 text-success"></i> Gobernanza, productividad y plazos
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 4. Metodología de Trabajo en Tres Pasos -->
<section class="py-5 bg-light" id="metodologia-atencion">
  <div class="container py-lg-4">
    <div class="text-center mx-auto mb-5" style="max-width: 720px;">
      <span class="text-primary fw-bold text-uppercase small tracking-wide">
        <i class="fas fa-route me-1"></i> Proceso Transparente
      </span>
      <h2 class="h1 fw-bold text-dark mt-2 mb-3">¿CÓMO COORDINAMOS TU CAPACITACIÓN?</h2>
      <div class="heading-line-sm mx-auto mb-3"></div>
      <p class="text-secondary">
        Un flujo ágil y estructurado desde el primer contacto hasta el despliegue del programa formativo en tu organización.
      </p>
    </div>

    <div class="row g-4">
      <!-- Paso 1 -->
      <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="inhouse-step-card text-center">
          <div class="inhouse-step-number">01</div>
          <h5 class="fw-bold text-dark mb-2">Comunicar la Necesidad</h5>
          <p class="text-secondary small mb-0">
            Tu organización nos comparte las áreas de interés, los objetivos técnicos buscados, los proyectos de referencia y la cantidad estimada de participantes.
          </p>
        </div>
      </div>

      <!-- Paso 2 -->
      <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="inhouse-step-card text-center">
          <div class="inhouse-step-number">02</div>
          <h5 class="fw-bold text-dark mb-2">Definir Alcance y Participantes</h5>
          <p class="text-secondary small mb-0">
            Nuestro equipo técnico analiza el perfil del grupo, estructura los temas clave, los casos de aplicación práctica, las fechas tentativas y la modalidad idónea.
          </p>
        </div>
      </div>

      <!-- Paso 3 -->
      <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="300">
        <div class="inhouse-step-card text-center">
          <div class="inhouse-step-number">03</div>
          <h5 class="fw-bold text-dark mb-2">Recibir Propuesta Integral</h5>
          <p class="text-secondary small mb-0">
            Entregamos una propuesta técnica y económica formal, detallando objetivos de aprendizaje, cronograma, entregables académicos y cotización transparente.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 5. Formulario de Solicitud de Propuesta In-House -->
<section class="py-5 bg-white position-relative" id="solicitar-propuesta">
  <div class="container py-lg-4">
    <div class="row g-4 g-lg-5 align-items-stretch">

      <!-- Columna Izquierda: Información de Contacto y Canales Oficiales -->
      <div class="col-12 col-lg-5" data-aos="fade-right">
        <div class="inhouse-info-card d-flex flex-column justify-content-between">
          <div>
            <span class="badge inhouse-hero-badge px-3 py-2 rounded-pill mb-3">
              <i class="fas fa-handshake me-2 text-warning"></i> Coordinación Corporativa
            </span>
            <h3 class="fw-extrabold text-white mb-3">Inicia la Formación de tu Equipo</h3>
            <p class="text-white-50 mb-4" style="line-height: 1.65;">
              Completa el formulario para coordinar una reunión de diagnóstico técnico o comunícate de manera directa con nuestra área comercial.
            </p>

            <div class="inhouse-info-item">
              <div class="inhouse-info-icon">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div>
                <strong class="d-block text-white small">Sede Corporativa:</strong>
                <span class="text-white-50 small">Av. Javier Prado 757, piso 10 Magdalena, Lima 17, Perú</span>
              </div>
            </div>

            <div class="inhouse-info-item">
              <div class="inhouse-info-icon">
                <i class="fas fa-envelope"></i>
              </div>
              <div>
                <strong class="d-block text-white small">Correo Oficial:</strong>
                <a href="mailto:comercial@pmo-solutions.com" class="small">comercial@pmo-solutions.com</a>
              </div>
            </div>

            <div class="inhouse-info-item">
              <div class="inhouse-info-icon">
                <i class="fab fa-whatsapp"></i>
              </div>
              <div>
                <strong class="d-block text-white small">Teléfono / WhatsApp:</strong>
                <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20una%20propuesta%20de%20Capacitaci%C3%B3n%20In-House" target="_blank" rel="noopener noreferrer" class="small fw-bold">
                  +51 944 276 649
                </a>
              </div>
            </div>

            <div class="inhouse-info-item mb-0">
              <div class="inhouse-info-icon">
                <i class="fas fa-clock"></i>
              </div>
              <div>
                <strong class="d-block text-white small">Horario de Atención Comercial:</strong>
                <span class="text-white-50 small">Lunes a Viernes: 8:30 AM - 6:30 PM (PET)</span>
              </div>
            </div>
          </div>

          <div class="pt-4 mt-4 border-top border-white border-opacity-10 text-white-50 small">
            <i class="fas fa-user-shield text-warning me-2"></i> Respuesta garantizada en menos de 24 horas hábiles.
          </div>
        </div>
      </div>

      <!-- Columna Derecha: Formulario Conectado a /contacto/submit -->
      <div class="col-12 col-lg-7" data-aos="fade-left">
        <div class="inhouse-form-card">
          <div class="mb-4">
            <h4 class="fw-bold text-dark mb-1">Formulario de Solicitud Corporativa</h4>
            <p class="text-secondary small mb-0">Los campos marcados con (*) son obligatorios para remitir la propuesta técnica.</p>
          </div>

          <div id="contactFormFeedback" class="mb-3" style="display:none;"></div>

          <form id="contactForm" action="/contacto/submit" method="POST" novalidate>
            <!-- Token CSRF -->
            <input type="hidden" name="csrf_token" value="<?= View::e(Csrf::getToken()) ?>">

            <!-- Honeypot anti-spam -->
            <div class="d-none" aria-hidden="true">
              <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
            </div>

            <!-- Servicio Identificador -->
            <input type="hidden" name="servicio" value="Capacitaciones In-House">

            <div class="row g-3">
              <!-- Nombre Completo -->
              <div class="col-12 col-md-6">
                <label for="inhouse_nombre" class="form-label fw-bold small text-secondary">
                  Nombre y Apellido <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                  <input type="text" class="form-control border-start-0 ps-0" id="inhouse_nombre" name="nombre" placeholder="Ej. Ing. Carlos Mendoza" required>
                </div>
              </div>

              <!-- Empresa -->
              <div class="col-12 col-md-6">
                <label for="inhouse_empresa" class="form-label fw-bold small text-secondary">
                  Empresa u Organización
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="fas fa-building text-muted"></i></span>
                  <input type="text" class="form-control border-start-0 ps-0" id="inhouse_empresa" name="empresa" placeholder="Nombre de tu empresa o consorcio">
                </div>
              </div>

              <!-- Correo Corporativo -->
              <div class="col-12 col-md-6">
                <label for="inhouse_email" class="form-label fw-bold small text-secondary">
                  Correo Corporativo <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                  <input type="email" class="form-control border-start-0 ps-0" id="inhouse_email" name="email" placeholder="Ingresa tu correo corporativo" required>
                </div>
              </div>

              <!-- Teléfono / WhatsApp -->
              <div class="col-12 col-md-6">
                <label for="inhouse_telefono" class="form-label fw-bold small text-secondary">
                  Teléfono / WhatsApp <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="fab fa-whatsapp text-muted"></i></span>
                  <input type="tel" class="form-control border-start-0 ps-0" id="inhouse_telefono" name="telefono" placeholder="+51 900 000 000" required>
                </div>
              </div>

              <!-- Área de Interés -->
              <div class="col-12 col-md-6">
                <label for="inhouse_area" class="form-label fw-bold small text-secondary">
                  Área de Interés Principal
                </label>
                <select class="form-select" id="inhouse_area" name="area_interes">
                  <option value="Contratos NEC4 y FIDIC">Contratos NEC4 y FIDIC</option>
                  <option value="Dispute Boards y DAB">Dispute Boards y DAB</option>
                  <option value="Análisis Forense de Atrasos">Análisis Forense de Atrasos</option>
                  <option value="VDC y BIM Management">VDC y BIM Management</option>
                  <option value="Gestión Integral de Riesgos">Gestión Integral de Riesgos</option>
                  <option value="PMO, Planificación y Control">PMO, Planificación y Control</option>
                  <option value="Programa Integral Multidisciplinario">Programa Integral Multidisciplinario</option>
                </select>
              </div>

              <!-- Cantidad Estimada de Participantes -->
              <div class="col-12 col-md-6">
                <label for="inhouse_participantes" class="form-label fw-bold small text-secondary">
                  Participantes Estimados
                </label>
                <select class="form-select" id="inhouse_participantes" name="participantes">
                  <option value="1 a 5 personas">1 a 5 profesionales</option>
                  <option value="6 a 15 personas" selected>6 a 15 profesionales</option>
                  <option value="16 a 30 personas">16 a 30 profesionales</option>
                  <option value="Más de 30 personas">Más de 30 profesionales</option>
                </select>
              </div>

              <!-- Requerimiento o Consulta -->
              <div class="col-12">
                <label for="inhouse_mensaje" class="form-label fw-bold small text-secondary">
                  Requerimiento o Necesidad de Capacitación <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="inhouse_mensaje" name="mensaje" rows="4" placeholder="Describe brevemente los objetivos, tipo de obra/proyecto o desafíos específicos que busca resolver tu organización..." required></textarea>
                <div class="form-text small text-muted">
                  Mínimo 10 caracteres. Por favor incluye detalles relevantes del proyecto.
                </div>
              </div>

              <!-- Botón de Envío -->
              <div class="col-12 mt-4">
                <button type="submit" id="contactSubmitBtn" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white w-100 shadow-sm hvr-grow">
                  <i class="fas fa-paper-plane me-2"></i> Solicitar Propuesta Técnica y Económica
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>
