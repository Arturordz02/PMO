<?php
/**
 * PMO SOLUTIONS - Vista de Página: Gestión de la Calidad en Proyectos
 * Archivo: private_app/app/Views/pages/advisory/gestion-de-la-calidad.php
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
            <i class="fas fa-check-double me-2"></i> Estandarización & Control Operativo
          </span>
        </div>
        <h1 class="hero-title fw-extrabold mb-3 animate__animated animate__fadeInDown">
          GESTIÓN DE LA CALIDAD
        </h1>
        <p class="hero-subtitle text-white-50 fs-5 mb-4 animate__animated animate__fadeInUp animate__delay-1s" style="line-height: 1.6;">
          Estandarización, control en obra, auditoría técnica y mejora continua para obtener resultados más consistentes, trazables y libres de retrabajos en proyectos de ingeniería y construcción.
        </p>
        <div class="d-flex flex-wrap gap-3 pt-2">
          <a href="/contacto" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
            <i class="fas fa-clipboard-check me-2"></i> Solicitar Asesoría
          </a>
          <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20asesor%C3%ADa%20en%20Gesti%C3%B3n%20de%20la%20Calidad" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light fw-bold px-4 py-3 shadow-sm hvr-grow">
            <i class="fab fa-whatsapp me-2 text-success"></i> Contactar Especialista
          </a>
        </div>
      </div>

      <div class="col-12 col-lg-4 text-center d-none d-lg-block">
        <div class="p-4 rounded-4 bg-white bg-opacity-10 backdrop-blur border border-white border-opacity-15 shadow-lg">
          <i class="fas fa-certificate text-warning mb-3" style="font-size: 3.5rem;"></i>
          <h4 class="text-white fw-bold mb-2">Cero Retrabajos</h4>
          <p class="text-white-50 small mb-0">Estandarización práctica orientada a la recepción contractual sin observaciones en el dossier.</p>
        </div>
      </div>
    </div>

    <!-- Grid de 3 Pilares en Hero -->
    <div class="row g-3 mt-4 pt-3 border-top border-white border-opacity-10">
      <div class="col-12 col-md-4">
        <div class="p-3 rounded-3 bg-white bg-opacity-10 text-white h-100 border border-white border-opacity-10">
          <span class="badge bg-primary text-white mb-2"><i class="fas fa-file-circle-check me-1"></i> Estandarización</span>
          <p class="small mb-0 text-white-50">Manuales, planes PIE y formatos de control de utilidad operativa real.</p>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="p-3 rounded-3 bg-white bg-opacity-10 text-white h-100 border border-white border-opacity-10">
          <span class="badge bg-warning text-dark mb-2"><i class="fas fa-clipboard-check me-1"></i> Auditoría & Trazabilidad</span>
          <p class="small mb-0 text-white-50">Pautas de liberación, cierre de no conformidades y estructuración del dossier.</p>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="p-3 rounded-3 bg-white bg-opacity-10 text-white h-100 border border-white border-opacity-10">
          <span class="badge bg-success text-white mb-2"><i class="fas fa-rotate-right me-1"></i> Mejora Continua</span>
          <p class="small mb-0 text-white-50">Análisis de causa raíz en campo y gestión estadística de defectología en postventa.</p>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- 2. Reducción de la Variabilidad & Documentación Útil -->
<section class="py-5 bg-light" id="reduccion-variabilidad">
  <div class="container py-lg-4">
    <div class="row align-items-center gy-5">
      <div class="col-12 col-lg-6" data-aos="fade-right">
        <div class="mb-3">
          <span class="text-primary fw-bold text-uppercase small tracking-wide">
            <i class="fas fa-sliders-h me-1"></i> Estandarización Práctica en Proyectos
          </span>
          <h2 class="h1 fw-bold text-dark mt-2 mb-3">REDUCCIÓN DE LA VARIABILIDAD DE RESULTADOS</h2>
          <div class="heading-line-sm mb-4" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
        </div>
        <p class="text-secondary mb-3" style="font-size: 1.05rem; line-height: 1.7;">
          En PMO Solutions brindamos asesoría técnica y capacitación aplicada para orientar la estandarización de procesos constructivos y de ingeniería, fortaleciendo el desempeño en obra.
        </p>
        <p class="text-secondary mb-3" style="font-size: 1.05rem; line-height: 1.7;">
          Nuestro enfoque se centra en dotar a los equipos de herramientas de aseguramiento y control que sean verdaderamente prácticas, eliminando la sobrecarga burocrática y promoviendo que cada entregable cumpla con las especificaciones técnicas desde la primera vez.
        </p>

        <!-- Bloque Destacado Obligatorio -->
        <div class="p-3 rounded-3 bg-white border-start border-4 border-warning shadow-sm mb-3">
          <div class="d-flex align-items-center gap-2 text-dark fw-bold">
            <i class="fas fa-lightbulb text-warning fs-5"></i>
            <span>No es necesario buscar una certificación ISO 9001 para comenzar a mejorar la gestión de la calidad en tus proyectos.</span>
          </div>
        </div>

        <p class="text-secondary small mb-0">
          Ayudamos a tu organización a encontrar el equilibrio óptimo entre el rigor documental y la agilidad de ejecución en campo, asegurando la trazabilidad total requerida por clientes y supervisores.
        </p>
      </div>

      <div class="col-12 col-lg-6" data-aos="fade-left">
        <!-- Grid 2x2 Documentación Útil -->
        <div class="row g-3">
          <div class="col-12 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
              <div class="p-3 rounded-3 bg-primary bg-opacity-10 text-primary fs-4 mb-3 align-self-start">
                <i class="fas fa-book-bookmark"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">Manuales de Calidad</h5>
              <p class="text-secondary small mb-0">Políticas, alcance y directrices claras de gestión adaptadas a la escala y tipo de obra, sin exceso de teoría.</p>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
              <div class="p-3 rounded-3 bg-warning bg-opacity-10 text-warning fs-4 mb-3 align-self-start">
                <i class="fas fa-diagram-project"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">Planes de Calidad</h5>
              <p class="text-secondary small mb-0">Protocolos de Inspección y Ensayo (PIE) y matrices de responsabilidades por especialidad constructiva.</p>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
              <div class="p-3 rounded-3 bg-success bg-opacity-10 text-success fs-4 mb-3 align-self-start">
                <i class="fas fa-list-check"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">Formatos de Control</h5>
              <p class="text-secondary small mb-0">Listas de chequeo y protocolos de liberación ágiles, fáciles de llenar y verificar en campo por la supervisión.</p>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
              <div class="p-3 rounded-3 bg-info bg-opacity-10 text-info fs-4 mb-3 align-self-start">
                <i class="fas fa-sliders"></i>
              </div>
              <h5 class="fw-bold text-dark mb-2">Herramientas a Medida</h5>
              <p class="text-secondary small mb-0">Dashboards dinámicos para el seguimiento en tiempo real de No Conformidades (RNC) y dossier de calidad.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 3. Asesoría en Control en Campo & Trazabilidad -->
<section class="py-5 bg-white" id="control-campo">
  <div class="container py-lg-4">
    <div class="row align-items-center gy-5 flex-lg-row-reverse">
      <div class="col-12 col-lg-6" data-aos="fade-left">
        <div class="mb-3">
          <span class="text-primary fw-bold text-uppercase small tracking-wide">
            <i class="fas fa-shield-alt me-1"></i> Control en Campo & Trazabilidad
          </span>
          <h2 class="h1 fw-bold text-dark mt-2 mb-3">ASESORÍA EN GESTIÓN DE LA CALIDAD</h2>
          <div class="heading-line-sm mb-4" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
        </div>
        <p class="text-secondary mb-3" style="font-size: 1.05rem; line-height: 1.7;">
          Brindamos asesoría especializada para orientar a los equipos en la implementación de protocolos de calidad, pautas de auditoría interna y verificación documental, asegurando la trazabilidad de materiales, ensayos y liberación de frentes de trabajo.
        </p>
        <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.7;">
          Orientamos en el análisis de causas de las no conformidades recurrentes y facilitamos herramientas para evaluar el grado de cumplimiento de los protocolos contractuales, previniendo contingencias durante la recepción de la obra.
        </p>

        <!-- Banner de Alerta Calidad -->
        <div class="p-3 rounded-4 bg-danger bg-opacity-10 border border-danger border-opacity-25 mb-4">
          <div class="d-flex align-items-start gap-3">
            <i class="fas fa-exclamation-triangle text-danger fs-4 mt-1"></i>
            <div>
              <strong class="text-danger">Una mala gestión de la calidad puede atrasar la recepción formal de tu proyecto.</strong>
              <p class="text-secondary small mb-0 mt-1">
                Las observaciones no levantadas a tiempo y los vacíos en el dossier de calidad impiden la firma de actas de recepción, demorando valorizaciones finales y liquidaciones contractuales.
              </p>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-12 col-sm-6">
            <div class="d-flex align-items-center gap-2 text-dark fw-semibold small">
              <i class="fas fa-check-circle text-success fs-5"></i>
              <span>Criterios de inspección in situ y tolerancias.</span>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="d-flex align-items-center gap-2 text-dark fw-semibold small">
              <i class="fas fa-check-circle text-success fs-5"></i>
              <span>Estructuración progresiva del dossier final.</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6" data-aos="fade-right">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
          <picture>
            <source srcset="/img/gesCa1.webp" type="image/webp">
            <img src="/img/gesCa1.png" alt="Inspección de seguridad y control de calidad en obra" class="img-fluid w-100 rounded-4" loading="lazy" width="600" height="400">
          </picture>
          <div class="p-3 bg-light text-start border-top">
            <span class="badge bg-success text-white mb-1"><i class="fas fa-clipboard-check me-1"></i> Protocolos de Calidad</span>
            <p class="text-secondary small mb-0">Orientación técnica en liberación de armaduras, concreto, instalaciones y pruebas de estanqueidad.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 4. Mejora Continua y Causa Raíz -->
<section class="py-5 bg-light" id="mejora-continua">
  <div class="container py-lg-4">
    <div class="row align-items-center gy-5">
      <div class="col-12 col-lg-6" data-aos="fade-right">
        <div class="mb-3">
          <span class="text-primary fw-bold text-uppercase small tracking-wide">
            <i class="fas fa-sync me-1"></i> Ciclo de Aprendizaje Operativo
          </span>
          <h2 class="h1 fw-bold text-dark mt-2 mb-3">MEJORA CONTINUA Y CAUSA RAÍZ</h2>
          <div class="heading-line-sm mb-4" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
        </div>
        <p class="text-secondary mb-3" style="font-size: 1.05rem; line-height: 1.7;">
          Corregir el síntoma no evita que el error se repita. Promovemos metodologías estructuradas de análisis de causa raíz (Diagrama de Ishikawa, 5 Porqués) para identificar el origen sistémico de los defectos constructivos.
        </p>

        <!-- Cadena de Flujo Causa Raíz -->
        <div class="d-flex flex-column gap-2 my-4">
          <div class="p-2 px-3 rounded-3 bg-white border border-danger border-opacity-50 text-danger fw-bold small d-flex align-items-center justify-content-between shadow-sm">
            <span><i class="fas fa-circle-exclamation me-2"></i> 1. Problema / No Conformidad</span>
            <i class="fas fa-arrow-down text-danger"></i>
          </div>
          <div class="p-2 px-3 rounded-3 bg-white border text-primary fw-bold small d-flex align-items-center justify-content-between shadow-sm">
            <span><i class="fas fa-magnifying-glass-chart me-2"></i> 2. Análisis de Causa Raíz (Ishikawa)</span>
            <i class="fas fa-arrow-down text-primary"></i>
          </div>
          <div class="p-2 px-3 rounded-3 bg-white border text-warning text-dark fw-bold small d-flex align-items-center justify-content-between shadow-sm">
            <span><i class="fas fa-wrench me-2"></i> 3. Acción Correctiva Inmediata</span>
            <i class="fas fa-arrow-down text-warning"></i>
          </div>
          <div class="p-2 px-3 rounded-3 bg-white border text-info text-dark fw-bold small d-flex align-items-center justify-content-between shadow-sm">
            <span><i class="fas fa-lightbulb me-2"></i> 4. Lección Aprendida Documentada</span>
            <i class="fas fa-arrow-down text-info"></i>
          </div>
          <div class="p-2 px-3 rounded-3 bg-success text-white fw-bold small d-flex align-items-center justify-content-between shadow-sm">
            <span><i class="fas fa-arrow-trend-up me-2"></i> 5. Mejora Sostenible en Obra</span>
            <i class="fas fa-check-circle"></i>
          </div>
        </div>

        <p class="text-secondary small mb-0">
          Al transformar cada no conformidad en una lección aprendida documentada, blindamos los siguientes frentes de trabajo y transferimos el conocimiento a toda la organización para reducir costos por retrabajos.
        </p>
      </div>

      <div class="col-12 col-lg-6" data-aos="fade-left">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
          <picture>
            <source srcset="/img/gesCa2.webp" type="image/webp">
            <img src="/img/gesCa2.png" alt="Manual de procesos y estandarización de calidad" class="img-fluid w-100 rounded-4" loading="lazy" width="600" height="400">
          </picture>
          <div class="p-3 bg-white text-start border-top">
            <span class="badge bg-warning text-dark mb-1"><i class="fas fa-book-open me-1"></i> Gestión del Conocimiento</span>
            <p class="text-secondary small mb-0">Documentación sistemática de lecciones aprendidas y actualización continua de los estándares operativos.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 5. Los 4 Pilares de la Gestión de la Calidad -->
<section class="py-5 bg-white" id="pilares-calidad">
  <div class="container py-lg-4">
    <div class="text-center max-w-700 mx-auto mb-5" style="max-width: 720px;">
      <span class="text-primary fw-bold text-uppercase small tracking-wide">
        <i class="fas fa-cubes me-1"></i> Marco Integral de Servicios
      </span>
      <h2 class="h1 fw-bold text-dark mt-2 mb-3">LOS 4 PILARES DE LA GESTIÓN DE LA CALIDAD</h2>
      <div class="heading-line-sm mx-auto mb-3" style="width: 60px; height: 4px; background: var(--pmo-accent); border-radius: 2px;"></div>
      <p class="text-secondary">Una propuesta integral diseñada para acompañar a tu empresa desde la estandarización documental hasta el control de campo y la postventa.</p>
    </div>

    <div class="row g-4">
      <div class="col-12 col-md-6" data-aos="fade-up" data-aos-delay="100">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-primary">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="p-3 rounded-3 bg-primary bg-opacity-10 text-primary fs-4">
              <i class="fas fa-file-shield"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-0 text-dark">1. Gestión de la Calidad</h5>
              <small class="text-muted">Estandarización Operativa</small>
            </div>
          </div>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Asesoría en estandarización de procesos, formulación de planes de calidad (PAQ) y diseño de formatos de control útiles, adaptados a la realidad de la obra.
          </p>
        </div>
      </div>

      <div class="col-12 col-md-6" data-aos="fade-up" data-aos-delay="200">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-warning">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="p-3 rounded-3 bg-warning bg-opacity-10 text-warning fs-4">
              <i class="fas fa-clipboard-check"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-0 text-dark">2. Pautas de Auditoría Interna</h5>
              <small class="text-muted">Control & Trazabilidad</small>
            </div>
          </div>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Orientación y criterios metodológicos para evaluar el estado de la calidad, trazabilidad de materiales, cierre oportuno de observaciones y preparación del dossier final.
          </p>
        </div>
      </div>

      <div class="col-12 col-md-6" data-aos="fade-up" data-aos-delay="300">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-success">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="p-3 rounded-3 bg-success bg-opacity-10 text-success fs-4">
              <i class="fas fa-arrow-trend-up"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-0 text-dark">3. Mejora Continua</h5>
              <small class="text-muted">Causa Raíz & Lecciones</small>
            </div>
          </div>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Metodología estructurada de análisis de causa raíz ante no conformidades, orientación en acciones correctivas y registro sistemático de lecciones aprendidas.
          </p>
        </div>
      </div>

      <div class="col-12 col-md-6" data-aos="fade-up" data-aos-delay="400">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 border-top border-4 border-info">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="p-3 rounded-3 bg-info bg-opacity-10 text-info fs-4">
              <i class="fas fa-chart-line"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-0 text-dark">4. Gestión de Postventa</h5>
              <small class="text-muted">Defectología & Retroalimentación</small>
            </div>
          </div>
          <p class="text-secondary small mb-0" style="line-height: 1.6;">
            Estudio estadístico de defectología, paneles de control predictivos y retroalimentación directa a los equipos de compras y construcción para evitar reincidencias.
          </p>
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
        <i class="fas fa-check-double me-1"></i> Asesoría Especializada en Calidad
      </span>
      <h2 class="h1 fw-extrabold text-white mb-3">
        Mejora la gestión de la calidad de tus proyectos
      </h2>
      <p class="text-white-50 fs-5 mb-4">
        Estandariza tus procesos, asegura la trazabilidad en obra y elimina sobrecostos por retrabajos con el acompañamiento de nuestros especialistas senior.
      </p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="/contacto" class="btn btn-pmo-warning fw-bold px-4 py-3 text-white shadow-sm hvr-grow">
          <i class="fas fa-clipboard-check me-2"></i> Solicitar Asesoría
        </a>
        <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20solicitar%20asesor%C3%ADa%20en%20Gesti%C3%B3n%20de%20la%20Calidad" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light fw-bold px-4 py-3 shadow-sm hvr-grow">
          <i class="fab fa-whatsapp me-2 text-success"></i> Contactar por WhatsApp
        </a>
      </div>
    </div>
  </div>
</section>

