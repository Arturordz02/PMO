<?php
/**
 * PMO SOLUTIONS - Vista de Página: Gestión del Riesgo
 * Archivo: private_app/app/Views/pages/advisory/gestion-de-riesgos.php
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
            <i class="fas fa-shield-alt me-2"></i> Asesoría Especializada en Riesgos
          </span>
        </div>
        <h1 class="hero-title fw-extrabold mb-3 animate__animated animate__fadeInDown">
          GESTIÓN DEL RIESGO
        </h1>
        <p class="hero-subtitle text-white-50 fs-5 mb-4 animate__animated animate__fadeInUp animate__delay-1s" style="line-height: 1.6;">
          <strong>Identificación, análisis, respuesta y monitoreo estratégico</strong> de riesgos en proyectos de ingeniería y construcción para maximizar la predictibilidad, proteger el margen operativo y asegurar el cumplimiento de plazos y costos.
        </p>
        <div class="d-flex flex-wrap gap-3 pt-2">
          <a href="/contacto" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
            <i class="fas fa-clipboard-check me-2"></i> Solicitar Asesoría
          </a>
          <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20asesor%C3%ADa%20en%20Gesti%C3%B3n%20de%20Riesgos" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light fw-bold px-4 py-3 shadow-sm hvr-grow">
            <i class="fab fa-whatsapp me-2 text-success"></i> Contactar Especialista
          </a>
        </div>
      </div>

      <div class="col-12 col-lg-4 text-center d-none d-lg-block">
        <div class="p-4 rounded-4 bg-white bg-opacity-10 backdrop-blur border border-white border-opacity-15 shadow-lg">
          <i class="fas fa-shield-halved text-warning mb-3" style="font-size: 3.5rem;"></i>
          <h4 class="text-white fw-bold mb-2">Riesgo Técnico Controlado</h4>
          <p class="text-white-50 small mb-0">Mitigación proactiva de contingencias en obra antes de que impacten la ruta crítica.</p>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- 2. Enfoque: Gestión de Riesgos Técnicos en Operaciones -->
<section class="py-5 bg-light" id="introduccion-riesgos">
  <div class="container py-lg-4">
    <div class="row align-items-center gy-5">
      <div class="col-12 col-lg-6" data-aos="fade-right">
        <div class="mb-3">
          <span class="text-primary fw-bold text-uppercase small tracking-wide">
            <i class="fas fa-hard-hat me-1"></i> Enfoque Operativo & Estratégico
          </span>
          <h2 class="h1 fw-bold text-dark mt-2 mb-3">GESTIÓN DE RIESGOS TÉCNICOS EN OPERACIONES</h2>
          <div class="heading-line-sm mb-4" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
        </div>
        <p class="text-secondary mb-3" style="font-size: 1.05rem; line-height: 1.7;">
          La gestión de riesgos abarca no solo seguridad ocupacional y finanzas, sino principalmente el <strong>Riesgo Técnico de las Operaciones</strong>.
        </p>
        <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.7;">
          En el sector de la ingeniería y construcción, las desviaciones más severas suelen originarse en la incertidumbre técnica: constructabilidad no evaluada, variaciones geotécnicas imprevistas, incompatibilidad en modelos de ingeniería, problemas en la cadena de suministros y cuellos de botella en la productividad operativa.
        </p>

        <div class="row g-3">
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-primary">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary fs-5">
                  <i class="fas fa-helmet-safety"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Más Allá de Seguridad & Finanzas</h5>
                  <p class="text-secondary small mb-0">Integramos los riesgos tradicionales con la realidad técnica del frente de trabajo.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-warning">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-warning bg-opacity-10 text-warning fs-5">
                  <i class="fas fa-gears"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Foco en Riesgos Técnicos Operativos</h5>
                  <p class="text-secondary small mb-0">Análisis riguroso de constructabilidad, rendimientos y contingencias de diseño.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-success">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-success bg-opacity-10 text-success fs-5">
                  <i class="fas fa-chart-line"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Protección de Plazo, Costo y Calidad</h5>
                  <p class="text-secondary small mb-0">Decisiones anticipadas que evitan sobrecostos y controversias contractuales.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6" data-aos="fade-left">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
          <picture>
            <source srcset="/img/gesRi1.webp" type="image/webp">
            <img src="/img/gesRi1.png" alt="Gestión de riesgos técnicos en proyectos de construcción" class="img-fluid w-100 rounded-4" loading="lazy" width="600" height="400">
          </picture>
          <div class="p-3 bg-white text-start border-top">
            <span class="badge bg-primary text-white mb-1"><i class="fas fa-shield-alt me-1"></i> Control Proactivo</span>
            <p class="text-secondary small mb-0">Mitigación proactiva de desviaciones en proyectos complejos de ingeniería y construcción.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 3. Ciclo de 6 Pasos: Proceso de Gestión de Riesgos -->
<section class="py-5 bg-white" id="proceso-riesgos">
  <div class="container py-lg-4">
    <div class="text-center max-w-700 mx-auto mb-5" style="max-width: 720px;">
      <span class="text-primary fw-bold text-uppercase small tracking-wide">
        <i class="fas fa-sync-alt me-1"></i> Ciclo Integral de Gestión
      </span>
      <h2 class="h1 fw-bold text-dark mt-2 mb-3">NUESTRO PROCESO DE GESTIÓN DE RIESGOS</h2>
      <div class="heading-line-sm mx-auto mb-3" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
      <p class="text-secondary">Metodología estructurada de 6 etapas continuas para asegurar la previsibilidad y éxito del proyecto.</p>
    </div>

    <div class="row g-4">
      <!-- Paso 1 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-primary">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="badge bg-primary fs-6 px-3 py-1">01</span>
            <i class="fas fa-clipboard-list text-primary fs-3"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Planificación de Riesgos</h5>
          <p class="text-secondary small mb-0">Revisión del proyecto, documentación contractual, contexto y desarrollo del Plan de Gestión de Riesgos.</p>
        </div>
      </div>

      <!-- Paso 2 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-warning">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="badge bg-warning text-dark fs-6 px-3 py-1">02</span>
            <i class="fas fa-search-plus text-warning fs-3"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Identificación de Riesgos</h5>
          <p class="text-secondary small mb-0">Levantamiento de riesgos individuales, constructabilidad y fuentes generales de incertidumbre.</p>
        </div>
      </div>

      <!-- Paso 3 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-danger">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="badge bg-danger fs-6 px-3 py-1">03</span>
            <i class="fas fa-chart-column text-danger fs-3"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Análisis de Riesgos</h5>
          <p class="text-secondary small mb-0">Evaluación cualitativa y cuantitativa por probabilidad, impacto en plazo/costo y orden de prioridad.</p>
        </div>
      </div>

      <!-- Paso 4 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="400">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-info">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="badge bg-info text-dark fs-6 px-3 py-1">04</span>
            <i class="fas fa-shield-alt text-info fs-3"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Planificación de Respuestas</h5>
          <p class="text-secondary small mb-0">Diseño de estrategias de mitigación, transferencia, contingencia y acciones preventivas concretas.</p>
        </div>
      </div>

      <!-- Paso 5 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="500">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-secondary">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="badge bg-secondary fs-6 px-3 py-1">05</span>
            <i class="fas fa-gears text-secondary fs-3"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Implementación de Respuestas</h5>
          <p class="text-secondary small mb-0">Asignación de responsables y ejecución efectiva de las acciones acordadas frente a cada riesgo.</p>
        </div>
      </div>

      <!-- Paso 6 -->
      <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="600">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-success">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="badge bg-success fs-6 px-3 py-1">06</span>
            <i class="fas fa-eye text-success fs-3"></i>
          </div>
          <h5 class="fw-bold text-dark mb-2">Monitoreo Continuo</h5>
          <p class="text-secondary small mb-0">Seguimiento sistemático mediante matrices vivas, curvas de tendencia y dashboards ejecutivos.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 4. Matriz de Probabilidad e Impacto 5x5 -->
<section class="py-5 bg-light" id="matriz-riesgos">
  <div class="container py-lg-4">
    <div class="row align-items-center gy-5">
      <div class="col-12 col-lg-6" data-aos="fade-right">
        <div class="mb-3">
          <span class="text-primary fw-bold text-uppercase small tracking-wide">
            <i class="fas fa-table-cells me-1"></i> Herramienta Cuantitativa & Cualitativa
          </span>
          <h2 class="h1 fw-bold text-dark mt-2 mb-3">MATRIZ DE PROBABILIDAD E IMPACTO</h2>
          <div class="heading-line-sm mb-4" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
        </div>
        <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.7;">
          La Matriz de Riesgos categoriza cada evento identificado según su probabilidad de ocurrencia e impacto potencial en los objetivos clave (alcance, costo, plazo y calidad). Permite priorizar la atención en los riesgos de severidad Alta (zona roja) y Media (zona naranja/amarilla), asignando recursos y planes de contingencia eficientes.
        </p>

        <div class="card border-0 shadow-sm rounded-4 p-4">
          <h5 class="fw-bold text-dark mb-3"><i class="fas fa-layer-group text-primary me-2"></i> Criterios de Severidad:</h5>
          <div class="d-flex flex-column gap-3">
            <div class="d-flex align-items-start gap-3">
              <span class="badge bg-danger rounded-circle p-2 mt-1" style="width:14px; height:14px;"> </span>
              <div>
                <strong class="text-dark">Riesgo Alto (Zona Roja):</strong>
                <p class="text-secondary small mb-0">Acción y mitigación inmediata. Requiere plan de respuesta prioritario y seguimiento semanal.</p>
              </div>
            </div>
            <div class="d-flex align-items-start gap-3">
              <span class="badge bg-warning rounded-circle p-2 mt-1" style="width:14px; height:14px;"> </span>
              <div>
                <strong class="text-dark">Riesgo Medio (Zona Naranja / Amarilla):</strong>
                <p class="text-secondary small mb-0">Monitoreo activo y definición de contingencias operativas preparadas para actuar.</p>
              </div>
            </div>
            <div class="d-flex align-items-start gap-3">
              <span class="badge bg-success rounded-circle p-2 mt-1" style="width:14px; height:14px;"> </span>
              <div>
                <strong class="text-dark">Riesgo Bajo (Zona Verde):</strong>
                <p class="text-secondary small mb-0">Aceptación informada y seguimiento periódico en el registro vivo de riesgos.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6" data-aos="fade-left">
        <!-- Visual Matriz 5x5 -->
        <div class="card border-0 shadow-lg rounded-4 p-4 bg-white text-center">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-table-cells me-2 text-primary"></i> Matriz 5×5 (PMI / ISO 31000)</h5>
            <span class="badge bg-primary text-white">Severidad P × I</span>
          </div>
          
          <div class="table-responsive">
            <table class="table table-bordered text-center align-middle mb-2 small fw-bold" style="font-size: 0.82rem;">
              <tbody>
                <tr>
                  <th class="bg-light text-muted" style="width: 75px;">5 (M.A.)</th>
                  <td class="bg-warning text-dark">5</td>
                  <td class="text-white" style="background:#f97316;">10</td>
                  <td class="bg-danger text-white">15</td>
                  <td class="bg-danger text-white">20</td>
                  <td class="bg-danger text-white">25</td>
                </tr>
                <tr>
                  <th class="bg-light text-muted">4 (A)</th>
                  <td class="bg-warning text-dark">4</td>
                  <td class="text-white" style="background:#f97316;">8</td>
                  <td class="text-white" style="background:#f97316;">12</td>
                  <td class="bg-danger text-white">16</td>
                  <td class="bg-danger text-white">20</td>
                </tr>
                <tr>
                  <th class="bg-light text-muted">3 (M)</th>
                  <td class="bg-success text-white">3</td>
                  <td class="bg-warning text-dark">6</td>
                  <td class="text-white" style="background:#f97316;">9</td>
                  <td class="text-white" style="background:#f97316;">12</td>
                  <td class="bg-danger text-white">15</td>
                </tr>
                <tr>
                  <th class="bg-light text-muted">2 (B)</th>
                  <td class="bg-success text-white">2</td>
                  <td class="bg-success text-white">4</td>
                  <td class="bg-warning text-dark">6</td>
                  <td class="text-white" style="background:#f97316;">8</td>
                  <td class="text-white" style="background:#f97316;">10</td>
                </tr>
                <tr>
                  <th class="bg-light text-muted">1 (M.B.)</th>
                  <td class="bg-success text-white">1</td>
                  <td class="bg-success text-white">2</td>
                  <td class="bg-success text-white">3</td>
                  <td class="bg-warning text-dark">4</td>
                  <td class="bg-warning text-dark">5</td>
                </tr>
                <tr class="bg-light">
                  <th class="text-muted">P \ I</th>
                  <th class="text-muted">1 (M.B.)</th>
                  <th class="text-muted">2 (B)</th>
                  <th class="text-muted">3 (M)</th>
                  <th class="text-muted">4 (A)</th>
                  <th class="text-muted">5 (M.A.)</th>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="text-muted small mt-2 d-flex justify-content-between">
            <span>↑ Probabilidad</span>
            <span>Impacto →</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 5. Estándares Internacionales (PMI & ISO 31000) -->
<section class="py-5 bg-white" id="estandares">
  <div class="container py-lg-4">
    <div class="text-center max-w-700 mx-auto mb-5" style="max-width: 720px;">
      <span class="text-primary fw-bold text-uppercase small tracking-wide">
        <i class="fas fa-award me-1"></i> Marcos Normativos Internacionales
      </span>
      <h2 class="h1 fw-bold text-dark mt-2 mb-3">ESTÁNDARES Y MARCOS DE TRABAJO</h2>
      <div class="heading-line-sm mx-auto mb-3" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
      <p class="text-secondary">Metodologías fundamentadas en las mejores prácticas de los principales organismos globales líderes en gobernanza.</p>
    </div>

    <div class="row g-4">
      <div class="col-12 col-md-6" data-aos="fade-up" data-aos-delay="100">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
          <img src="/img/gestionriesgos.png" alt="PMI Risk Management" class="card-img-top" loading="lazy" style="height: 220px; object-fit: cover;">
          <div class="card-body p-4">
            <span class="badge bg-primary text-white mb-2">Project Management Institute</span>
            <h4 class="fw-bold text-dark mb-2">PMI® – Gestión de Riesgos</h4>
            <p class="text-secondary small mb-0" style="line-height: 1.6;">
              Asesoramos y estructuramos bajo los lineamientos del <em>Practice Standard for Project Risk Management</em> del PMI, orientando el desarrollo de planes de gestión cuantitativos y cualitativos para cada ciclo de vida del proyecto.
            </p>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6" data-aos="fade-up" data-aos-delay="200">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
          <picture>
            <source srcset="/img/ISO1.webp" type="image/webp">
            <img src="/img/ISO1.png" alt="ISO 31000 Risk Management" class="card-img-top" loading="lazy" style="height: 220px; object-fit: cover;">
          </picture>
          <div class="card-body p-4">
            <span class="badge bg-success text-white mb-2">Normativa Internacional ISO</span>
            <h4 class="fw-bold text-dark mb-2">ISO 31000 – Risk Management</h4>
            <p class="text-secondary small mb-0" style="line-height: 1.6;">
              Asesoría técnica y capacitación aplicada para orientar la adopción de las directrices ISO 31000, fortaleciendo el marco de gobernanza, la gestión de contingencias y la toma de decisiones informada.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 6. Cita Destacada Corporativa -->
<section class="py-5 bg-light text-center">
  <div class="container py-3">
    <div class="max-w-700 mx-auto" style="max-width: 760px;">
      <i class="fas fa-quote-left text-warning mb-3" style="font-size: 2.5rem;"></i>
      <blockquote class="blockquote fs-4 fw-semibold text-dark mb-3">
        “El problema de ayer es el riesgo de hoy, y el problema de hoy es el riesgo del mañana.”
      </blockquote>
      <div class="heading-line-sm mx-auto mb-2" style="width: 50px; height: 3px; background: var(--pmo-accent);"></div>
      <span class="text-muted small fw-bold text-uppercase">PMO Solutions • Principio de Gestión Proactiva</span>
    </div>
  </div>
</section>

<!-- 7. CTA Final -->
<section class="py-5 text-white position-relative" style="background: linear-gradient(135deg, var(--pmo-primary-dark) 0%, var(--pmo-primary) 60%, #003870 100%);">
  <div class="container py-4 text-center">
    <div class="max-w-700 mx-auto" style="max-width: 780px;">
      <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">
        <i class="fas fa-shield-alt me-1"></i> Prevención & Control Técnico
      </span>
      <h2 class="h1 fw-extrabold text-white mb-3">
        Gestiona los riesgos antes de que se conviertan en problemas
      </h2>
      <p class="text-white-50 fs-5 mb-4">
        Anticípate a las contingencias operativas y asegura la rentabilidad y el plazo de tu proyecto con nuestra asesoría especializada.
      </p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="/contacto" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
          <i class="fas fa-clipboard-check me-2"></i> Solicitar Asesoría
        </a>
        <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20asesor%C3%ADa%20en%20Gesti%C3%B3n%20de%20Riesgos" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light fw-bold px-4 py-3 shadow-sm hvr-grow">
          <i class="fab fa-whatsapp me-2 text-success"></i> Contactar por WhatsApp
        </a>
      </div>
    </div>
  </div>
</section>

