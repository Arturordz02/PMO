<?php
/**
 * PMO SOLUTIONS - Vista de Página: Evaluación del Riesgo del Cumplimiento del Plazo
 * Archivo: private_app/app/Views/pages/advisory/riesgo-del-plazo.php
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
            <i class="fas fa-clock-rotate-left me-2"></i> Metodología Exclusiva • Curva de Liberación®
          </span>
        </div>
        <h1 class="hero-title fw-extrabold mb-3 animate__animated animate__fadeInDown">
          EVALUACIÓN DEL RIESGO DEL CUMPLIMIENTO DEL PLAZO
        </h1>
        <p class="hero-subtitle text-white-50 fs-5 mb-4 animate__animated animate__fadeInUp animate__delay-1s" style="line-height: 1.6;">
          ¿Le suele ocurrir que estando más allá del 90% de avance del proyecto, ese saldo restante demora más de lo previsto? Ese estadío se denomina el <strong>Síndrome del 90%</strong>. En <strong>PMO Solutions</strong> aplicamos una metodología estructurada para evaluar el riesgo de cumplimiento del plazo mediante la <strong>Curva de Liberación®</strong>, método validado en prestigiosos foros internacionales como PMI® Global Congress - Washington D.C., Techsuyo at Stanford University y Lean Construction México.
        </p>
        <div class="d-flex flex-wrap gap-3 pt-2">
          <a href="/contacto" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
            <i class="fas fa-clipboard-check me-2"></i> Solicitar Asesoría de Plazo
          </a>
          <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20asesor%C3%ADa%20en%20Evaluaci%C3%B3n%20del%20Riesgo%20del%20Plazo%20(S%C3%ADndrome%20del%2090%25)" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light fw-bold px-4 py-3 shadow-sm hvr-grow">
            <i class="fab fa-whatsapp me-2 text-success"></i> WhatsApp Especialista
          </a>
        </div>
      </div>

      <div class="col-12 col-lg-4 text-center d-none d-lg-block">
        <div class="p-4 rounded-4 bg-white bg-opacity-10 backdrop-blur border border-white border-opacity-15 shadow-lg">
          <i class="fas fa-chart-line text-warning mb-3" style="font-size: 3.5rem;"></i>
          <h4 class="text-white fw-bold mb-2">Previsibilidad Real</h4>
          <p class="text-white-50 small mb-0">Modelado predictivo matemático para proyectar la fecha exacta de culminación de obra.</p>
        </div>
      </div>
    </div>

    <!-- Badges de Respaldo -->
    <div class="row g-3 mt-4 pt-3 border-top border-white border-opacity-10 text-center">
      <div class="col-12 col-md-4">
        <div class="p-2 rounded-3 bg-white bg-opacity-10 text-white">
          <i class="fas fa-triangle-exclamation text-warning me-2"></i> +90% Zona Crítica de Desviación
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="p-2 rounded-3 bg-white bg-opacity-10 text-white">
          <i class="fas fa-chart-area text-warning me-2"></i> Curva de Liberación®
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="p-2 rounded-3 bg-white bg-opacity-10 text-white">
          <i class="fas fa-university text-warning me-2"></i> Stanford & PMI® Validado
        </div>
      </div>
    </div>
  </div>
</header>

<!-- 2. Sección: ¿Qué es el Síndrome del 90%? -->
<section class="py-5 bg-light" id="que-es-sindrome">
  <div class="container py-lg-4">
    <div class="row align-items-center gy-5">
      <div class="col-12 col-lg-6" data-aos="fade-right">
        <div class="mb-3">
          <span class="text-primary fw-bold text-uppercase small tracking-wide">
            <i class="fas fa-exclamation-triangle me-1"></i> El Desafío Crítico en la Construcción
          </span>
          <h2 class="h1 fw-bold text-dark mt-2 mb-3">¿POR QUÉ EL ÚLTIMO 10% TOMA EL 90% DEL ESFUERZO?</h2>
          <div class="heading-line-sm mb-4" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
        </div>
        <p class="text-secondary mb-3" style="font-size: 1.05rem; line-height: 1.7;">
          En proyectos de ingeniería y construcción, el avance físico reportado suele progresar con aparente normalidad hasta alcanzar aproximadamente el 90%. A partir de ese punto, el saldo restante tiende a demorar de manera desproporcionada.
        </p>
        <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.7;">
          Esto ocurre porque el avance financiero o porcentual tradicional oculta la acumulación de restricciones no liberadas en partidas críticas de acabado, pruebas, comisionamiento y trámites de entrega.
        </p>

        <div class="row g-3">
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-warning">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-warning bg-opacity-10 text-warning fs-5">
                  <i class="fas fa-chart-pie"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Falsa Sensación de Avance</h5>
                  <p class="text-secondary small mb-0">El porcentaje monetario acumulado no refleja el estado real de liberaciones operativas en terreno.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-danger">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-danger bg-opacity-10 text-danger fs-5">
                  <i class="fas fa-filter-circle-xmark"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Concentración de Restricciones</h5>
                  <p class="text-secondary small mb-0">El último 10% acumula interferencias no resueltas de ingeniería, proveedores y subcontratistas.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 p-3 border-start border-4 border-primary">
              <div class="d-flex align-items-start gap-3">
                <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary fs-5">
                  <i class="fas fa-money-bill-trend-up"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-dark">Impacto en Costos y Penalidades</h5>
                  <p class="text-secondary small mb-0">Las extensiones de plazo erosionan el margen operativo por penalidades y mayores gastos generales.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6" data-aos="fade-left">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
          <picture>
            <source srcset="/img/s901.webp" type="image/webp">
            <img src="/img/s901.png" alt="Curva de Liberación y control del plazo en proyectos" class="img-fluid w-100 rounded-4" loading="lazy" width="600" height="400">
          </picture>
          <div class="p-3 bg-white text-start border-top">
            <span class="badge bg-warning text-dark mb-1"><i class="fas fa-chart-area me-1"></i> Curva de Liberación®</span>
            <p class="text-secondary small mb-0">Modelado matemático predictivo que determina con exactitud la fecha real de compleción.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 3. Etapas de Nuestra Metodología (4 Fases Estructuradas) -->
<section class="py-5 bg-white" id="etapas-metodologia">
  <div class="container py-lg-4">
    <div class="text-center max-w-700 mx-auto mb-5" style="max-width: 720px;">
      <span class="text-primary fw-bold text-uppercase small tracking-wide">
        <i class="fas fa-tasks me-1"></i> Fases de Implementación
      </span>
      <h2 class="h1 fw-bold text-dark mt-2 mb-3">ETAPAS DE NUESTRA METODOLOGÍA</h2>
      <div class="heading-line-sm mx-auto mb-3" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
      <p class="text-secondary">Un proceso estructurado, predictivo y fundamentado en ingeniería para optimizar el control y previsibilidad en la fecha de entrega de su proyecto.</p>
    </div>

    <div class="row g-4">
      <!-- Etapa 1 -->
      <div class="col-12 col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-primary">
          <span class="badge bg-primary fs-6 px-3 py-1 mb-3 align-self-start">01</span>
          <h5 class="fw-bold text-dark mb-2">Revisión del Alcance</h5>
          <ul class="list-unstyled text-secondary small mb-0 d-flex flex-column gap-2" style="line-height: 1.5;">
            <li><i class="fas fa-check text-success me-2"></i> Alcance del proyecto y líneas base.</li>
            <li><i class="fas fa-check text-success me-2"></i> Reportes de avance y cronogramas vigentes.</li>
            <li><i class="fas fa-check text-success me-2"></i> Evaluación del estado contractual.</li>
          </ul>
        </div>
      </div>

      <!-- Etapa 2 -->
      <div class="col-12 col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-warning">
          <span class="badge bg-warning text-dark fs-6 px-3 py-1 mb-3 align-self-start">02</span>
          <h5 class="fw-bold text-dark mb-2">Matriz de Restricciones</h5>
          <ul class="list-unstyled text-secondary small mb-0 d-flex flex-column gap-2" style="line-height: 1.5;">
            <li><i class="fas fa-check text-success me-2"></i> Aplicación en partidas críticas.</li>
            <li><i class="fas fa-check text-success me-2"></i> Registro y tipificación operativa.</li>
            <li><i class="fas fa-check text-success me-2"></i> Jerarquización por impacto en ruta crítica.</li>
          </ul>
        </div>
      </div>

      <!-- Etapa 3 -->
      <div class="col-12 col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-danger">
          <span class="badge bg-danger text-white fs-6 px-3 py-1 mb-3 align-self-start">03</span>
          <h5 class="fw-bold text-dark mb-2">Curvas de Liberación®</h5>
          <ul class="list-unstyled text-secondary small mb-0 d-flex flex-column gap-2" style="line-height: 1.5;">
            <li><i class="fas fa-check text-success me-2"></i> Ordenamiento y depuración de datos.</li>
            <li><i class="fas fa-check text-success me-2"></i> Curvas de Liberación General.</li>
            <li><i class="fas fa-check text-success me-2"></i> Modelado por partidas individuales.</li>
          </ul>
        </div>
      </div>

      <!-- Etapa 4 -->
      <div class="col-12 col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-success">
          <span class="badge bg-success text-white fs-6 px-3 py-1 mb-3 align-self-start">04</span>
          <h5 class="fw-bold text-dark mb-2">Informe Técnico</h5>
          <ul class="list-unstyled text-secondary small mb-0 d-flex flex-column gap-2" style="line-height: 1.5;">
            <li><i class="fas fa-check text-success me-2"></i> Hallazgos relevantes de terreno.</li>
            <li><i class="fas fa-check text-success me-2"></i> Proyección matemática de fecha final.</li>
            <li><i class="fas fa-check text-success me-2"></i> Plan de choque y recomendaciones ejecutivas.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 4. Respaldo y Difusión Internacional -->
<section class="py-5 bg-light" id="presentaciones">
  <div class="container py-lg-4">
    <div class="text-center max-w-700 mx-auto mb-5" style="max-width: 720px;">
      <span class="text-primary fw-bold text-uppercase small tracking-wide">
        <i class="fas fa-globe-americas me-1"></i> Respaldo y Difusión Internacional
      </span>
      <h2 class="h1 fw-bold text-dark mt-2 mb-3">ALGUNOS LUGARES DONDE SE PRESENTÓ LA METODOLOGÍA</h2>
      <div class="heading-line-sm mx-auto mb-3" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
      <p class="text-secondary">Nuestra metodología de la Curva de Liberación® ha sido validada y expuesta en los foros más prestigiosos de gestión e ingeniería.</p>
    </div>

    <div class="row g-4">
      <!-- PMI Washington -->
      <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="100">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
          <img src="/img/10-pmi_orig.jpg" alt="PMI Global Congress, Washington D.C." class="card-img-top" loading="lazy" style="height: 220px; object-fit: cover;">
          <div class="card-body p-4">
            <span class="badge bg-primary text-white mb-2"><i class="fas fa-map-marker-alt me-1"></i> Washington D.C., USA</span>
            <h5 class="fw-bold text-dark mb-2">PMI Global Congress</h5>
            <p class="text-secondary small mb-0">Presentación sobre metodologías predictivas para el control de plazos en proyectos de infraestructura compleja.</p>
          </div>
        </div>
      </div>

      <!-- Stanford University -->
      <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="200">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
          <div class="position-relative bg-dark text-center" style="height: 220px;">
            <img src="https://img.youtube.com/vi/Pxe6RP_J9Ek/hqdefault.jpg" alt="Video Techsuyo Stanford" class="w-100 h-100" style="object-fit: cover; opacity: 0.7;">
            <a href="https://www.youtube.com/watch?v=Pxe6RP_J9Ek" target="_blank" rel="noopener noreferrer" class="btn btn-danger position-absolute top-50 start-50 translate-middle rounded-circle shadow-lg" style="width: 56px; height: 56px; display: inline-flex; align-items: center; justify-content: center;">
              <i class="fas fa-play text-white"></i>
            </a>
          </div>
          <div class="card-body p-4">
            <span class="badge bg-danger text-white mb-2"><i class="fab fa-youtube me-1"></i> Stanford University, CA</span>
            <h5 class="fw-bold text-dark mb-2">Techsuyo at Stanford</h5>
            <p class="text-secondary small mb-3">Exposición magistral sobre la prevención del Síndrome del 90% mediante la Curva de Liberación®.</p>
            <a href="https://www.youtube.com/watch?v=Pxe6RP_J9Ek" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm fw-bold w-100">
              <i class="fas fa-arrow-up-right-from-square me-1"></i> Ver Conferencia Completa
            </a>
          </div>
        </div>
      </div>

      <!-- Lean Construction México -->
      <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="300">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
          <img src="/img/19-lnx_orig.jpeg" alt="Congreso Lean Construction México D.F." class="card-img-top" loading="lazy" style="height: 220px; object-fit: cover;">
          <div class="card-body p-4">
            <span class="badge bg-warning text-dark mb-2"><i class="fas fa-map-marker-alt me-1"></i> Ciudad de México</span>
            <h5 class="fw-bold text-dark mb-2">Congreso Lean Construction</h5>
            <p class="text-secondary small mb-0">Disertación técnica sobre la integración de la metodología Last Planner con la gestión predictiva de partidas críticas.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 5. Frase Destacada -->
<section class="py-5 bg-white text-center">
  <div class="container py-3">
    <div class="max-w-700 mx-auto" style="max-width: 780px;">
      <i class="fas fa-quote-left text-warning mb-3" style="font-size: 2.5rem;"></i>
      <blockquote class="blockquote fs-4 fw-semibold text-dark mb-3">
        “El 90% del proyecto suele ejecutarse en el 90% del tiempo; el 10% restante toma otro 90% si no se evalúa con la Curva de Liberación®.”
      </blockquote>
      <div class="heading-line-sm mx-auto mb-2" style="width: 50px; height: 3px; background: var(--pmo-accent);"></div>
      <span class="text-muted small fw-bold text-uppercase">PMO Solutions • Metodología de Prevención de Plazo</span>
    </div>
  </div>
</section>

<!-- 6. CTA Final -->
<section class="py-5 text-white position-relative" style="background: linear-gradient(135deg, var(--pmo-primary-dark) 0%, var(--pmo-primary) 60%, #003870 100%);">
  <div class="container py-4 text-center">
    <div class="max-w-700 mx-auto" style="max-width: 780px;">
      <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">
        <i class="fas fa-clock me-1"></i> Previsibilidad de Plazos
      </span>
      <h2 class="h1 fw-extrabold text-white mb-3">
        ¿Su proyecto está próximo al 90% y requiere asegurar su fecha de entrega?
      </h2>
      <p class="text-white-50 fs-5 mb-4">
        Aplique la Curva de Liberación® y prevenga sobrecostos por penalidades y extensiones de plazo contractual.
      </p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="/contacto" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
          <i class="fas fa-clipboard-check me-2"></i> Solicitar Asesoría de Plazo
        </a>
        <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20asesor%C3%ADa%20en%20Evaluaci%C3%B3n%20del%20Riesgo%20del%20Plazo%20(S%C3%ADndrome%20del%2090%25)" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light fw-bold px-4 py-3 shadow-sm hvr-grow">
          <i class="fab fa-whatsapp me-2 text-success"></i> WhatsApp Especialista
        </a>
      </div>
    </div>
  </div>
</section>

