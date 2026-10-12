<?php
use App\Core\View;

/**
 * PMO SOLUTIONS - Vista: Evaluación Interactiva de Habilidades Blandas
 * 
 * Compatible con:
 *  - App\Controllers\SoftSkillsController
 *  - js/modules/soft-skills.js
 *  - js/core/utils.js
 */
?>

<!-- =========================================================================
     1. ENCABEZADO HERO (ESTILO CORPORATIVO PMO)
========================================================================= -->
<header class="hero-section text-center text-lg-start position-relative overflow-hidden py-5" style="background: linear-gradient(135deg, #0A3663 0%, #1a5b82 100%);">
  <div class="hero-grid-overlay"></div>
  <div class="container position-relative py-lg-4">
    <div class="row align-items-center gy-4">
      <div class="col-lg-8">
        <div class="mb-3">
          <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2 hero-badge shadow-sm">
            <i class="fas fa-brain me-2"></i> DIAGNÓSTICO PROFESIONAL 2026
          </span>
        </div>
        <h1 class="hero-title text-white fw-bold display-5 mb-3">
          Evaluación de Habilidades Blandas en Ingeniería & Proyectos
        </h1>
        <p class="hero-subtitle text-white-50 fs-5 mb-4" style="max-width: 680px;">
          Mide tu nivel en <strong>Comunicación, Trabajo en Equipo, Resolución de Problemas y Adaptabilidad</strong> a través de 20 escenarios situacionales reales del sector construcción y contratos colaborativos.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="#soft-skills-container" class="btn btn-warning btn-lg fw-bold text-dark px-4 py-3 rounded-pill shadow hvr-grow">
            <i class="fas fa-play me-2"></i> Iniciar Evaluación Gratuita
          </a>
          <a href="capacitaciones" class="btn btn-outline-light btn-lg fw-bold px-4 py-3 rounded-pill hvr-grow">
            <i class="fas fa-graduation-cap me-2"></i> Ver Programas Formativos
          </a>
        </div>
      </div>

      <div class="col-lg-4 d-none d-lg-block">
        <div class="card border-0 rounded-4 shadow-lg p-4 bg-white bg-opacity-10 backdrop-blur text-white border-white border-opacity-10">
          <div class="text-center mb-3">
            <div class="d-inline-flex p-3 rounded-circle bg-warning text-dark mb-2 shadow-sm">
              <i class="fas fa-chart-pie fs-2"></i>
            </div>
            <h5 class="fw-bold text-white mb-1">Reporte Inmediato</h5>
            <p class="text-white-50 small mb-0">Resultados calculados con lógica de madurez profesional</p>
          </div>
          <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
            <li class="d-flex align-items-center gap-2">
              <i class="fas fa-check-circle text-warning"></i>
              <span>Gráfico de Radar Interactivo (Chart.js)</span>
            </li>
            <li class="d-flex align-items-center gap-2">
              <i class="fas fa-check-circle text-warning"></i>
              <span>Diagnóstico de Fortalezas & Oportunidades</span>
            </li>
            <li class="d-flex align-items-center gap-2">
              <i class="fas fa-check-circle text-warning"></i>
              <span>Descarga de Reporte Oficial en PDF</span>
            </li>
            <li class="d-flex align-items-center gap-2">
              <i class="fas fa-check-circle text-warning"></i>
              <span>Recomendaciones formativas personalizadas</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- =========================================================================
     2. CONTENEDOR PRINCIPAL DE EVALUACIÓN DINÁMICA
