<?php
/**
 * PMO SOLUTIONS - Vista de Página: Universidad Corporativa & Formación Empresarial
 * Archivo: private_app/app/Views/pages/advisory/universidad-corporativa.php
 */
use App\Core\View;
?>

<!-- 1. Encabezado Hero Corporativo PMO -->
<header class="hero-section position-relative text-white">
  <div class="hero-grid-overlay"></div>
  <div class="container position-relative py-4 py-lg-5">
    <div class="row align-items-center gy-4">
      <div class="col-12 col-lg-8">
        <div class="mb-3">
          <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold shadow-sm hero-badge">
            <i class="fas fa-graduation-cap me-2"></i> Formación Empresarial de Alto Impacto
          </span>
        </div>
        <h1 class="hero-title fw-extrabold mb-3 animate__animated animate__fadeInDown">
          UNIVERSIDAD CORPORATIVA
        </h1>
        <p class="hero-subtitle text-white-50 fs-5 mb-4 animate__animated animate__fadeInUp animate__delay-1s" style="line-height: 1.6;">
          Asesoría especializada para orientar el diseño, estructuración y despliegue de academias corporativas y mallas formativas a la medida de las necesidades, metas operativas y objetivos estratégicos de tu organización.
        </p>
        <div class="d-flex flex-wrap gap-3 pt-2">
          <a href="/contacto" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
            <i class="fas fa-clipboard-check me-2"></i> Solicitar Asesoría
          </a>
          <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20asesor%C3%ADa%20en%20Universidad%20Corporativa" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light fw-bold px-4 py-3 shadow-sm hvr-grow">
            <i class="fab fa-whatsapp me-2 text-success"></i> Contactar Especialista
          </a>
        </div>
      </div>

      <div class="col-12 col-lg-4 text-center d-none d-lg-block">
        <div class="p-4 rounded-4 bg-white bg-opacity-10 backdrop-blur border border-white border-opacity-15 shadow-lg">
          <i class="fas fa-university text-warning mb-3" style="font-size: 3.5rem;"></i>
          <h4 class="text-white fw-bold mb-2">Talento Técnico Alineado</h4>
          <p class="text-white-50 small mb-0">Estandarización del know-how crítico de tu empresa para multiplicar la productividad en obra.</p>
        </div>
      </div>
    </div>

    <!-- Grid de 3 Pilares en Hero -->
    <div class="row g-3 mt-4 pt-3 border-top border-white border-opacity-10">
      <div class="col-12 col-md-4">
        <div class="p-3 rounded-3 bg-white bg-opacity-10 text-white h-100 border border-white border-opacity-10">
          <span class="badge bg-primary text-white mb-2"><i class="fas fa-chalkboard-user me-1"></i> Aprendizaje a Medida</span>
          <p class="small mb-0 text-white-50">Diseño de mallas curriculares y rutas formativas alineadas a la realidad de tu empresa.</p>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="p-3 rounded-3 bg-white bg-opacity-10 text-white h-100 border border-white border-opacity-10">
          <span class="badge bg-warning text-dark mb-2"><i class="fas fa-brain me-1"></i> Gestión del Conocimiento</span>
          <p class="small mb-0 text-white-50">Estandarización y retención del know-how operativo de tus mejores ingenieros.</p>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="p-3 rounded-3 bg-white bg-opacity-10 text-white h-100 border border-white border-opacity-10">
          <span class="badge bg-success text-white mb-2"><i class="fas fa-award me-1"></i> Docencia de Alto Nivel</span>
          <p class="small mb-0 text-white-50">Instructores asociados con liderazgo comprobado en megaproyectos y docencia ejecutiva.</p>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- 2. ¿Cómo construimos tu Universidad Corporativa? (6 Pilares) -->
