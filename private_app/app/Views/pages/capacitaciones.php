<?php
use App\Core\View;
use App\Models\CourseModel;

$coursesList = is_array($courses ?? null) ? $courses : [];
$totalCourses = count($coursesList);

if (empty($categories) && !empty($coursesList)) {
    $categories = [];
    foreach ($coursesList as $item) {
        $catKey = (string)($item['category'] ?? 'general');
        if (!isset($categories[$catKey])) {
            $categories[$catKey] = [
                'slug'  => $catKey,
                'label' => (string)($item['category_label'] ?? ucfirst($catKey)),
                'icon'  => (string)($item['icon'] ?? 'fas fa-folder'),
                'count' => 0,
            ];
        }
        $categories[$catKey]['count']++;
    }
}
?>
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
            Aprende los estándares más exigentes de la industria en contratos colaborativos NEC3/NEC4 y FIDIC, juntas de disputas (DAB/JRD), análisis forense de atrasos, gestión de calidad, oficina técnica, VDC-BIM y control de proyectos.
          </p>
          <div class="d-flex flex-wrap gap-3">
            <a href="#catalogo-grid" class="btn btn-warning btn-lg fw-bold text-dark hvr-grow">
              <i class="fas fa-th-large me-2"></i> Explorar Cursos Disponibles (<?= View::e((string)$totalCourses) ?>)
            </a>
            <a href="/contacto" class="btn btn-outline-light btn-lg fw-bold hvr-grow">
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
                  <div class="text-warning fw-bold fs-5"><?= View::e((string)$totalCourses) ?></div>
                  <div class="text-white-50" style="font-size: 0.72rem;">Cursos Activos</div>
                </div>
              </div>
              <div class="col-6">
                <div class="bg-dark bg-opacity-50 p-2 rounded border border-white border-opacity-10 text-center">
                  <div class="text-info fw-bold fs-5">24/7</div>
                  <div class="text-white-50" style="font-size: 0.72rem;">Acceso Online</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- 2. Grid del Catálogo de Cursos con Filtro Interactivo y Búsqueda -->
  <section id="catalogo-grid" class="py-5 my-lg-4" data-courses-catalog>
    <div class="container">

      <div class="text-center mb-4" data-aos="fade-up">
        <span class="section-badge">Oferta Académica 2026</span>
        <h2 class="section-title">Programas Especializados de Ingeniería y Contratos</h2>
        <p class="section-subtitle">
          Selecciona un área académica o busca por título, docente o palabra clave para explorar temarios detallados, plana docente y casos prácticos de aplicación real.
        </p>
      </div>

      <!-- BARRA DE BÚSQUEDA Y ACCIONES DEL CATÁLOGO -->
      <div class="catalog-search-toolbar mb-4">
        <div class="row justify-content-center align-items-end g-2">
          <div class="col-12 col-md-8 col-lg-6">
            <label for="catalogSearchInput" class="form-label fw-semibold text-dark small mb-1">
              Buscar curso por título, docente o especialidad
            </label>
            <div class="input-group input-group-lg shadow-sm catalog-search-group">
              <span class="input-group-text bg-white border-end-0 text-primary" aria-hidden="true">
                <i class="fas fa-search"></i>
              </span>
              <input
                type="search"
                id="catalogSearchInput"
                class="form-control border-start-0"
                autocomplete="off"
                placeholder="Ej. NEC4, Primavera, Riesgos, VDC, Calidad, Edwar...">
              <button
                type="button"
                id="catalogClearSearchBtn"
                class="btn btn-outline-secondary d-none"
                aria-label="Limpiar texto de búsqueda"
                title="Limpiar búsqueda">
                <i class="fas fa-times" aria-hidden="true"></i>
              </button>
            </div>
          </div>
          <div class="col-12 col-md-auto text-center text-md-start">
            <button
              type="button"
              id="catalogToolbarResetBtn"
              class="btn btn-outline-primary btn-lg fw-semibold catalog-reset-btn d-none"
              hidden>
              <i class="fas fa-undo me-1" aria-hidden="true"></i> Limpiar filtros
            </button>
          </div>
        </div>
      </div>

      <!-- BOTONES DE FILTRO POR CATEGORÍA (Generados dinámicamente desde CourseModel) -->
      <div class="catalog-filter-container"
           role="group"
           aria-label="Filtrar cursos por área académica"
           data-aos="fade-up"
           data-aos-delay="100">
        <button type="button" class="catalog-filter-btn active" data-filter="all" aria-pressed="true">
          <i class="fas fa-th-large me-1" aria-hidden="true"></i> Todos los cursos (<?= View::e((string)$totalCourses) ?>)
        </button>
        <?php foreach (($categories ?? []) as $cat): ?>
          <button type="button" class="catalog-filter-btn" data-filter="<?= View::e($cat['slug']) ?>" aria-pressed="false">
            <i class="<?= View::e($cat['icon']) ?> me-1" aria-hidden="true"></i> <?= View::e($cat['label']) ?> (<?= View::e((string)$cat['count']) ?>)
          </button>
        <?php endforeach; ?>
      </div>

      <p class="text-center small text-muted mb-4" id="catalogResultsCounter" aria-live="polite" aria-atomic="true">
        Mostrando <strong id="catalogVisibleCount"><?= View::e((string)$totalCourses) ?></strong> de <strong><?= View::e((string)$totalCourses) ?></strong> cursos
      </p>

      <!-- GRID DINÁMICO DE CURSOS DESDE COURSEMODEL -->
      <div class="row g-4" id="coursesGridContainer" data-visible-courses="<?= View::e((string)$totalCourses) ?>">
        <?php foreach ($coursesList as $course): ?>
          <?php
            $cSlug     = (string)$course['slug'];
            $cTitle    = (string)$course['title'];
            $cCat      = (string)($course['category'] ?? 'general');
            $cCatLabel = (string)($course['category_label'] ?? 'Capacitación');
            $cDesc     = (string)($course['short_description'] ?? ($course['description'] ?? ''));
            $cBadge    = (string)($course['badge'] ?? $cCatLabel);
            $cBadgeCls = (string)($course['badge_class'] ?? 'bg-primary text-white');
            $cImg      = '/' . ltrim((string)($course['image'] ?? 'img/LogoPMO.png'), '/');
            $cUrl      = '/curso/' . rawurlencode($cSlug);
            $cDuration = $course['duration'] ?? null;
            $cInst     = $course['instructor'] ?? null;
            $cSearch   = (string)($course['search_text'] ?? CourseModel::buildSearchText($course));
            $isWebp    = str_ends_with(strtolower($cImg), '.webp');
          ?>
          <div class="col-lg-4 col-md-6 catalog-card-col"
               data-course-card
               data-category="<?= View::e($cCat) ?>"
               data-search="<?= View::e($cSearch) ?>">
            <div class="catalog-card h-100 hvr-box-shadow-outset">
              <a href="<?= View::e($cUrl) ?>" class="catalog-card-img-wrapper" title="Ver programa <?= View::e($cTitle) ?>">
                <?php if ($isWebp): ?>
                  <picture>
                    <source srcset="<?= View::e($cImg) ?>" type="image/webp">
                    <img src="<?= View::e($cImg) ?>" alt="<?= View::e($cTitle) ?>" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
                  </picture>
                <?php else: ?>
                  <img src="<?= View::e($cImg) ?>" alt="<?= View::e($cTitle) ?>" class="img-fluid rounded-top-4 shadow-sm" loading="lazy">
                <?php endif; ?>
                <div class="catalog-card-badge">
                  <span class="badge <?= View::e($cBadgeCls) ?>"><?= View::e($cBadge) ?></span>
                </div>
                <span class="hover-zoom-hint"><i class="fas fa-search-plus me-1" aria-hidden="true"></i> Ver Programa</span>
              </a>
              <div class="catalog-card-body">
                <h3 class="catalog-card-title">
                  <a href="<?= View::e($cUrl) ?>"><?= View::e($cTitle) ?></a>
                </h3>
                <p class="catalog-card-desc">
                  <?= View::e($cDesc) ?>
                </p>
                <?php if (!empty($cDuration) || !empty($cInst)): ?>
                  <div class="d-flex flex-wrap gap-2 small text-muted mb-2">
                    <?php if (!empty($cDuration)): ?>
                      <span><i class="fas fa-clock text-primary me-1" aria-hidden="true"></i><?= View::e($cDuration) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($cInst)): ?>
                      <span><i class="fas fa-user-tie text-warning me-1" aria-hidden="true"></i><?= View::e($cInst) ?></span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                <div class="mt-auto pt-3 border-top border-light-subtle">
                  <a href="<?= View::e($cUrl) ?>" class="btn btn-primary w-100 mt-2 fw-bold hvr-grow">
                    <i class="fas fa-book-open me-2" aria-hidden="true"></i> Ver Temario & Detalles
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div id="catalogEmptyState" class="text-center py-5 d-none" role="status" aria-live="polite" hidden>
        <i class="fas fa-search fs-1 text-muted mb-3" aria-hidden="true"></i>
        <h3 class="h4 fw-bold text-dark">No encontramos cursos con los filtros seleccionados.</h3>
        <p class="text-muted mb-3">Prueba con otra palabra clave o restablece los filtros para explorar los <?= View::e((string)$totalCourses) ?> cursos disponibles.</p>
        <button type="button" class="btn btn-outline-primary fw-bold" id="catalogResetFilterBtn">
          <i class="fas fa-undo me-1" aria-hidden="true"></i> Limpiar filtros
        </button>
      </div>
    </div>
  </section>

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
        <!-- Pilar 1: Clases Grabadas en HD -->
        <div class="col-lg-4 col-md-6">
          <div class="guarantee-card hvr-box-shadow-outset">
            <div class="guarantee-icon-box bg-primary bg-opacity-10 text-primary">
              <i class="fas fa-laptop-code text-primary fs-2"></i>
            </div>
            <h4 class="fw-bold mb-3">Clases Grabadas en HD</h4>
            <p class="text-muted mb-0">
              Lecciones estructuradas paso a paso en video HD, preparadas por especialistas de amplia trayectoria internacional para su aplicación directa en proyectos.
            </p>
          </div>
        </div>

        <!-- Pilar 2: Acceso Digital Flexible 24/7 -->
        <div class="col-lg-4 col-md-6">
          <div class="guarantee-card hvr-box-shadow-outset">
            <div class="guarantee-icon-box bg-success bg-opacity-10 text-success">
              <i class="fas fa-play-circle text-success fs-2"></i>
            </div>
            <h4 class="fw-bold mb-3">Acceso Digital Flexible 24/7</h4>
            <p class="text-muted mb-0">
              Estudia a tu propio ritmo, repite las lecciones cuando lo necesites y accede a plantillas y materiales descargables en cualquier momento sin horarios fijos.
            </p>
          </div>
        </div>

        <!-- Pilar 3: Plantillas y Herramientas Editables -->
        <div class="col-lg-4 col-md-12">
          <div class="guarantee-card hvr-box-shadow-outset">
            <div class="guarantee-icon-box bg-warning bg-opacity-10 text-warning">
              <i class="fas fa-file-excel text-warning fs-2"></i>
            </div>
            <h4 class="fw-bold mb-3">Plantillas y Herramientas Editables</h4>
            <p class="text-muted mb-0">
              Modelos de contratos, matrices de riesgos, hojas de cálculo y documentación técnica listos para implementar de inmediato en obra o gabinete.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 4. Footer Corporativo Limpio (Con Selector Multipaís & Libro de Reclamaciones) -->