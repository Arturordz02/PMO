<style>
  /* Estilos Específicos para la Escena 404 */
  body.error-page-body {
    background: radial-gradient(circle at 50% 20%, #0d2847 0%, #061527 100%);
    color: #f8fafc;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
    font-family: 'Plus Jakarta Sans', sans-serif;
  }

  .blueprint-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-image: 
      linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
    background-size: 30px 30px;
    pointer-events: none;
    z-index: 0;
  }

  .error-container {
    position: relative;
    z-index: 1;
  }

  .error-code-wrapper {
    position: relative;
    display: inline-block;
    line-height: 1;
  }

  .error-code-number {
    font-family: 'Montserrat', sans-serif;
    font-size: clamp(6rem, 16vw, 11rem);
    font-weight: 900;
    letter-spacing: -2px;
    background: linear-gradient(135deg, #FF5722 0%, #FFC107 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 10px 30px rgba(255, 87, 34, 0.3);
  }

  .hazard-stripe-badge {
    background: repeating-linear-gradient(
      -45deg,
      #FFC107,
      #FFC107 12px,
      #1e293b 12px,
      #1e293b 24px
    );
    color: #ffffff;
    font-weight: 800;
    font-size: 0.75rem;
    letter-spacing: 1.5px;
    padding: 6px 16px;
    border-radius: 6px;
    display: inline-block;
    text-transform: uppercase;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
  }

  .construction-scene {
    position: relative;
    width: 100%;
    max-width: 420px;
    height: 190px;
    margin: 0 auto 1.5rem auto;
    overflow: visible;
  }

  .steel-beam {
    position: absolute;
    bottom: 25px;
    left: 10%;
    width: 80%;
    height: 16px;
    background: linear-gradient(to bottom, #94a3b8, #475569);
    border-radius: 3px;
    box-shadow: 0 8px 15px rgba(0,0,0,0.5);
  }

  .steel-beam::before {
    content: '';
    position: absolute;
    top: -6px;
    left: 0;
    width: 100%;
    height: 6px;
    background: #cbd5e1;
    border-radius: 2px;
  }

  .steel-beam::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 0;
    width: 100%;
    height: 6px;
    background: #334155;
    border-radius: 2px;
  }

  .crane-cable {
    position: absolute;
    top: -40px;
    right: 25%;
    width: 2px;
    height: 90px;
    background: #f59e0b;
    animation: cableSway 3s ease-in-out infinite alternate;
    transform-origin: top center;
  }

  .crane-hook {
    position: absolute;
    bottom: -10px;
    left: -6px;
    font-size: 16px;
    color: #f59e0b;
  }

  .engineer-character {
    position: absolute;
    bottom: 40px;
    left: 20%;
    width: 70px;
    height: 80px;
    animation: engineerWalkAndFall 7s cubic-bezier(0.45, 0.05, 0.55, 0.95) infinite;
    transform-origin: bottom center;
  }

  .traffic-cone {
    position: absolute;
    bottom: 37px;
    left: 60%;
    width: 28px;
    height: 32px;
    animation: coneShake 7s ease infinite;
  }

  .speech-bubble {
    position: absolute;
    top: -30px;
    left: 10%;
    background: #ffffff;
    color: #0f172a;
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    animation: speechFade 7s ease infinite;
  }

  .speech-bubble::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 20px;
    border-width: 6px 6px 0;
    border-style: solid;
    border-color: #ffffff transparent;
    display: block;
    width: 0;
  }

  @keyframes engineerWalkAndFall {
    0% { left: 15%; transform: translateY(0) rotate(0deg); }
    35% { left: 52%; transform: translateY(-3px) rotate(-4deg); }
    40% { left: 55%; transform: translateY(-8px) rotate(20deg); }
    45% { left: 58%; transform: translateY(12px) rotate(75deg); }
    50%, 80% { left: 60%; transform: translateY(85px) rotate(180deg) scale(0.85); opacity: 0; }
    81% { left: 15%; transform: translateY(-50px) rotate(0deg); opacity: 0; }
    85%, 100% { left: 15%; transform: translateY(0) rotate(0deg); opacity: 1; }
  }

  @keyframes coneShake {
    0%, 39% { transform: scale(1); }
    40% { transform: scale(1.15) rotate(15deg); }
    43% { transform: scale(0.95) rotate(-10deg); }
    48%, 100% { transform: scale(1) rotate(0deg); }
  }

  @keyframes speechFade {
    0%, 15% { opacity: 0; transform: translateY(5px); }
    20%, 38% { opacity: 1; transform: translateY(0); }
    42%, 100% { opacity: 0; transform: translateY(-5px); }
  }

  @keyframes cableSway {
    0% { transform: rotate(-5deg); }
    100% { transform: rotate(5deg); }
  }

  .pmo-status-box {
    background: rgba(15, 23, 42, 0.7);
    border: 1px dashed rgba(255, 193, 7, 0.4);
    border-radius: 12px;
    backdrop-filter: blur(8px);
  }