<section class="py-5 bg-light" id="pilares-universidad">
  <div class="container py-lg-4">
    <div class="text-center max-w-700 mx-auto mb-5" style="max-width: 720px;">
      <span class="text-primary fw-bold text-uppercase small tracking-wide">
        <i class="fas fa-sitemap me-1"></i> Arquitectura de Formación Empresarial
      </span>
      <h2 class="h1 fw-bold text-dark mt-2 mb-3">¿CÓMO CONSTRUIMOS TU UNIVERSIDAD CORPORATIVA?</h2>
      <div class="heading-line-sm mx-auto mb-3" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
      <p class="text-secondary">Acompañamos a tu organización en cada fase del desarrollo de su ecosistema de aprendizaje, desde el diagnóstico hasta la medición de impacto operativo.</p>
    </div>

    <div class="row g-4">
      <!-- Pilar 1 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-primary">
          <div class="p-3 rounded-3 bg-primary bg-opacity-10 text-primary fs-4 mb-3 align-self-start">
            <i class="fas fa-handshake-angle"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Asesoría Profesional</h5>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Acompañamiento integral y metodológico en la concepción, gobernanza, dimensionamiento y puesta en marcha del proyecto académico corporativo.
          </p>
        </div>
      </div>

      <!-- Pilar 2 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-warning">
          <div class="p-3 rounded-3 bg-warning bg-opacity-10 text-warning fs-4 mb-3 align-self-start">
            <i class="fas fa-chess-knight"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Estrategia de Capacitación</h5>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Desarrollo del plan maestro formativo rigurosamente alineado a los objetivos de negocio, metas de productividad y cultura corporativa de la organización.
          </p>
        </div>
      </div>

      <!-- Pilar 3 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-danger">
          <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger fs-4 mb-3 align-self-start">
            <i class="fas fa-sitemap"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Malla de Capacitación</h5>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Estructuración modular y secuencial de rutas de aprendizaje progresivo adaptadas por roles: operativos, técnicos, mandos medios y directivos de obra.
          </p>
        </div>
      </div>

      <!-- Pilar 4 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-info">
          <div class="p-3 rounded-3 bg-info bg-opacity-10 text-info fs-4 mb-3 align-self-start">
            <i class="fas fa-pen-ruler"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Diseño de Cursos</h5>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Creación de contenidos teórico-prácticos, talleres aplicados, material didáctico estructurado y casos de estudio reales del sector de construcción.
          </p>
        </div>
      </div>

      <!-- Pilar 5 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="500">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-secondary">
          <div class="p-3 rounded-3 bg-secondary bg-opacity-10 text-secondary fs-4 mb-3 align-self-start">
            <i class="fas fa-chart-pie"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Evaluación del Desempeño</h5>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Instrumentos de medición del aprendizaje, evaluación de competencias adquiridas y métricas de impacto en el desempeño laboral en campo y oficina técnica.
          </p>
        </div>
      </div>

      <!-- Pilar 6 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="600">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-success">
          <div class="p-3 rounded-3 bg-success bg-opacity-10 text-success fs-4 mb-3 align-self-start">
            <i class="fas fa-brain"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Gestión del Conocimiento</h5>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Captura, sistematización y capitalización del know-how interno para evitar la fuga de talento y convertir la experiencia individual en valor corporativo sostenible.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 3. Factor Diferenciador & Dashboard de Formación -->
