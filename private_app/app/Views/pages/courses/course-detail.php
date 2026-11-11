<?php
use App\Core\View;

/**
 * PMO SOLUTIONS - Vista Reutilizable de Detalle de Curso (course-detail.php)
 * Renderiza todos los cursos migrados y administrados desde CourseModel.
 */

$title          = (string)($course['title'] ?? 'Curso de Especialización');
$slug           = (string)($course['slug'] ?? '');
$categoryLabel  = (string)($course['category_label'] ?? 'Capacitación Técnica');
$icon           = (string)($course['icon'] ?? 'fas fa-graduation-cap');
$badge          = (string)($course['badge'] ?? $categoryLabel);
$duration       = $course['duration'] ?? null;
$modality       = $course['modality'] ?? null;
$certification  = $course['certification'] ?? null;
$shortDesc      = (string)($course['short_description'] ?? ($course['description'] ?? ''));
$fullDesc       = (string)($course['full_description'] ?? $shortDesc);
$outcomes       = is_array($course['learning_outcomes'] ?? null) ? $course['learning_outcomes'] : [];
$syllabus       = is_array($course['syllabus'] ?? null) ? $course['syllabus'] : [];
$benefits       = is_array($course['benefits'] ?? null) ? $course['benefits'] : [];
$instructor     = $course['instructor'] ?? null;
$instTitle      = $course['instructor_title'] ?? null;
$instCreds      = is_array($course['instructor_credentials'] ?? null) ? $course['instructor_credentials'] : [];
$instBio        = is_array($course['instructor_bio'] ?? null) ? $course['instructor_bio'] : [];
$instImage      = $course['instructor_image'] ?? null;
$heroImage      = '/' . ltrim((string)($course['detail_image'] ?? ($course['image'] ?? 'img/LogoPMO.png')), '/');
$brochureUrl    = (string)($course['brochure_url'] ?? 'https://api.whatsapp.com/send?phone=51993463118');
$enrollSlug     = (string)($course['enrollment_slug'] ?? $slug);
$related        = is_array($relatedCourses ?? null) ? $relatedCourses : [];
?>

  <!-- 1. Hero del Curso -->
  <header class="nec-hero-section">
    <div class="hero-grid-overlay"></div>
    <div class="container position-relative">
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
          <li class="breadcrumb-item"><a href="/" class="text-white-50 text-decoration-none">Inicio</a></li>
          <li class="breadcrumb-item"><a href="/capacitaciones" class="text-white-50 text-decoration-none">Capacitaciones</a></li>
          <li class="breadcrumb-item active text-warning" aria-current="page"><?= View::e($categoryLabel) ?></li>
        </ol>
      </nav>

      <div class="row align-items-center gy-5">
        <div class="col-12 col-lg-7">
          <div class="mb-2">
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2 hero-badge shadow-sm">
              <i class="<?= View::e($icon) ?> me-2"></i> <?= View::e($badge) ?>
            </span>
          </div>
          <h1 class="hero-title mb-3 animate__animated animate__fadeInDown"><?= View::e($title) ?></h1>
          <?php if ($shortDesc !== ''): ?>
            <p class="hero-subtitle mb-4 animate__animated animate__fadeInUp"><?= View::e($shortDesc) ?></p>
          <?php endif; ?>

          <div class="hero-actions d-flex flex-wrap gap-2 pt-2">
            <?php if (!empty($syllabus)): ?>
              <a href="#temario-contenido" class="btn btn-warning btn-lg fw-bold shadow text-dark hvr-grow me-md-2 mb-2 mb-md-0">
                <i class="fas fa-book-open me-2"></i> Ver Temario Completo
              </a>
            <?php endif; ?>
            <a href="<?= View::e($brochureUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-lg fw-bold shadow hvr-grow mb-2 mb-md-0" data-enrollment-slug="<?= View::e($enrollSlug) ?>">
              <i class="fab fa-whatsapp me-2"></i> Solicitar Información & Brochure
            </a>
          </div>

          <div class="d-flex flex-wrap gap-3 gap-md-4 mt-4 pt-3 border-top border-white border-opacity-10 text-white-50">
            <div class="d-flex align-items-center gap-2">
              <i class="fas fa-layer-group text-warning fs-5"></i>
              <span class="text-white small fw-semibold"><?= View::e($categoryLabel) ?></span>
            </div>
            <?php if (!empty($duration)): ?>
              <div class="d-flex align-items-center gap-2">
                <i class="fas fa-clock text-info fs-5"></i>
                <span class="text-white small fw-semibold"><?= View::e($duration) ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($modality)): ?>
              <div class="d-flex align-items-center gap-2">
                <i class="fas fa-laptop text-success fs-5"></i>
                <span class="text-white small fw-semibold"><?= View::e($modality) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="d-flex flex-column gap-3">
            <?php if (!empty($duration)): ?>
              <div class="risk-metric-box d-flex align-items-start gap-3 text-white shadow-sm">
                <div class="p-3 rounded-3 bg-warning text-dark fs-4">
                  <i class="fas fa-stopwatch"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-warning">Duración del Programa</h5>
                  <p class="small text-white-50 mb-0"><?= View::e($duration) ?></p>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($certification)): ?>
              <div class="risk-metric-box d-flex align-items-start gap-3 text-white shadow-sm">
                <div class="p-3 rounded-3 bg-success text-white fs-4">
                  <i class="fas fa-award"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-white">Certificación & Acreditación</h5>
                  <p class="small text-white-50 mb-0"><?= View::e($certification) ?></p>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($instructor)): ?>
              <div class="risk-metric-box d-flex align-items-start gap-3 text-white shadow-sm">
                <div class="p-3 rounded-3 bg-info text-white fs-4">
                  <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                  <h5 class="fw-bold mb-1 text-warning">Docente Especialista</h5>
                  <p class="small text-white-50 mb-0"><?= View::e($instructor) ?><?= !empty($instTitle) ? ' · ' . View::e($instTitle) : '' ?></p>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- 2. Imagen del Programa, Objetivo y Perfil Docente -->
  <section class="py-5 my-lg-3">
    <div class="container">
      <div class="row g-4 g-lg-5 align-items-start">
        <div class="col-12 col-lg-5">
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden position-relative hvr-box-shadow-outset">
            <a href="<?= View::e($brochureUrl) ?>" target="_blank" rel="noopener noreferrer" data-enrollment-slug="<?= View::e($enrollSlug) ?>">
              <picture>
                <source srcset="<?= View::e($heroImage) ?>" type="image/webp">
                <img src="<?= View::e($heroImage) ?>" alt="<?= View::e($title) ?>" class="img-fluid w-100 rounded-4" style="display: block;">
              </picture>
              <div class="card-img-overlay d-flex align-items-end p-3 bg-dark bg-opacity-25">
                <span class="btn btn-warning btn-sm fw-bold w-100 shadow text-dark">
                  <i class="fas fa-file-alt me-2"></i> Solicitar Temario & Ficha Técnica
                </span>
              </div>
            </a>
          </div>
        </div>

        <div class="col-12 col-lg-7">
          <span class="section-badge"><?= View::e($categoryLabel) ?></span>
          <h2 class="section-title mb-3">Enfoque y Objetivo del Programa</h2>

          <?php if ($fullDesc !== ''): ?>
            <div class="p-4 rounded-4 bg-light border-start border-4 border-warning mb-4 shadow-sm">
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="fas fa-bullseye text-warning fs-5"></i>
                <h5 class="fw-bold text-dark mb-0">Objetivo General</h5>
              </div>
              <p class="text-secondary mb-0" style="line-height: 1.75;">
                <?= nl2br(View::e($fullDesc)) ?>
              </p>
            </div>
          <?php endif; ?>

          <?php if (!empty($outcomes)): ?>
            <div class="mb-4">
              <h6 class="fw-bold text-dark mb-3"><i class="fas fa-check-circle text-success me-2"></i>Ejes Clave y Resultados de Aprendizaje</h6>
              <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                <?php foreach ($outcomes as $outcome): ?>
                  <li class="d-flex align-items-start gap-2 text-secondary">
                    <i class="fas fa-angle-right text-primary mt-1 flex-shrink-0"></i>
                    <span><?= View::e($outcome) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if (!empty($instructor)): ?>
            <div class="card bg-light border-0 rounded-4 p-4 mt-4 shadow-sm border-start border-5 border-primary">
              <div class="row align-items-center g-3">
                <?php if (!empty($instImage)): ?>
                  <div class="col-md-3 text-center">
                    <img src="/<?= View::e(ltrim($instImage, '/')) ?>" alt="<?= View::e($instructor) ?>" class="rounded-circle shadow border border-3 border-white" loading="lazy" style="width: 110px; height: 110px; object-fit: cover; object-position: top;">
                  </div>
                <?php endif; ?>
                <div class="<?= !empty($instImage) ? 'col-md-9' : 'col-12' ?>">
                  <span class="badge bg-primary text-white mb-2 px-3 py-2 rounded-pill">
                    <i class="fas fa-user-tie me-1"></i> DOCENTE ESPECIALISTA
                  </span>
                  <h4 class="fw-bold mb-1 text-dark"><?= View::e($instructor) ?></h4>
                  <?php if (!empty($instTitle)): ?>
                    <p class="small text-primary fw-bold mb-2"><?= View::e($instTitle) ?></p>
                  <?php endif; ?>

                  <?php if (!empty($instCreds)): ?>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                      <?php foreach ($instCreds as $cred): ?>
                        <span class="badge bg-white text-dark border"><?= View::e($cred) ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($instBio)): ?>
                    <div class="small text-muted" style="line-height: 1.65;">
                      <?php foreach ($instBio as $bioLine): ?>
                        <p class="mb-2"><?= View::e($bioLine) ?></p>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- 3. Temario Estructurado -->
  <?php if (!empty($syllabus)): ?>
    <section class="py-5 bg-light" id="temario-contenido">
      <div class="container">
        <div class="text-center mb-5">
          <span class="section-badge">Estructura Académica</span>
          <h2 class="section-title">Temario Oficial del Curso</h2>
          <p class="section-subtitle">
            Contenido modular orientado a la aplicación práctica en obras de edificación, infraestructura y administración contractual.
          </p>
        </div>

        <div class="row g-4">
          <?php foreach ($syllabus as $idx => $module): ?>
            <?php
              $modNum = $module['number'] ?? ($idx + 1);
              $modTitle = (string)($module['title'] ?? ('Módulo ' . $modNum));
              $modDesc = (string)($module['description'] ?? '');
            ?>
            <div class="col-12 col-md-6">
              <div class="nec-clause-card h-100 hvr-box-shadow-outset bg-white">
                <span class="nec-clause-badge">
                  <i class="fas fa-layer-group me-1"></i> MÓDULO <?= View::e(str_pad((string)$modNum, 2, '0', STR_PAD_LEFT)) ?>
                </span>
                <h4 class="nec-clause-title mt-2"><?= View::e($modTitle) ?></h4>
                <?php if ($modDesc !== ''): ?>
                  <p class="nec-clause-desc mb-0"><?= nl2br(View::e($modDesc)) ?></p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- 4. Beneficios del Programa -->
  <?php if (!empty($benefits)): ?>
    <section class="py-5 bg-white">
      <div class="container">
        <div class="text-center mb-4">
          <span class="section-badge">Experiencia Formativa</span>
          <h2 class="section-title">Incluye en tu Capacitación</h2>
        </div>
        <div class="row g-4 justify-content-center">
          <?php foreach ($benefits as $benefit): ?>
            <div class="col-12 col-md-6 col-lg-4">
              <div class="guarantee-card h-100 hvr-box-shadow-outset">
                <h5 class="fw-bold mb-2 text-dark"><?= View::e($benefit['title'] ?? '') ?></h5>
                <p class="text-muted small mb-0"><?= View::e($benefit['description'] ?? '') ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- 5. CTA de Contacto Corporativo y Solicitud de Información (Sin pasarela de pagos) -->
  <section class="py-5 bg-primary text-white" id="seccion-brochure">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2">
            <i class="fas fa-graduation-cap me-1"></i> ASESORÍA ACADÉMICA & IN-HOUSE
          </span>
          <h2 class="fw-bold mb-2">¿Deseas solicitar el brochure o programar este curso para tu equipo?</h2>
          <p class="text-white-50 mb-0">
            Contáctanos directamente para recibir información detallada de fechas de inicio, modalidades corporativas in-house y facilidades de inscripción.
          </p>
        </div>
        <div class="col-lg-4 d-flex flex-wrap gap-3 justify-content-lg-end">
          <a href="<?= View::e($brochureUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-warning btn-lg fw-bold text-dark shadow" data-enrollment-slug="<?= View::e($enrollSlug) ?>">
            <i class="fab fa-whatsapp me-2"></i> Consultar por WhatsApp
          </a>
          <a href="/contacto" class="btn btn-outline-light btn-lg fw-bold">
            <i class="fas fa-envelope me-2"></i> Contacto Directo
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- 6. Cursos Relacionados -->
  <?php if (!empty($related)): ?>
    <section class="py-5 bg-light">
      <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
          <div>
            <span class="section-badge">Sigue Especializándote</span>
            <h2 class="section-title mb-0">Programas Relacionados</h2>
          </div>
          <a href="/capacitaciones" class="btn btn-outline-primary fw-bold">
            <i class="fas fa-th-large me-2"></i> Ver Catálogo Completo
          </a>
        </div>

        <div class="row g-4">
          <?php foreach ($related as $rel): ?>
            <?php
              $relImg = '/' . ltrim((string)($rel['image'] ?? 'img/LogoPMO.png'), '/');
              $relHref = '/curso/' . rawurlencode((string)$rel['slug']);
            ?>
            <div class="col-lg-4 col-md-6">
              <div class="catalog-card h-100 hvr-box-shadow-outset">
                <a href="<?= View::e($relHref) ?>" class="catalog-card-img-wrapper">
                  <img src="<?= View::e($relImg) ?>" alt="<?= View::e($rel['title']) ?>" class="img-fluid rounded-top-4" loading="lazy">
                  <div class="catalog-card-badge">
                    <span class="badge <?= View::e($rel['badge_class'] ?? 'bg-primary text-white') ?>"><?= View::e($rel['badge'] ?? $rel['category_label']) ?></span>
                  </div>
                </a>
                <div class="catalog-card-body">
                  <h3 class="catalog-card-title">
                    <a href="<?= View::e($relHref) ?>"><?= View::e($rel['title']) ?></a>
                  </h3>
                  <p class="catalog-card-desc"><?= View::e($rel['short_description'] ?? $rel['description']) ?></p>
                  <div class="mt-auto pt-3 border-top border-light-subtle">
                    <a href="<?= View::e($relHref) ?>" class="btn btn-primary w-100 fw-bold">
                      <i class="fas fa-book-open me-2"></i> Ver Temario & Detalles
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>