</style>

<div class="blueprint-overlay"></div>

<!-- Header Mínimo Corporativo -->
<header class="py-3 border-bottom border-white border-opacity-10 position-relative z-2">
  <div class="container d-flex justify-content-between align-items-center">
    <a href="./" class="d-inline-flex align-items-center text-decoration-none">
      <img src="img/LogoPMO.png" alt="PMO Solutions" height="42" class="me-2" style="mix-blend-mode: multiply;">
    </a>
    <a href="./" class="btn btn-outline-light btn-sm rounded-pill px-3">
      <i class="fas fa-home me-1"></i> Ir al Inicio
    </a>
  </div>
</header>

<!-- Contenido Central 404 -->
<main class="flex-grow-1 d-flex align-items-center py-5 position-relative z-1 error-container">
  <div class="container text-center">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8 col-xl-7">

        <!-- Badge de Advertencia de Obra -->
        <div class="mb-3">
          <span class="hazard-stripe-badge animate__animated animate__fadeInDown">
            <i class="fas fa-exclamation-triangle text-warning me-1"></i> CRITICAL PATH ERROR: 404_NOT_FOUND
          </span>
        </div>

        <!-- Código 404 -->
        <div class="error-code-wrapper animate__animated animate__zoomIn">
          <h1 class="error-code-number m-0">404</h1>
        </div>

        <!-- Escenario Interactivo y Animado -->
        <div class="construction-scene">
          <!-- Cable de Grúa -->
          <div class="crane-cable">
            <i class="fas fa-anchor crane-hook"></i>
          </div>

          <!-- Diálogo Cómico -->
          <div class="speech-bubble">
            ¡Revisen el Primavera P6! 📋
          </div>

          <!-- Ingeniero Animado -->
          <div class="engineer-character">
            <svg viewBox="0 0 100 120" width="100%" height="100%">
              <ellipse cx="50" cy="32" rx="26" ry="15" fill="#FF5722"/>
              <path d="M24 32 C24 16, 76 16, 76 32 Z" fill="#FF9800"/>
              <rect x="20" y="30" width="60" height="6" rx="3" fill="#E65100"/>
              <rect x="44" y="16" width="12" height="16" fill="#FFA726"/>
              <circle cx="50" cy="48" r="14" fill="#ffdbac"/>
              <rect x="40" y="44" width="20" height="7" rx="3" fill="#0284c7" opacity="0.8"/>
              <line x1="36" y1="47" x2="64" y2="47" stroke="#334155" stroke-width="2"/>
              <path d="M32 62 L68 62 L74 95 L26 95 Z" fill="#00509E"/>
              <rect x="36" y="65" width="28" height="6" fill="#FACC15"/>
              <rect x="34" y="78" width="32" height="6" fill="#FACC15"/>
              <ellipse cx="40" cy="100" rx="9" ry="5" fill="#1e293b"/>
              <ellipse cx="60" cy="100" rx="9" ry="5" fill="#1e293b"/>
            </svg>
          </div>

          <!-- Cono de Tráfico -->
          <div class="traffic-cone">
            <svg viewBox="0 0 40 50" width="100%" height="100%">
              <polygon points="20,2 6,42 34,42" fill="#FF5722"/>
              <polygon points="20,14 11,32 29,32" fill="#FFFFFF"/>
              <polygon points="20,22 14,30 26,30" fill="#FF5722"/>
              <rect x="2" y="42" width="36" height="6" rx="2" fill="#1e293b"/>
            </svg>
          </div>

          <!-- Viga de Acero -->
          <div class="steel-beam"></div>
        </div>

        <!-- Mensaje Principal -->
        <h2 class="fw-bold text-white mb-2 animate__animated animate__fadeInUp" style="font-size: clamp(1.4rem, 3.5vw, 2rem);">
          ¡Oops! Ocurrió un desvío no planificado en la obra.
        </h2>
        <p class="text-light opacity-75 mb-4 mx-auto" style="max-width: 580px; font-size: 1rem;">
          Parece que la página que buscas no estaba en el cronograma de ruta crítica, fue demolida o cambió de ubicación contractual.
        </p>

        <!-- Diagnóstico Técnico Humorístico PMO -->
        <div class="pmo-status-box p-3 mb-4 mx-auto text-start" style="max-width: 500px;">
          <div class="row g-2 text-center text-md-start small">
            <div class="col-6 col-md-4">
              <span class="text-secondary d-block">Índice SPI:</span>
              <span class="fw-bold text-danger font-monospace"><i class="fas fa-times-circle me-1"></i> 0.00 (Demora)</span>
            </div>
            <div class="col-6 col-md-4">
              <span class="text-secondary d-block">Dictamen JRD:</span>
              <span class="fw-bold text-warning font-monospace">No Compensable</span>
            </div>
            <div class="col-12 col-md-4">
              <span class="text-secondary d-block">Medida Correctiva:</span>
              <span class="fw-bold text-success font-monospace">Reubicar Enlace</span>
            </div>
          </div>
        </div>

        <!-- Botones de Acción (CTAs de Rescate) -->
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mb-4">
          <a href="./" class="btn btn-warning btn-lg fw-bold rounded-pill px-4 py-3 shadow text-dark hvr-grow">
            <i class="fas fa-hard-hat me-2"></i> Volver al Proyecto Principal (Inicio)
          </a>
          <a href="https://wa.me/51944276649?text=Hola%20PMO%20Solutions,%20encontr%C3%A9%20un%20enlace%20roto%20(Error%20404)%20en%20el%20sitio%20web" 
             target="_blank" 
             rel="noopener noreferrer" 
             class="btn btn-outline-light btn-lg rounded-pill px-4 py-3 hvr-grow">
            <i class="fab fa-whatsapp text-success me-2 fs-5 align-middle"></i> Reportar Enlace al Soporte
          </a>
        </div>

        <!-- Accesos Directos -->
        <div class="pt-3 border-top border-white border-opacity-10">
          <p class="small text-secondary mb-2">O accede directamente a nuestros programas de alta especialización:</p>
          <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="capacitaciones" class="badge bg-white bg-opacity-10 text-white text-decoration-none p-2 rounded-pill hover-badge">
              <i class="fas fa-graduation-cap me-1 text-warning"></i> Catálogo 2026
            </a>
            <a href="nec4" class="badge bg-white bg-opacity-10 text-white text-decoration-none p-2 rounded-pill hover-badge">
              <i class="fas fa-file-contract me-1 text-info"></i> Contratos NEC4
            </a>
            <a href="primavera-p6" class="badge bg-white bg-opacity-10 text-white text-decoration-none p-2 rounded-pill hover-badge">
              <i class="fas fa-chart-gantt me-1 text-success"></i> Oracle Primavera P6
            </a>
            <a href="dab-jrd" class="badge bg-white bg-opacity-10 text-white text-decoration-none p-2 rounded-pill hover-badge">
              <i class="fas fa-gavel me-1 text-danger"></i> Dispute Boards (JRD)
            </a>
            <a href="contacto" class="badge bg-white bg-opacity-10 text-white text-decoration-none p-2 rounded-pill hover-badge">
              <i class="fas fa-envelope me-1 text-warning"></i> Contacto
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
</main>

<!-- Footer Simple -->
<footer class="py-3 text-center border-top border-white border-opacity-10 position-relative z-2">
  <div class="container">
    <small class="text-white-50">
      &copy; <?= date('Y') ?> PMO Solutions S.A.C. Todos los derechos reservados. | RUC: 20600000000
    </small>
  </div>
</footer>