<section class="py-5 bg-white" id="diferenciadores-universidad">
  <div class="container py-lg-4">
    <div class="row align-items-center gy-5">
      <div class="col-12 col-lg-6" data-aos="fade-right">
        <div class="mb-3">
          <span class="text-primary fw-bold text-uppercase small tracking-wide">
            <i class="fas fa-award me-1"></i> Excelencia en Cédula Docente
          </span>
          <h2 class="h1 fw-bold text-dark mt-2 mb-3">NUESTROS ASOCIADOS SON NUESTRA PRINCIPAL VENTAJA</h2>
          <div class="heading-line-sm mb-4" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
        </div>
        <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.7;">
          El valor de nuestra consultoría académica radica en el altísimo nivel de nuestros instructores y consultores asociados, quienes combinan liderazgo real en la industria de la construcción con probada excelencia metodológica.
        </p>

        <div class="d-flex flex-column gap-3">
          <div class="d-flex align-items-start gap-3">
            <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary fs-5 mt-1">
              <i class="fas fa-briefcase"></i>
            </div>
            <div>
              <h6 class="fw-bold text-dark mb-1">Experiencia Ejecutiva en Empresas de Gran Envergadura</h6>
              <p class="text-secondary small mb-0">Profesionales que han liderado megaproyectos y direcciones corporativas en las principales compañías del sector.</p>
            </div>
          </div>

          <div class="d-flex align-items-start gap-3">
            <div class="p-2 rounded-3 bg-warning bg-opacity-10 text-warning fs-5 mt-1">
              <i class="fas fa-hard-hat"></i>
            </div>
            <div>
              <h6 class="fw-bold text-dark mb-1">Conocimiento Profundo y Real de Obra</h6>
              <p class="text-secondary small mb-0">Casos prácticos de ingeniería, gestión contractual, calidad y control de riesgos fundamentados en la realidad de campo.</p>
            </div>
          </div>

          <div class="d-flex align-items-start gap-3">
            <div class="p-2 rounded-3 bg-success bg-opacity-10 text-success fs-5 mt-1">
              <i class="fas fa-university"></i>
            </div>
            <div>
              <h6 class="fw-bold text-dark mb-1">Docencia en Prestigiosas Escuelas y Posgrados</h6>
              <p class="text-secondary small mb-0">Catedráticos e instructores con destacada trayectoria en centros de capacitación ejecutiva del país.</p>
            </div>
          </div>

          <div class="d-flex align-items-start gap-3">
            <div class="p-2 rounded-3 bg-info bg-opacity-10 text-info fs-5 mt-1">
              <i class="fas fa-network-wired"></i>
            </div>
            <div>
              <h6 class="fw-bold text-dark mb-1">Red de Contactos de Alto Nivel</h6>
              <p class="text-secondary small mb-0">Acceso a una comunidad estratégica de especialistas y referentes técnicos de la ingeniería.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6" data-aos="fade-left">
        <!-- Mockup Visual del Ecosistema de Formación -->
        <div class="card border-0 shadow-lg rounded-4 p-4 bg-dark text-white">
          <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-secondary">
            <div>
              <h5 class="fw-bold mb-1 text-white"><i class="fas fa-laptop-code text-warning me-2"></i> Formación Corporativa a Medida</h5>
              <small class="text-white-50">Entrenamiento y Aprendizaje Especializado</small>
            </div>
            <span class="badge bg-success text-white">Programas Activos</span>
          </div>

          <!-- Métricas -->
          <div class="row g-2 text-center mb-4">
            <div class="col-4">
              <div class="p-2 rounded-3 bg-white bg-opacity-10">
                <div class="fw-bold fs-5 text-warning">+1,200</div>
                <small class="text-white-50" style="font-size: 0.72rem;">Profesionales</small>
              </div>
            </div>
            <div class="col-4">
              <div class="p-2 rounded-3 bg-white bg-opacity-10">
                <div class="fw-bold fs-5 text-success">98.4%</div>
                <small class="text-white-50" style="font-size: 0.72rem;">Satisfacción</small>
              </div>
            </div>
            <div class="col-4">
              <div class="p-2 rounded-3 bg-white bg-opacity-10">
                <div class="fw-bold fs-5 text-info">100%</div>
                <small class="text-white-50" style="font-size: 0.72rem;">Casos Reales</small>
              </div>
            </div>
          </div>

          <!-- Módulos de Muestra -->
          <div class="d-flex flex-column gap-2 mb-3">
            <div class="p-2 px-3 rounded-3 bg-white bg-opacity-5 d-flex justify-content-between align-items-center">
              <span class="small text-white-50"><i class="fas fa-check-circle text-success me-2"></i> Gestión Avanzada de PMO & EVM</span>
              <span class="badge bg-primary">Completado</span>
            </div>
            <div class="p-2 px-3 rounded-3 bg-white bg-opacity-5 d-flex justify-content-between align-items-center">
              <span class="small text-white-50"><i class="fas fa-play-circle text-warning me-2"></i> Contratos NEC4 & Prevención de Disputas</span>
              <span class="badge bg-warning text-dark">En Curso</span>
            </div>
            <div class="p-2 px-3 rounded-3 bg-white bg-opacity-5 d-flex justify-content-between align-items-center">
              <span class="small text-white-50"><i class="fas fa-play-circle text-warning me-2"></i> Planificación Lean & Last Planner System</span>
              <span class="badge bg-info text-dark">En Curso</span>
            </div>
          </div>

          <div class="p-2 rounded-3 bg-white bg-opacity-10 text-center small text-white-50">
            <i class="fas fa-award text-warning me-1"></i> <strong>Certificación Corporativa</strong> con Validez Empresarial
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 4. Banner Promocional: Catálogo de Capacitaciones Corporativas -->
<section class="py-5 bg-light" id="catalogo-corporativo">
  <div class="container">
    <div class="card border-0 shadow-sm rounded-4 p-4 p-lg-5 bg-white border-start border-4 border-warning">
      <div class="row align-items-center gy-4">
        <div class="col-12 col-lg-8">
          <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2">
            <i class="fas fa-tag me-1"></i> Programas In-House & Corporativos
          </span>
          <h3 class="fw-extrabold text-dark mb-2">Impulsa las competencias de tu equipo técnico</h3>
          <p class="text-secondary mb-0">
            Accede a nuestra oferta integral de especializaciones técnicas o solicita una malla curricular adaptada 100% a las necesidades de tus proyectos.
          </p>
        </div>
        <div class="col-12 col-lg-4 text-lg-end">
          <a href="/capacitaciones" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
            <i class="fas fa-graduation-cap me-2"></i> Ver Catálogo de Cursos
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 5. Experiencia y Cédula Docente en Acción (3 Fotografías Reales) -->
<section class="py-5 bg-white" id="docentes-eventos">
  <div class="container py-lg-4">
    <div class="text-center max-w-700 mx-auto mb-5" style="max-width: 720px;">
      <span class="text-primary fw-bold text-uppercase small tracking-wide">
        <i class="fas fa-chalkboard-teacher me-1"></i> Trayectoria Académica Comprobada
      </span>
      <h2 class="h1 fw-bold text-dark mt-2 mb-3">EXPERIENCIA Y CÉDULA DOCENTE EN ACCIÓN</h2>
      <div class="heading-line-sm mx-auto mb-3" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
      <p class="text-secondary">Nuestros especialistas liderando conferencias magistrales, cátedras universitarias y programas ejecutivos en las principales instituciones del país.</p>
    </div>

    <div class="row g-4">
      <!-- Foto 1 -->
      <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
          <div class="position-relative">
            <picture>
              <source srcset="/img/uni1.webp" type="image/webp">
              <img src="/img/uni1.png" alt="Conferencia de capacitación sobre Normas ISO 14001" class="card-img-top" loading="lazy" style="height: 230px; object-fit: cover;">
            </picture>
            <span class="badge bg-primary position-absolute bottom-0 start-0 m-3"><i class="fas fa-award me-1"></i> Capacitación Normativa</span>
          </div>
          <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-2">Conferencia de Capacitación Normativa</h5>
            <p class="text-secondary small mb-0">Sesiones dinámicas de especialización en gestión ambiental y normas internacionales de calidad para equipos técnicos y de supervisión.</p>
          </div>
        </div>
      </div>

      <!-- Foto 2 -->
      <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
          <div class="position-relative">
            <picture>
              <source srcset="/img/uni2.webp" type="image/webp">
              <img src="/img/uni2.png" alt="Ponencia académica universitaria sobre ISO 9001" class="card-img-top" loading="lazy" style="height: 230px; object-fit: cover;">
            </picture>
            <span class="badge bg-warning text-dark position-absolute bottom-0 start-0 m-3"><i class="fas fa-university me-1"></i> Cátedra Universitaria</span>
          </div>
          <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-2">Ponencia Académica Universitaria</h5>
            <p class="text-secondary small mb-0">Cátedra ejecutiva sobre Sistemas de Gestión de la Calidad ISO 9001 impartida a profesionales de posgrado en prestigiosas universidades.</p>
          </div>
        </div>
      </div>

      <!-- Foto 3 -->
      <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="300">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
          <div class="position-relative">
            <picture>
              <source srcset="/img/uni3.webp" type="image/webp">
              <img src="/img/uni3.png" alt="Presentación ejecutiva sobre gestión de riesgos en proyectos" class="card-img-top" loading="lazy" style="height: 230px; object-fit: cover;">
            </picture>
            <span class="badge bg-success position-absolute bottom-0 start-0 m-3"><i class="fas fa-shield-alt me-1"></i> Megaproyectos</span>
          </div>
          <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-2">Presentación Ejecutiva en Proyectos</h5>
            <p class="text-secondary small mb-0">Exposición metodológica sobre cuantificación de riesgos de plazo y costo para comités directivos en obras de infraestructura.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 6. CTA Final -->
<section class="py-5 text-white position-relative" style="background: linear-gradient(135deg, var(--pmo-primary-dark) 0%, var(--pmo-primary) 60%, #003870 100%);">
  <div class="container py-4 text-center">
    <div class="max-w-700 mx-auto" style="max-width: 780px;">
      <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">
        <i class="fas fa-graduation-cap me-1"></i> Formación Estratégica
      </span>
      <h2 class="h1 fw-extrabold text-white mb-3">
        Diseña la Universidad Corporativa de tu empresa
      </h2>
      <p class="text-white-50 fs-5 mb-4">
        Estandariza el conocimiento crítico de tu organización, potencia las capacidades de tus ingenieros y asegura la excelencia operativa en tus proyectos.
      </p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="/contacto" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
          <i class="fas fa-clipboard-check me-2"></i> Solicitar Asesoría
        </a>
        <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20asesor%C3%ADa%20en%20Universidad%20Corporativa" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light fw-bold px-4 py-3 shadow-sm hvr-grow">
          <i class="fab fa-whatsapp me-2 text-success"></i> Contactar por WhatsApp
        </a>
      </div>
    </div>
  </div>
</section>

