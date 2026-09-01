<?php
/**
 * PMO SOLUTIONS - Componente Parcial: Navbar & Top Bars
 */
$active = $activeNav ?? '';
?>
<!-- Top Announcement Notification Bar -->
<div class="announcement-bar">
  <div class="container d-flex flex-wrap align-items-center justify-content-center">
    <span class="announcement-badge"><i class="fas fa-bullhorn me-1"></i> Convocatoria 2026</span>
    <span>Inscripciones abiertas en Programas de Alta Especialización Contractual & Forense.</span>
    <a href="capacitaciones" class="hvr-grow"><i class="fas fa-arrow-right me-1"></i> Ver Catálogo y Beneficios</a>
  </div>
</div>

<!-- Top Utility Bar -->
<div class="top-bar py-2 d-none d-md-block">
  <div class="container d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-4">
      <span><i class="fas fa-map-marker-alt text-warning me-2"></i>Av. Javier Prado 757, piso 10 Magdalena, Lima 17</span>
      <a href="mailto:comercial@pmo-solutions.com"><i class="fas fa-envelope text-warning me-2"></i>comercial@pmo-solutions.com</a>
    </div>
    <div class="d-flex align-items-center gap-3">
      <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n" target="_blank" rel="noopener noreferrer">
        <i class="fab fa-whatsapp text-success me-1"></i> +51 944 276 649
      </a>
      <span class="text-white-50">|</span>
      <a href="https://www.facebook.com/people/PMO-Solutions/100070284155015/#" target="_blank" rel="noopener noreferrer" title="Facebook"><i class="fab fa-facebook"></i></a>
      <a href="https://www.youtube.com/@pmosolutions/videos" target="_blank" rel="noopener noreferrer" title="YouTube"><i class="fab fa-youtube"></i></a>
    </div>
  </div>
</div>

<!-- Header & Navigation -->
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container">
    <a class="navbar-brand" href="./">
      <img src="img/LogoPMO.png" alt="PMO Solutions Logo" class="brand-logo-img animate__animated animate__bounceIn" width="44" height="44" style="mix-blend-mode: multiply;">
      <div class="brand-text-wrapper">
        <span class="brand-title">PMO <span>SOLUTIONS</span></span>
        <span class="brand-subtitle">Construimos Soluciones</span>
      </div>
    </a>

    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPmoNav" aria-controls="navbarPmoNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarPmoNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <li class="nav-item">
          <a class="nav-link <?= $active === 'home' ? 'active' : '' ?> hvr-underline-from-left" href="./">
            <i class="fas fa-home me-1 d-lg-none"></i> HOME
          </a>
        </li>

        <!-- Capacitaciones Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $active === 'capacitaciones' ? 'active' : '' ?> hvr-underline-from-left" href="capacitaciones" id="navbarDropdownCapacitaciones" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-graduation-cap me-1 d-lg-none"></i> CAPACITACIONES
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownCapacitaciones">
            <li><a class="dropdown-item fw-bold text-primary border-bottom pb-2 mb-1" href="capacitaciones"><i class="fas fa-th-large me-2"></i> Ver Todo el Catálogo</a></li>
            <li><a class="dropdown-item" href="dab-jrd"><i class="fas fa-gavel me-2"></i> Dispute Boards & DAB-JRD</a></li>
            <li><a class="dropdown-item" href="analisis-forense"><i class="fas fa-search-plus me-2"></i> Análisis Forense de Atrasos</a></li>
            <li><a class="dropdown-item" href="nec4"><i class="fas fa-file-contract me-2"></i> Contratos NEC4 (ECC / PSC)</a></li>
            <li><a class="dropdown-item" href="vdc-bim"><i class="fas fa-cubes me-2"></i> VDC - BIM Management</a></li>
            <li><a class="dropdown-item" href="contratos-estado"><i class="fas fa-landmark me-2"></i> Gestión del Cambio & Contratos Estado</a></li>
            <li><a class="dropdown-item" href="compliance"><i class="fas fa-balance-scale me-2"></i> Compliance en la Construcción</a></li>
            <li><a class="dropdown-item" href="primavera-p6"><i class="fas fa-chart-line me-2"></i> Primavera P6 & Power BI</a></li>
            <li><a class="dropdown-item" href="riesgos-pmi"><i class="fas fa-shield-alt me-2"></i> Gestión Integral de Riesgos (PMI®)</a></li>
            <li><a class="dropdown-item" href="analisis-cuantitativo"><i class="fas fa-calculator me-2"></i> Análisis Cuantitativo de Riesgos</a></li>
            <li><a class="dropdown-item" href="eventos-compensables"><i class="fas fa-file-invoice-dollar me-2"></i> Eventos Compensables</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $active === 'contacto' ? 'active' : '' ?> hvr-underline-from-left" href="contacto">
            <i class="fas fa-envelope me-1 d-lg-none"></i> CONTACTO
          </a>
        </li>

        <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
          <a href="https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n%20sobre%20sus%20servicios%20y%20cursos" target="_blank" rel="noopener noreferrer" class="btn btn-pmo-warning fw-bold px-3 py-2 text-white d-inline-flex align-items-center justify-content-center w-100 w-lg-auto shadow-sm hvr-grow">
            <i class="fab fa-whatsapp me-2 fs-5"></i> Contactar Asesor
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