========================================================================= -->
<section id="soft-skills-container" class="py-5 bg-light">
  <div class="container py-lg-4" style="max-width: 900px;">

    <!-- Spinner de Carga -->
    <div id="eval-loader" class="text-center py-5" style="display: none;">
      <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
      <h5 class="loader-msg fw-semibold text-secondary">Cargando evaluación...</h5>
    </div>

    <!-- Alerta de Error -->
    <div id="eval-error" class="alert alert-danger shadow-sm rounded-4 mb-4" role="alert" style="display: none;"></div>

    <!-- ─────────────────────────────────────────────────────────────────────
         PASO 1: FORMULARIO DE REGISTRO DEL PARTICIPANTE
    ───────────────────────────────────────────────────────────────────── -->
    <div id="step-participant" class="step-card">
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
        
        <div class="text-center mb-4">
          <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-2">
            Paso 1 de 2: Identificación
          </span>
          <h2 class="fw-bold text-dark mb-2">Datos del Evaluado</h2>
          <p class="text-muted">
            Ingresa tus datos para generar tu informe confidencial de competencias blandas con código único institucional.
          </p>
        </div>

        <form id="form-participant" novalidate>
          <!-- CSRF Token de Seguridad -->
          <input type="hidden" name="csrf_token" value="<?= \App\Core\View::e(\App\Core\Csrf::getToken()) ?>">

          <!-- Campo Trampa Honeypot Anti-Spam -->
          <input type="text" name="website_hp" class="d-none" tabindex="-1" autocomplete="off">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">
                Nombre Completo <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                <input type="text" name="nombre_completo" class="form-control border-start-0 ps-0" placeholder="Ej. Ing. Carlos Mendoza" required>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">
                Correo Electrónico <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="carlos.mendoza@empresa.com" required>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">Cargo / Puesto Actual</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-briefcase text-muted"></i></span>
                <input type="text" name="cargo" class="form-control border-start-0 ps-0" placeholder="Ej. Residente de Obra / Project Manager">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold text-dark">Empresa u Organización</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-building text-muted"></i></span>
                <input type="text" name="empresa" class="form-control border-start-0 ps-0" placeholder="Ej. Consorcio Vial / Constructora">
              </div>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold text-dark">Objetivo de la Evaluación</label>
              <select name="tipo_evaluacion" class="form-select">
                <option value="Individual" selected>Diagnóstico Individual de Desarrollo Profesional</option>
                <option value="Pre-Capacitación">Evaluación Diagnóstica Pre-Capacitación</option>
                <option value="Post-Capacitación">Evaluación de Cierre Post-Capacitación</option>
                <option value="Grupal">Evaluación Corporativa de Equipo</option>
              </select>
            </div>
          </div>

          <div class="mt-4 pt-3 text-center border-top">
            <button type="button" id="btn-start-eval" class="btn btn-warning btn-lg px-5 py-3 fw-bold rounded-pill shadow hvr-grow text-dark">
              Comenzar Evaluación <i class="fas fa-arrow-right ms-2"></i>
            </button>
            <p class="small text-muted mt-2 mb-0">
              <i class="fas fa-clock me-1"></i> Tiempo estimado: 6 a 8 minutos | 20 preguntas situacionales
            </p>
          </div>
        </form>

      </div>

      <!-- Resumen de Competencias Evaluadas -->
      <div class="row g-3">
        <?php if (!empty($catalog)): ?>
          <?php foreach ($catalog as $compKey => $comp): ?>
            <div class="col-sm-6 col-lg-3">
              <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 text-center">
                <div class="p-3 rounded-circle d-inline-flex mx-auto mb-2" style="background-color: <?= View::e($comp['color'] ?? '#1a5b82') ?>15; color: <?= View::e($comp['color'] ?? '#1a5b82') ?>;">
                  <i class="<?= View::e($comp['icon'] ?? 'fas fa-star') ?> fs-4"></i>
                </div>
                <h6 class="fw-bold mb-1 text-dark"><?= View::e($comp['label'] ?? '') ?></h6>
                <small class="text-muted" style="font-size: 0.78rem; line-height: 1.3;">
                  <?= View::e($comp['description'] ?? '') ?>
                </small>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────────────
         PASO 2: CUESTIONARIO SITUACIONAL INTERACTIVO
    ───────────────────────────────────────────────────────────────────── -->
    <div id="step-evaluation" class="step-card" style="display: none;">
      
      <!-- Barra Superior de Progreso -->
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
          <div class="d-flex align-items-center gap-2">
            <span class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
              <i id="current-competency-icon" class="fas fa-comments"></i>
            </span>
            <span class="fw-bold text-dark fs-6" id="current-competency-name">Comunicación</span>
          </div>
          <span class="badge bg-light text-secondary border fw-semibold px-3 py-2" id="eval-progress-text">
            Pregunta 1 de 20
          </span>
        </div>

        <div class="progress" style="height: 10px; border-radius: 6px; background-color: #e9ecef;">
          <div id="eval-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 5%;" aria-valuenow="5" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>

      <!-- Tarjeta Dinámica de Pregunta -->
      <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4" id="question-card">
        <!-- Contenido inyectado dinámicamente por soft-skills.js -->
      </div>

      <!-- Botones de Navegación -->
      <div class="d-flex justify-content-between align-items-center">
        <button type="button" id="btn-prev" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-semibold" style="display: none;">
          <i class="fas fa-arrow-left me-2"></i> Anterior
        </button>
        <div class="ms-auto">
          <button type="button" id="btn-next" class="btn btn-primary btn-lg px-5 py-3 rounded-pill fw-bold shadow hvr-grow">
            Siguiente <i class="fas fa-arrow-right ms-2"></i>
          </button>
        </div>
      </div>

    </div>

    <!-- ─────────────────────────────────────────────────────────────────────
         PASO 3: TABLERO DE RESULTADOS Y DIAGNÓSTICO
    ───────────────────────────────────────────────────────────────────── -->
    <div id="step-results" class="step-card" style="display: none;">
      
      <!-- Contenedor Dinámico de Resultados -->
      <div id="results-container">
        <!-- Inyectado dinámicamente por soft-skills.js tras calcular resultados -->
      </div>

      <!-- Canvas de Gráfico Radar -->
      <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mt-4 text-center">
        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-chart-area me-2 text-primary"></i>Perfil Visual de Competencias (Radar 360°)</h5>
        <div style="max-width: 500px; margin: 0 auto; position: relative;">
          <canvas id="radar-chart" width="400" height="400"></canvas>
        </div>
      </div>

    </div>

  </div>
