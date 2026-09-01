<!-- 1. Encabezado Hero Estilizado (Ed-Tech Catalog Style) -->
  <header class="hero-section text-center text-lg-start">
    <div class="hero-grid-overlay"></div>
    <div class="container position-relative">
      <div class="row align-items-center gy-4">
        <div class="col-lg-8">
          <div class="mb-3">
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2 hero-badge">
              <i class="fas fa-graduation-cap me-2"></i> PROGRAMAS DE ESPECIALIZACIÓN PROFESIONAL
            </span>
          </div>
          <h1 class="hero-title animate__animated animate__fadeInDown">CATÁLOGO DE CAPACITACIONES ESPECIALIZADAS</h1>
          <p class="hero-subtitle mb-4 animate__animated animate__fadeInUp animate__delay-1s">
            Aprende los estándares más exigentes de la industria en juntas de disputas, análisis forense de atrasos, contratos NEC4, VDC-BIM, gestión del cambio y compliance en la construcción.
          </p>
          <div class="d-flex flex-wrap gap-3">
            <a href="#catalogo-grid" class="btn btn-warning btn-lg fw-bold text-dark hvr-grow">
              <i class="fas fa-th-large me-2"></i> Explorar Cursos Disponibles
            </a>
            <a href="contacto" class="btn btn-outline-light btn-lg fw-bold hvr-grow">
              <i class="fas fa-headset me-2"></i> Asesoría Personalizada
            </a>
          </div>
        </div>

        <div class="col-lg-4 d-none d-lg-block">
          <div class="hero-visual-card p-4 text-center hvr-box-shadow-outset">
            <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-25 rounded-circle p-3 mb-3 text-warning">
              <i class="fas fa-user-graduate fs-1"></i>
            </div>
            <h4 class="text-white fw-bold mb-2">Formación de Élite</h4>
            <p class="text-white-50 small mb-3">Metodologías basadas en casos reales de mega proyectos y estándares internacionales.</p>
            <div class="row g-2 text-start">
              <div class="col-6">
                <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-10 text-center">
                  <div class="text-warning fw-bold fs-5">100%</div>
                  <div class="text-white-50" style="font-size: 0.72rem;">Online en Vivo</div>
                </div>
              </div>
              <div class="col-6">
                <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-10 text-center">
                  <div class="text-info fw-bold fs-5">24/7</div>
                  <div class="text-white-50" style="font-size: 0.72rem;">Aula Virtual</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- 2. Grid del Catálogo de Cursos con Filtro Interactivo -->
  <main id="catalogo-grid" class="py-5 my-lg-4">
    <div class="container">
      
      <div class="text-center mb-4" data-aos="fade-up">
        <span class="section-badge">Oferta Académica 2026</span>
        <h2 class="section-title">Programas Especializados de Ingeniería y Contratos</h2>
        <p class="section-subtitle">
          Selecciona una categoría para explorar temarios detallados, plana docente, cronogramas de clases y opciones de certificación oficial.
        </p>
      </div>

      <!-- BOTONES DE FILTRO POR CATEGORÍA -->
      <div class="catalog-filter-container" data-aos="fade-up" data-aos-delay="100">
        <button type="button" class="catalog-filter-btn active" data-filter="all">
          <i class="fas fa-th-large me-1"></i> Todos los Programas (10)
        </button>
        <button type="button" class="catalog-filter-btn" data-filter="nec-fidic">
          <i class="fas fa-file-contract me-1"></i> Contratos NEC4 & FIDIC
        </button>
        <button type="button" class="catalog-filter-btn" data-filter="dispute-boards">
          <i class="fas fa-gavel me-1"></i> Dispute Boards & Arbitraje
        </button>
        <button type="button" class="catalog-filter-btn" data-filter="forense-claims">
          <i class="fas fa-search-plus me-1"></i> Peritaje Forense & Riesgos
        </button>
        <button type="button" class="catalog-filter-btn" data-filter="bim-software">
          <i class="fas fa-cubes me-1"></i> BIM / VDC & Software
        </button>
        <button type="button" class="catalog-filter-btn" data-filter="estado-publica">
          <i class="fas fa-landmark me-1"></i> Gestión Estatal & Compliance
        </button>
      </div>

      <!-- GRID DE 10 CURSOS CATEGORIZADOS -->
      <div class="row g-4" id="coursesGridContainer">
        
        <!-- Curso 1: Dispute Boards (DAB) -->
        <div class="col-lg-4 col-md-6 catalog-card-col" data-category="dispute-boards">
          <div data-aos="fade-right" data-aos-delay="100" class="catalog-card hvr-box-shadow-outset">
            <a href="dab-jrd" class="catalog-card-img-wrapper" title="Ver programa Junta de Resolución de Disputas (DAB) en Contratos NEC">
              <img src="img/picture1b_orig.png" alt="Junta de Resolución de Disputas (DAB) en Contratos NEC" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
              <div class="catalog-card-badge">
                <span class="badge bg-primary">Dispute Boards</span>
              </div>
              <span class="hover-zoom-hint"><i class="fas fa-search-plus me-1"></i> Ver Programa</span>
            </a>
            <div class="catalog-card-body">
              <h3 class="catalog-card-title">
                <a href="dab-jrd">JUNTA DE RESOLUCIÓN DE DISPUTAS (DAB) EN CONTRATOS NEC</a>
              </h3>
              <p class="catalog-card-desc">
                Mecanismos de prevención y solución temprana de controversias bajo Dispute Boards y normativas de contratos colaborativos estándar.
              </p>
              <div class="mt-auto pt-3 border-top border-light-subtle">
                <a href="dab-jrd" class="btn btn-primary w-100 mt-2 fw-bold hvr-grow">
                  <i class="fas fa-info-circle me-2"></i> Ver Detalles e Inscripción
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Curso 2: Análisis Forense de Atrasos -->
        <div class="col-lg-4 col-md-6 catalog-card-col" data-category="forense-claims">
          <div data-aos="fade-up" data-aos-delay="200" class="catalog-card hvr-box-shadow-outset">
            <a href="analisis-forense" class="catalog-card-img-wrapper" title="Ver programa Análisis Forense de Atrasos en el Cronograma">
              <img src="img/picture2b_orig.png" alt="Análisis Forense de Atrasos en el Cronograma" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
              <div class="catalog-card-badge">
                <span class="badge bg-danger">Forense & Claims</span>
              </div>
              <span class="hover-zoom-hint"><i class="fas fa-search-plus me-1"></i> Ver Programa</span>
            </a>
            <div class="catalog-card-body">
              <h3 class="catalog-card-title">
                <a href="analisis-forense">ANÁLISIS FORENSE DE ATRASOS EN EL CRONOGRAMA</a>
              </h3>
              <p class="catalog-card-desc">
                Metodologías de cuantificación de demoras, Time Impact Analysis (TIA), As-Planned vs As-Built y sustentación pericial de reclamaciones.
              </p>
              <div class="mt-auto pt-3 border-top border-light-subtle">
                <a href="analisis-forense" class="btn btn-primary w-100 mt-2 fw-bold hvr-grow">
                  <i class="fas fa-info-circle me-2"></i> Ver Detalles e Inscripción
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Curso 3: Casos de Aplicación en Contratos NEC4 -->
        <div class="col-lg-4 col-md-6 catalog-card-col" data-category="nec-fidic">
          <div data-aos="fade-left" data-aos-delay="300" class="catalog-card hvr-box-shadow-outset">
            <a href="nec4" class="catalog-card-img-wrapper" title="Ver programa Casos de Aplicación en Contratos NEC4">
              <img src="img/picture3b_orig.png" alt="Casos de Aplicación en Contratos NEC4" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
              <div class="catalog-card-badge">
                <span class="badge bg-warning text-dark">Contratos NEC4</span>
              </div>
              <span class="hover-zoom-hint"><i class="fas fa-search-plus me-1"></i> Ver Programa</span>
            </a>
            <div class="catalog-card-body">
              <h3 class="catalog-card-title">
                <a href="nec4">CASOS DE APLICACIÓN EN CONTRATOS NEC4</a>
              </h3>
              <p class="catalog-card-desc">
                Casos prácticos de administración contractual, gestión de alertas tempranas (Early Warnings), eventos compensables y control de riesgos.
              </p>
              <div class="mt-auto pt-3 border-top border-light-subtle">
                <a href="nec4" class="btn btn-primary w-100 mt-2 fw-bold hvr-grow">
                  <i class="fas fa-info-circle me-2"></i> Ver Detalles e Inscripción
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Curso 4: Virtual Design & Construction (VDC) -->
        <div class="col-lg-4 col-md-6 catalog-card-col" data-category="bim-software">
          <div data-aos="fade-right" data-aos-delay="100" class="catalog-card hvr-box-shadow-outset">
            <a href="vdc-bim" class="catalog-card-img-wrapper" title="Ver programa Virtual Design & Construction (VDC)">
              <img src="img/picture4b_orig.png" alt="Virtual Design & Construction VDC" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
              <div class="catalog-card-badge">
                <span class="badge bg-info text-dark">BIM & VDC</span>
              </div>
              <span class="hover-zoom-hint"><i class="fas fa-search-plus me-1"></i> Ver Programa</span>
            </a>
            <div class="catalog-card-body">
              <h3 class="catalog-card-title">
                <a href="vdc-bim">VIRTUAL DESIGN & CONSTRUCTION (VDC)</a>
              </h3>
              <p class="catalog-card-desc">
                Metodología Stanford CIFE: Integración de modelos BIM 3D/4D/5D, ingeniería concurrente (Sesiones ICE) y métricas de producción PPM.
              </p>
              <div class="mt-auto pt-3 border-top border-light-subtle">
                <a href="vdc-bim" class="btn btn-primary w-100 mt-2 fw-bold hvr-grow">
                  <i class="fas fa-info-circle me-2"></i> Ver Detalles e Inscripción
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Curso 5: Gestión del Cambio en los Contratos con el Estado -->
        <div class="col-lg-4 col-md-6 catalog-card-col" data-category="estado-publica">
          <div data-aos="fade-up" data-aos-delay="200" class="catalog-card hvr-box-shadow-outset">
            <a href="contratos-estado" class="catalog-card-img-wrapper" title="Ver programa Gestión del Cambio en los Contratos con el Estado">
              <img src="img/picture5b_orig.png" alt="Gestión del Cambio en Contratos con el Estado" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
              <div class="catalog-card-badge">
                <span class="badge bg-success">Contratación Pública</span>
              </div>
              <span class="hover-zoom-hint"><i class="fas fa-search-plus me-1"></i> Ver Programa</span>
            </a>
            <div class="catalog-card-body">
              <h3 class="catalog-card-title">
                <a href="contratos-estado">GESTIÓN DEL CAMBIO EN LOS CONTRATOS CON EL ESTADO</a>
              </h3>
              <p class="catalog-card-desc">
                Administración de ampliaciones de plazo, adicionales de obra, deductivos y modificaciones contractuales conforme a la Ley de Contrataciones.
              </p>
              <div class="mt-auto pt-3 border-top border-light-subtle">
                <a href="contratos-estado" class="btn btn-primary w-100 mt-2 fw-bold hvr-grow">
                  <i class="fas fa-info-circle me-2"></i> Ver Detalles e Inscripción
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Curso 6: Gestión de Compliance en la Construcción -->
        <div class="col-lg-4 col-md-6 catalog-card-col" data-category="estado-publica">
          <div data-aos="fade-left" data-aos-delay="300" class="catalog-card hvr-box-shadow-outset">
            <a href="compliance" class="catalog-card-img-wrapper" title="Ver programa Gestión de Compliance en la Construcción">
              <img src="img/picture6b_orig.png" alt="Gestión de Compliance en la Construcción" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
              <div class="catalog-card-badge">
                <span class="badge bg-dark">Compliance & ISO</span>
              </div>
              <span class="hover-zoom-hint"><i class="fas fa-search-plus me-1"></i> Ver Programa</span>
            </a>
            <div class="catalog-card-body">
              <h3 class="catalog-card-title">
                <a href="compliance">GESTIÓN DE COMPLIANCE EN LA CONSTRUCCIÓN</a>
              </h3>
              <p class="catalog-card-desc">
                Modelos de prevención penal, sistemas antisoborno ISO 37001, matrices de riesgo y gobernanza corporativa en proyectos de construcción.
              </p>
              <div class="mt-auto pt-3 border-top border-light-subtle">
                <a href="compliance" class="btn btn-primary w-100 mt-2 fw-bold hvr-grow">
                  <i class="fas fa-info-circle me-2"></i> Ver Detalles e Inscripción
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Curso 7: Primavera P6 & Power BI -->
        <div class="col-lg-4 col-md-6 catalog-card-col" data-category="bim-software">
          <div data-aos="fade-right" data-aos-delay="100" class="catalog-card hvr-box-shadow-outset">
            <a href="primavera-p6" class="catalog-card-img-wrapper" title="Ver programa Primavera P6 & Power BI">
              <img src="img/controlproyectos.png" alt="Primavera P6 & Power BI" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
              <div class="catalog-card-badge">
                <span class="badge bg-info text-dark">Software & Control</span>
              </div>
              <span class="hover-zoom-hint"><i class="fas fa-search-plus me-1"></i> Ver Programa</span>
            </a>
            <div class="catalog-card-body">
              <h3 class="catalog-card-title">
                <a href="primavera-p6">CONTROL DE PROYECTOS CON PRIMAVERA P6 & POWER BI</a>
              </h3>
              <p class="catalog-card-desc">
                Planificación avanzada, programación de cronogramas, análisis de ruta crítica, valor ganado y tableros directivos de control en Power BI.
              </p>
              <div class="mt-auto pt-3 border-top border-light-subtle">
                <a href="primavera-p6" class="btn btn-primary w-100 mt-2 fw-bold hvr-grow">
                  <i class="fas fa-info-circle me-2"></i> Ver Detalles e Inscripción
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Curso 8: Gestión Integral de Riesgos PMI -->
        <div class="col-lg-4 col-md-6 catalog-card-col" data-category="forense-claims">
          <div data-aos="fade-up" data-aos-delay="200" class="catalog-card hvr-box-shadow-outset">
            <a href="riesgos-pmi" class="catalog-card-img-wrapper" title="Ver programa Gestión Integral de Riesgos PMI">
              <img src="img/gestionriesgos.png" alt="Gestión Integral de Riesgos PMI" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
              <div class="catalog-card-badge">
                <span class="badge bg-danger">Riesgos PMI®</span>
              </div>
              <span class="hover-zoom-hint"><i class="fas fa-search-plus me-1"></i> Ver Programa</span>
            </a>
            <div class="catalog-card-body">
              <h3 class="catalog-card-title">
                <a href="riesgos-pmi">GESTIÓN INTEGRAL DE RIESGOS EN PROYECTOS (PMI®)</a>
              </h3>
              <p class="catalog-card-desc">
                Identificación, evaluación cualitativa y cuantitativa, planificación de respuestas y monitoreo de riesgos en contratos de infraestructura.
              </p>
              <div class="mt-auto pt-3 border-top border-light-subtle">
                <a href="riesgos-pmi" class="btn btn-primary w-100 mt-2 fw-bold hvr-grow">
                  <i class="fas fa-info-circle me-2"></i> Ver Detalles e Inscripción
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Curso 9: Análisis Cuantitativo de Riesgos -->
        <div class="col-lg-4 col-md-6 catalog-card-col" data-category="forense-claims">
          <div data-aos="fade-left" data-aos-delay="300" class="catalog-card hvr-box-shadow-outset">
            <a href="analisis-cuantitativo" class="catalog-card-img-wrapper" title="Ver programa Análisis Cuantitativo de Riesgos">
              <img src="img/analisiscuantitativo.png" alt="Análisis Cuantitativo de Riesgos" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
              <div class="catalog-card-badge">
                <span class="badge bg-danger">Simulación & Riesgos</span>
              </div>
              <span class="hover-zoom-hint"><i class="fas fa-search-plus me-1"></i> Ver Programa</span>
            </a>
            <div class="catalog-card-body">
              <h3 class="catalog-card-title">
                <a href="analisis-cuantitativo">ANÁLISIS CUANTITATIVO DE RIESGOS EN CRONOGRAMAS</a>
              </h3>
              <p class="catalog-card-desc">
                Simulación probabilística de Monte Carlo con Primavera Risk Analysis y @RISK para determinar reservas de contingencia de plazo y costo.
              </p>
              <div class="mt-auto pt-3 border-top border-light-subtle">
                <a href="analisis-cuantitativo" class="btn btn-primary w-100 mt-2 fw-bold hvr-grow">
                  <i class="fas fa-info-circle me-2"></i> Ver Detalles e Inscripción
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Curso 10: Eventos Compensables -->
        <div class="col-lg-4 col-md-6 catalog-card-col" data-category="nec-fidic">
          <div data-aos="fade-right" data-aos-delay="100" class="catalog-card hvr-box-shadow-outset">
            <a href="eventos-compensables" class="catalog-card-img-wrapper" title="Ver programa Eventos Compensables">
              <img src="img/gestioneventos.png" alt="Eventos Compensables en Contratos NEC4" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
              <div class="catalog-card-badge">
                <span class="badge bg-warning text-dark">Eventos NEC4</span>
              </div>
              <span class="hover-zoom-hint"><i class="fas fa-search-plus me-1"></i> Ver Programa</span>
            </a>
            <div class="catalog-card-body">
              <h3 class="catalog-card-title">
                <a href="eventos-compensables">GESTIÓN DE EVENTOS COMPENSABLES EN CONTRATOS NEC</a>
              </h3>
              <p class="catalog-card-desc">
                Identificación, notificación, cotización, evaluación de impacto en el Costo Definido y Programa de Obra en contratos NEC3 y NEC4.
              </p>
              <div class="mt-auto pt-3 border-top border-light-subtle">
                <a href="eventos-compensables" class="btn btn-primary w-100 mt-2 fw-bold hvr-grow">
                  <i class="fas fa-info-circle me-2"></i> Ver Detalles e Inscripción
                </a>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>

  <!-- 3. Sección de Garantía de Aprendizaje Ejecutivo (3 Pilares) -->
  <section class="values-section py-5">
    <div class="container py-lg-4">
      <div class="text-center mb-5">
        <span class="section-badge">Metodología Comprobada</span>
        <h2 class="section-title">Garantía de Aprendizaje Ejecutivo</h2>
        <p class="section-subtitle">
          Nuestra experiencia formativa está diseñada para maximizar el valor profesional y la aplicabilidad inmediata en obra y oficina técnica.
        </p>
      </div>

      <div class="row g-4">
        <!-- Pilar 1: Clases en Vivo 100% Interactivas -->
        <div class="col-lg-4 col-md-6">
          <div class="guarantee-card hvr-box-shadow-outset">
            <div class="guarantee-icon-box bg-primary bg-opacity-10 text-primary">
              <i class="fas fa-video text-primary fs-2"></i>
            </div>
            <h4 class="fw-bold mb-3">Clases en Vivo 100% Interactivas</h4>
            <p class="text-muted mb-0">
              Sesiones sincrónicas con docentes de amplia trayectoria internacional, propiciando el debate técnico y la resolución de dudas en tiempo real.
            </p>
          </div>
        </div>

        <!-- Pilar 2: Acceso a Grabaciones 24/7 -->
        <div class="col-lg-4 col-md-6">
          <div class="guarantee-card hvr-box-shadow-outset">
            <div class="guarantee-icon-box bg-success bg-opacity-10 text-success">
              <i class="fas fa-play-circle text-success fs-2"></i>
            </div>
            <h4 class="fw-bold mb-3">Acceso a Grabaciones 24/7</h4>
            <p class="text-muted mb-0">
              Revisa las sesiones y materiales académicos en cualquier momento a través de nuestra plataforma virtual en alta definición.
            </p>
          </div>
        </div>

        <!-- Pilar 3: Certificación Institucional -->
        <div class="col-lg-4 col-md-12">
          <div class="guarantee-card hvr-box-shadow-outset">
            <div class="guarantee-icon-box bg-warning bg-opacity-10 text-warning">
              <i class="fas fa-certificate text-warning fs-2"></i>
            </div>
            <h4 class="fw-bold mb-3">Certificación Institucional</h4>
            <p class="text-muted mb-0">
              Acreditación de participación emitida por PMO Solutions, con código de verificación QR y horas lectivas válidas para tu CV profesional.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Banner CTA In-Company -->
  <section class="container my-5">
    <div class="cta-banner">
      <div class="row align-items-center position-relative" style="z-index: 2;">
        <div class="col-lg-8 mb-4 mb-lg-0">
          <span class="badge bg-warning text-dark px-3 py-2 fw-bold mb-3">CAPACITACIONES CORPORATIVAS A MEDIDA</span>
          <h3 class="text-white fw-bold display-6 mb-3">¿Desea capacitar al equipo técnico de su empresa?</h3>
          <p class="text-white-50 mb-0 fs-5">
            Adaptamos cualquiera de nuestros programas con casos de estudio propios de su organización o proyecto en ejecución.
          </p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="contacto" class="btn btn-warning btn-lg fw-bold px-4 py-3 text-dark shadow hvr-grow">
            <i class="fas fa-briefcase me-2"></i> Cotizar Plan In-Company
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- 4. Footer Corporativo Limpio (Con Selector Multipaís & Libro de Reclamaciones) -->