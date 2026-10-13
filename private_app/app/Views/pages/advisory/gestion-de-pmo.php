<?php
/**
 * PMO SOLUTIONS - Vista de Página: Gestión de Proyectos & PMO
 * Archivo: private_app/app/Views/pages/advisory/gestion-de-pmo.php
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
            <i class="fas fa-sitemap me-2"></i> Asesoría Especializada en Construcción
          </span>
        </div>
        <h1 class="hero-title fw-extrabold mb-3 animate__animated animate__fadeInDown">
          GESTIÓN DE PROYECTOS & PMO
        </h1>
        <p class="hero-subtitle text-white-50 fs-5 mb-4 animate__animated animate__fadeInUp animate__delay-1s" style="line-height: 1.6;">
          Asesoría especializada para orientar el diseño, adopción y consolidación de Oficinas de Gestión de Proyectos (PMO) en organizaciones y proyectos de construcción, integrando las áreas de conocimiento del project management: alcance, costo, plazo, calidad, riesgos y gobernanza, fortaleciendo las capacidades del equipo interno.
        </p>
        <div class="d-flex flex-wrap gap-3 pt-2">
          <a href="/contacto" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
            <i class="fas fa-clipboard-check me-2"></i> Solicitar Asesoría
          </a>
          <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20asesor%C3%ADa%20en%20Gobernanza%20y%20Gesti%C3%B3n%20de%20PMO" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light fw-bold px-4 py-3 shadow-sm hvr-grow">
            <i class="fab fa-whatsapp me-2 text-success"></i> Contactar Asesor
          </a>
        </div>
      </div>
      <div class="col-12 col-lg-4 text-center d-none d-lg-block">
        <div class="p-4 rounded-4 bg-white bg-opacity-10 backdrop-blur border border-white border-opacity-15 shadow-lg">
          <i class="fas fa-layer-group text-warning mb-3" style="font-size: 3.5rem;"></i>
          <h4 class="text-white fw-bold mb-2">Madurez Organizacional</h4>
          <p class="text-white-50 small mb-0">Estructuración pragmática no burocrática alineada a los objetivos de inversión y negocio.</p>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- 2. Sección: Desarrollo de la PMO (Nivel de Madurez Organizacional) -->
<section class="py-5 bg-light" id="desarrollo-pmo">
  <div class="container py-lg-4">
    <div class="row align-items-center gy-5">
      <div class="col-12 col-lg-6" data-aos="fade-right">
        <div class="mb-3">
          <span class="text-primary fw-bold text-uppercase small tracking-wide">
            <i class="fas fa-chart-line me-1"></i> Nivel de Madurez Organizacional
          </span>
          <h2 class="h1 fw-bold text-dark mt-2 mb-3">DESARROLLO DE LA PMO</h2>
          <div class="heading-line-sm mb-4" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
        </div>
        <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.7;">
          Brindamos asesoría especializada para orientar la estructuración de la PMO al nivel requerido por tu organización: a nivel de gestor de estándares, gestor de proyectos o nivel estratégico. Acompañamos en el ordenamiento de estándares, transferencia metodológica e integración con tus stakeholders clave. Brindamos soporte técnico y juicio experto para evaluar soluciones informáticas de administración de proyectos, con un enfoque práctico y no burocrático.
        </p>

        <!-- Niveles de Desarrollo Grid -->
        <div class="row g-3">
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-primary">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary fs-5">
                  <i class="fas fa-list-check"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Nivel de Gestor de Estándares</h5>
                  <p class="text-secondary small mb-0">Compilación, estandarización y estructuración de plantillas y normativas corporativas.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-warning">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-warning bg-opacity-10 text-warning fs-5">
                  <i class="fas fa-project-diagram"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Nivel de Gestor de Proyecto</h5>
                  <p class="text-secondary small mb-0">Control integral de alcance, cronograma de plazo y costo en tiempo real.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-success">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-success bg-opacity-10 text-success fs-5">
                  <i class="fas fa-chess-knight"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Nivel Estratégico</h5>
                  <p class="text-secondary small mb-0">Alineamiento del portafolio con los objetivos de negocio y retorno de inversión.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6" data-aos="fade-left">
        <div class="position-relative text-center">
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <picture>
              <source srcset="/img/gesPMO1.webp" type="image/webp">
              <img src="/img/gesPMO1.png" alt="Equipo de gestión de proyectos y desarrollo de PMO" class="img-fluid w-100 rounded-4" loading="lazy" width="600" height="400">
            </picture>
            <div class="p-3 bg-white text-start border-top">
              <span class="badge bg-primary text-white mb-1"><i class="fas fa-chart-pie me-1"></i> PMO Estratégica</span>
              <p class="text-secondary small mb-0">Integración fluida con contratistas, supervisión y stakeholders clave.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 3. Sección: Metodología PHVA y Prevención del 90% -->
<section class="py-5 bg-white" id="metodologia">
  <div class="container py-lg-4">
    <div class="row align-items-center gy-5 flex-lg-row-reverse">
      <div class="col-12 col-lg-6" data-aos="fade-left">
        <div class="mb-3">
          <span class="text-primary fw-bold text-uppercase small tracking-wide">
            <i class="fas fa-sync-alt me-1"></i> Marco de Trabajo & Excelencia
          </span>
          <h2 class="h1 fw-bold text-dark mt-2 mb-3">NUESTRA METODOLOGÍA</h2>
          <div class="heading-line-sm mb-4" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
        </div>
        <p class="text-secondary mb-3" style="font-size: 1.05rem; line-height: 1.7;">
          Nos basamos en el ciclo de mejora continua de Shewhart <strong>PHVA</strong> (Planificar, Hacer, Verificar, Actuar). En ese sentido definimos en la planificación los activos de los procesos de gestión de la organización, recopilamos las prácticas vigentes, configuramos una propuesta técnica y la validamos con recorridos de mapeo de procesos (process mapping).
        </p>
        <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.7;">
          Subsecuentemente, brindamos acompañamiento y asesoría para orientar la adopción gradual de procesos y evaluamos los entregables según costo-beneficio. Planteamos los ajustes metodológicos requeridos para fortalecer las capacidades internas hasta que la PMO sea sostenible por sí misma en la organización.
        </p>

        <!-- Bloque Destacado: Curva de Liberación -->
        <div class="p-3 rounded-4 bg-light border border-warning border-2 mb-4">
          <div class="d-flex align-items-start gap-3">
            <div class="p-2 rounded-circle bg-warning text-dark fs-5 mt-1">
              <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-1 text-dark">Control Riguroso del Plazo</h5>
              <p class="text-secondary small mb-0">
                Asimismo, transferimos y aplicamos nuestra metodología estructurada para la evaluación del cumplimiento del plazo (prevención del síndrome del 90%), método expuesto y validado en foros de excelencia técnica internacional.
              </p>
            </div>
          </div>
        </div>

        <!-- Badges PHVA -->
        <div class="row g-2 text-center">
          <div class="col-3">
            <div class="p-2 rounded-3 bg-primary text-white fw-bold shadow-sm">
              <div class="fs-4">P</div>
              <small style="font-size: 0.75rem;">Planificar</small>
            </div>
          </div>
          <div class="col-3">
            <div class="p-2 rounded-3 bg-secondary text-white fw-bold shadow-sm">
              <div class="fs-4">H</div>
              <small style="font-size: 0.75rem;">Hacer</small>
            </div>
          </div>
          <div class="col-3">
            <div class="p-2 rounded-3 bg-warning text-dark fw-bold shadow-sm">
              <div class="fs-4">V</div>
              <small style="font-size: 0.75rem;">Verificar</small>
            </div>
          </div>
          <div class="col-3">
            <div class="p-2 rounded-3 bg-success text-white fw-bold shadow-sm">
              <div class="fs-4">A</div>
              <small style="font-size: 0.75rem;">Actuar</small>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6" data-aos="fade-right">
        <div class="position-relative text-center">
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <picture>
              <source srcset="/img/gesPMO2.webp" type="image/webp">
              <img src="/img/gesPMO2.png" alt="Metodología de mejora continua y ciclo PHVA en gestión de proyectos" class="img-fluid w-100 rounded-4" loading="lazy" width="600" height="400">
            </picture>
            <div class="p-3 bg-light text-start border-top">
              <span class="badge bg-warning text-dark mb-1"><i class="fas fa-arrows-rotate me-1"></i> Ciclo PHVA Continuo</span>
              <p class="text-secondary small mb-0">Metodología aplicada para la transferencia sostenible de capacidades al equipo de obra.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 4. Sección: Evaluación y Madurez de Áreas y Proveedores -->
<section class="py-5 bg-light" id="auditoria">
  <div class="container py-lg-4">
    <div class="row align-items-center gy-5">
      <div class="col-12 col-lg-6" data-aos="fade-right">
        <div class="mb-3">
          <span class="text-primary fw-bold text-uppercase small tracking-wide">
            <i class="fas fa-shield-alt me-1"></i> Diagnóstico & Madurez
          </span>
          <h2 class="h1 fw-bold text-dark mt-2 mb-3">EVALUACIÓN Y MADUREZ DE ÁREAS Y PROVEEDORES</h2>
          <div class="heading-line-sm mb-4" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
        </div>
        <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.7;">
          Asesoramos en la evaluación de la madurez del sistema de gestión de proyectos de la organización y sus proveedores, brindando criterios técnicos para diagnosticar el nivel de sistematización, estandarización y aporte a la cadena de valor, analizando entregables, indicadores y requerimientos técnicos reales.
        </p>

        <div class="row g-3">
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary fs-5">
                  <i class="fas fa-layer-group"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Nivel de Sistematización</h5>
                  <p class="text-secondary small mb-0">Diagnóstico de herramientas y procesos activos en cada unidad operativa y gerencial.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-success bg-opacity-10 text-success fs-5">
                  <i class="fas fa-arrow-trend-up"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Aporte a la Cadena de Valor</h5>
                  <p class="text-secondary small mb-0">Medición del impacto real de cada entregable en los resultados del proyecto y reducción de desperdicios.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-warning bg-opacity-10 text-warning fs-5">
                  <i class="fas fa-gauge-high"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Gestión de Requerimientos & KPIs</h5>
                  <p class="text-secondary small mb-0">Revisión técnica de métricas e indicadores de rendimiento operativo para la toma de decisiones.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6" data-aos="fade-left">
        <div class="position-relative text-center">
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <picture>
              <source srcset="/img/gesPMO3.webp" type="image/webp">
              <img src="/img/gesPMO3.png" alt="Auditoría de áreas, procesos y evaluación de proveedores" class="img-fluid w-100 rounded-4" loading="lazy" width="600" height="400">
            </picture>
            <div class="p-3 bg-white text-start border-top">
              <span class="badge bg-success text-white mb-1"><i class="fas fa-shield-alt me-1"></i> Control de Eficiencia</span>
              <p class="text-secondary small mb-0">Diagnósticos exhaustivos con enfoque en la eficiencia del gasto y cumplimiento contractual.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 5. CTA Final Corporativo PMO -->
<section class="py-5 text-white position-relative" style="background: linear-gradient(135deg, var(--pmo-primary-dark) 0%, var(--pmo-primary) 60%, #003870 100%);">
  <div class="container py-4 text-center">
    <div class="max-w-700 mx-auto" style="max-width: 780px;">
      <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">
        <i class="fas fa-handshake me-1"></i> Asesoría y Consultoría PMO Solutions
      </span>
      <h2 class="h1 fw-extrabold text-white mb-3">
        ¿Desea estructurar y fortalecer la PMO de su organización?
      </h2>
      <p class="text-white-50 fs-5 mb-4">
        Nuestros consultores senior orientan a su equipo en la adopción, estandarización y gobernanza ágil de proyectos de ingeniería y construcción.
      </p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="/contacto" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
          <i class="fas fa-clipboard-check me-2"></i> Solicitar Asesoría
        </a>
        <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20asesor%C3%ADa%20en%20Gobernanza%20y%20Gesti%C3%B3n%20de%20PMO" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light fw-bold px-4 py-3 shadow-sm hvr-grow">
          <i class="fab fa-whatsapp me-2 text-success"></i> Contactar por WhatsApp
        </a>
      </div>
    </div>
  </div>
</section>