</section>

<!-- =========================================================================
     ESTILOS ESPECÍFICOS PARA EL MÓDULO DE EVALUACIÓN
========================================================================= -->
<style>
  .step-card {
    transition: all 0.3s ease-in-out;
  }
  .backdrop-blur {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
  }
  .question-scenario {
    color: #1a2a3a;
    line-height: 1.6;
    font-size: 1.15rem;
  }
  .question-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .option-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 20px;
    background-color: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    user-select: none;
  }
  .option-card:hover {
    background-color: #ffffff;
    border-color: #cbd5e1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  }
  .option-card.selected {
    background-color: #eff6ff;
    border-color: #1a5b82;
    box-shadow: 0 4px 14px rgba(26, 91, 130, 0.15);
  }
  .option-letter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    min-width: 32px;
    border-radius: 50%;
    background-color: #e2e8f0;
    color: #475569;
    font-weight: 700;
    font-size: 0.9rem;
    transition: all 0.2s ease;
  }
  .option-card.selected .option-letter {
    background-color: #1a5b82;
    color: #ffffff;
  }
  .option-text {
    font-size: 0.98rem;
    color: #334155;
    line-height: 1.5;
    margin-top: 4px;
  }
  .option-card.selected .option-text {
    color: #0f172a;
    font-weight: 500;
  }
  .shake {
    animation: pmoShake 0.4s ease-in-out;
  }
  @keyframes pmoShake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-8px); }
    40%, 80% { transform: translateX(8px); }
  }
  .pmo-toast {
    background-color: #1e293b;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    font-size: 0.92rem;
    opacity: 0;
    transform: translateY(15px);
    transition: all 0.3s ease;
  }
  .pmo-toast--visible {
    opacity: 1;
    transform: translateY(0);
  }
  .pmo-toast--success { border-left: 5px solid #22c55e; }
  .pmo-toast--error { border-left: 5px solid #ef4444; }
  .pmo-toast--warning { border-left: 5px solid #f59e0b; }
  .pmo-toast--info { border-left: 5px solid #3b82f6; }
</style>

