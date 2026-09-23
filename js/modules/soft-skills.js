/**
 * PMO SOLUTIONS — Módulo de Evaluación de Habilidades Blandas (soft-skills.js)
 *
 * Gestiona la interfaz interactiva de evaluación situacional:
 *  - Renderizado dinámico de preguntas desde la API del catálogo
 *  - Seguimiento de progreso en tiempo real
 *  - Envío de respuestas y renderizado del reporte con gráfico radar
 *  - Exportación del reporte a PDF (jsPDF cargado de forma lazy)
 *  - Persistencia del progreso en localStorage (anti-pérdida)
 *
 * @module soft-skills
 * @requires PMO.utils, PMO.http
 */

// Obtener URL versionada de utils.js desde meta tag o fallback seguro
const utilsMetaUrl = document.querySelector('meta[name="asset-utils"]')?.getAttribute('content') || '/js/core/utils.js';
const { PMO } = await import(utilsMetaUrl);

const utils = PMO.utils;
const http  = PMO.http;

// ─── Estado de la evaluación ─────────────────────────────────────────────────
const state = {
    catalog:         null,      // Catálogo cargado de la API
    currentComp:     0,         // Índice de competencia actual (0-3)
    currentQuestion: 0,         // Índice de pregunta actual dentro de la competencia
    answers:         {},        // {competency: {questionId: optionChosen}}
    startTime:       null,      // Timestamp de inicio
    participantData: {},        // Datos del formulario del participante
    sessionId:       null,      // ID de sesión para recuperación
};

// ─── Constantes ──────────────────────────────────────────────────────────────
const API_CATALOG = '/api/evaluacion-habilidades/catalogo';
const API_SUBMIT  = '/api/evaluacion-habilidades';
const STORAGE_KEY = 'pmo_softskills_progress';

// ─── Referencias DOM ─────────────────────────────────────────────────────────
let els = {};

// ─────────────────────────────────────────────────────────────────────────────
// INICIALIZACIÓN
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Inicializa el módulo de evaluación.
 * @param {Object} options - Opciones de configuración
 */
async function init(options = {}) {

    // Resolver referencias DOM
    els = {
        container:      document.getElementById('soft-skills-container'),
        stepParticipant:document.getElementById('step-participant'),
        stepEvaluation: document.getElementById('step-evaluation'),
        stepResults:    document.getElementById('step-results'),
        progressBar:    document.getElementById('eval-progress-bar'),
        progressText:   document.getElementById('eval-progress-text'),
        questionCard:   document.getElementById('question-card'),
        competencyName: document.getElementById('current-competency-name'),
        competencyIcon: document.getElementById('current-competency-icon'),
        resultsContainer: document.getElementById('results-container'),
        radarCanvas:    document.getElementById('radar-chart'),
        btnStart:       document.getElementById('btn-start-eval'),
        btnNext:        document.getElementById('btn-next'),
        btnPrev:        document.getElementById('btn-prev'),
        formParticipant: document.getElementById('form-participant'),
        btnDownloadPdf:  document.getElementById('btn-download-pdf'),
    };

    if (!els.container) return; // No estamos en la página de evaluación

    // Recuperar progreso guardado
    state.sessionId = utils?.generateSessionId() || 'sess_' + Date.now();
    const savedProgress = utils?.getWithTTL(STORAGE_KEY);
    if (savedProgress) {
        showRecoveryPrompt(savedProgress);
    }

    // Cargar catálogo desde la API (cacheado en el servidor)
    await loadCatalog();

    // Bind de eventos principales
    bindEvents();
}

// ─────────────────────────────────────────────────────────────────────────────
// CARGA DEL CATÁLOGO
// ─────────────────────────────────────────────────────────────────────────────

async function loadCatalog() {
    showLoader('Cargando evaluación...');
    try {
        const response = await http.get(API_CATALOG);
        if (response.success) {
            state.catalog = response.catalog;
            hideLoader();
        } else {
            throw new Error('No se pudo obtener el catálogo de competencias.');
        }
    } catch (err) {
        hideLoader();
        showError('Error al cargar la evaluación. Por favor, recarga la página.');
        console.error('[SoftSkills] Error cargando catálogo:', err);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FLUJO DE EVALUACIÓN
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Inicia la evaluación después de validar el formulario del participante.
 */
function startEvaluation() {
    const form    = els.formParticipant;
    const nombre  = form.querySelector('[name="nombre_completo"]')?.value?.trim();
    const email   = form.querySelector('[name="email"]')?.value?.trim();

    if (!nombre || !email) {
        utils?.toast('Por favor completa tu nombre y correo para continuar.', 'warning');
        return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        utils?.toast('El correo electrónico no tiene un formato válido.', 'error');
        return;
    }

    // Guardar datos del participante
    state.participantData = {
        nombre_completo:  nombre,
        email:            email,
        cargo:            form.querySelector('[name="cargo"]')?.value?.trim() || '',
        empresa:          form.querySelector('[name="empresa"]')?.value?.trim() || '',
        tipo_evaluacion:  form.querySelector('[name="tipo_evaluacion"]')?.value || 'Individual',
    };

    state.startTime   = Date.now();
    state.currentComp = 0;
    state.currentQuestion = 0;
    state.answers     = {};

    // Inicializar estructura de respuestas
    Object.keys(state.catalog).forEach(comp => {
        state.answers[comp] = {};
    });

    showStep('evaluation');
    renderCurrentQuestion();
}

/**
 * Renderiza la pregunta actual en la UI.
 */
function renderCurrentQuestion() {
    const competencies = Object.keys(state.catalog);
    const compKey      = competencies[state.currentComp];
    const compData     = state.catalog[compKey];
    const question     = compData.questions[state.currentQuestion];
    const totalQ       = competencies.length * compData.question_count;
    const answeredQ    = state.currentComp * compData.question_count + state.currentQuestion;

    // Actualizar indicadores de competencia
    if (els.competencyName) els.competencyName.textContent = compData.label;
    if (els.competencyIcon) {
        els.competencyIcon.className = '';
        els.competencyIcon.className = compData.icon || 'fas fa-star';
        els.competencyIcon.style.color = compData.color;
    }

    // Actualizar barra de progreso
    const progress = Math.round((answeredQ / totalQ) * 100);
    if (els.progressBar) {
        els.progressBar.style.width = `${progress}%`;
        els.progressBar.setAttribute('aria-valuenow', progress);
    }
    if (els.progressText) {
        els.progressText.textContent = `Pregunta ${answeredQ + 1} de ${totalQ}`;
    }

    // Renderizar tarjeta de pregunta
    if (els.questionCard) {
        const questionId = state.currentQuestion + 1;
        const savedAnswer = state.answers[compKey]?.[questionId];

        els.questionCard.innerHTML = `
            <div class="question-header mb-4">
                <span class="badge bg-primary mb-2">Competencia: ${compData.label}</span>
                <h4 class="question-scenario fs-6 fw-semibold">${escapeHtml(question.scenario)}</h4>
            </div>
            <div class="question-options">
                ${question.options.map((opt, i) => `
                    <label class="option-card ${savedAnswer === (i + 1) ? 'selected' : ''}"
                           data-option="${i + 1}" role="radio"
                           aria-checked="${savedAnswer === (i + 1)}"
                           tabindex="0">
                        <input type="radio" name="q_${compKey}_${questionId}"
                               value="${i + 1}"
                               ${savedAnswer === (i + 1) ? 'checked' : ''}
                               class="visually-hidden">
                        <span class="option-letter">${String.fromCharCode(65 + i)}</span>
                        <span class="option-text">${escapeHtml(opt.text)}</span>
                    </label>
                `).join('')}
            </div>
        `;

        // Bind de selección de opción
        els.questionCard.querySelectorAll('.option-card').forEach(card => {
            card.addEventListener('click', () => selectOption(card, compKey, questionId));
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') selectOption(card, compKey, questionId);
            });
        });
    }

    // Actualizar botones de navegación
    const isFirst = state.currentComp === 0 && state.currentQuestion === 0;
    const isLast  = state.currentComp === competencies.length - 1
                 && state.currentQuestion === compData.question_count - 1;

    if (els.btnPrev) els.btnPrev.style.display = isFirst ? 'none' : 'inline-flex';
    if (els.btnNext) {
        els.btnNext.textContent = isLast ? '🏁 Finalizar Evaluación' : 'Siguiente →';
        els.btnNext.classList.toggle('btn-success', isLast);
        els.btnNext.classList.toggle('btn-primary', !isLast);
    }

    // Guardar progreso en localStorage
    saveProgress();
}

/**
 * Selecciona una opción y marca visualmente la tarjeta.
 */
function selectOption(card, compKey, questionId) {
    const optionNum = parseInt(card.dataset.option, 10);

    // Actualizar estado
    state.answers[compKey][questionId] = optionNum;

    // Actualizar UI
    const allCards = els.questionCard.querySelectorAll('.option-card');
    allCards.forEach(c => {
        c.classList.remove('selected');
        c.setAttribute('aria-checked', 'false');
        c.querySelector('input')?.removeAttribute('checked');
    });

    card.classList.add('selected');
    card.setAttribute('aria-checked', 'true');
    const radio = card.querySelector('input[type="radio"]');
    if (radio) {
        radio.checked = true;
        radio.setAttribute('checked', 'true');
    }
}

/**
 * Avanza a la siguiente pregunta o envía la evaluación.
 */
function nextQuestion() {
    const competencies = Object.keys(state.catalog);
    const compKey      = competencies[state.currentComp];
    const compData     = state.catalog[compKey];
    const questionId   = state.currentQuestion + 1;

    // Validar que se seleccionó una opción
    if (!state.answers[compKey]?.[questionId]) {
        utils?.toast('Por favor selecciona una respuesta antes de continuar.', 'warning');
        const card = els.questionCard;
        if (card) {
            card.classList.add('shake');
            setTimeout(() => card.classList.remove('shake'), 500);
        }
        return;
    }

    const isLastQuestion  = state.currentQuestion >= compData.question_count - 1;
    const isLastComp      = state.currentComp >= competencies.length - 1;

    if (isLastQuestion && isLastComp) {
        // Enviar evaluación
        submitEvaluation();
    } else if (isLastQuestion) {
        state.currentComp++;
        state.currentQuestion = 0;
        renderCurrentQuestion();
    } else {
        state.currentQuestion++;
        renderCurrentQuestion();
    }
}

/**
 * Retrocede a la pregunta anterior.
 */
function prevQuestion() {
    const competencies = Object.keys(state.catalog);
    const compData     = state.catalog[competencies[state.currentComp]];

    if (state.currentQuestion > 0) {
        state.currentQuestion--;
    } else if (state.currentComp > 0) {
        state.currentComp--;
        const prevComp = state.catalog[competencies[state.currentComp]];
        state.currentQuestion = prevComp.question_count - 1;
    }

    renderCurrentQuestion();
}

// ─────────────────────────────────────────────────────────────────────────────
// ENVÍO Y REPORTE
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Envía la evaluación al servidor y renderiza el reporte.
 */
async function submitEvaluation() {
    showLoader('Calculando tu reporte personalizado...');
    if (els.btnNext) els.btnNext.disabled = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('[name="csrf_token"]')?.value || '';
        const payload = {
            ...state.participantData,
            answers:     state.answers,
            website_hp:  '', // Honeypot vacío
            csrf_token:  csrfToken,
        };

        const response = await http.post(API_SUBMIT, payload);

        if (response.success) {
            // Limpiar progreso guardado
            localStorage.removeItem(STORAGE_KEY);

            // Renderizar resultados
            hideLoader();
            showStep('results');
            await renderResults(response.report, response.codigo_evaluacion);
        } else {
            throw new Error(response.message || 'Error al procesar la evaluación.');
        }

    } catch (err) {
        hideLoader();
        if (els.btnNext) els.btnNext.disabled = false;
        utils?.toast(err.message || 'Ocurrió un error. Por favor intenta nuevamente.', 'error');
        console.error('[SoftSkills] Error al enviar evaluación:', err);
    }
}

/**
 * Renderiza el reporte de resultados con gráfico radar.
 * @param {Object} report - Reporte generado por el servidor
 * @param {string} codigo - Código de evaluación
 */
async function renderResults(report, codigo) {
    const summary = report.summary;

    if (els.resultsContainer) {
        els.resultsContainer.innerHTML = `
            <div class="result-header text-center mb-5">
                <div class="result-badge mb-3" style="background-color:${summary.maturity_color}20; border:2px solid ${summary.maturity_color}; border-radius:50%; width:120px; height:120px; display:flex; align-items:center; justify-content:center; margin:0 auto;">
                    <span style="font-size:3rem;">${summary.maturity_icon}</span>
                </div>
                <h2 class="fw-bold">${summary.global_score}<span class="fs-4 text-muted">/100</span></h2>
                <span class="badge fs-6 px-4 py-2" style="background-color:${summary.maturity_color}">
                    ${summary.maturity_level}
                </span>
                <p class="text-muted mt-2">Código: <strong>${codigo}</strong></p>
            </div>

            <div class="row g-4 mb-4">
                ${Object.entries(summary.scores).map(([comp, score]) => `
                    <div class="col-sm-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted mb-2">${formatCompetencyLabel(comp)}</h6>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="progress flex-grow-1" style="height:10px;">
                                        <div class="progress-bar" role="progressbar"
                                             style="width:${score}%; background-color:${utils?.maturityColor(getLevel(score)) || '#1a5b82'};"
                                             aria-valuenow="${score}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="fw-bold" style="min-width:40px;">${score}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="text-success mb-3"><i class="fas fa-star me-2"></i>Fortalezas</h5>
                            ${summary.strengths.map(s => `
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-success me-2">${s.score}</span>
                                    <span>${s.label}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="text-warning mb-3"><i class="fas fa-chart-line me-2"></i>Oportunidades de Mejora</h5>
                            ${summary.areas_to_improve.map(a => `
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-warning text-dark me-2">${a.score}</span>
                                    <span>${a.label}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-graduation-cap fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-primary">Ruta de Especialización Sugerida & Recomendaciones</h5>
                            <small class="text-muted">Cursos de PMO Solutions diseñados para potenciar tus competencias técnicas y contractuales</small>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-2 mb-3">
                        ${summary.recommendations.map(rec => `
                            <div class="p-3 rounded-3 border bg-light bg-opacity-50 d-flex align-items-start gap-3">
                                <i class="fas fa-check-circle text-success fs-5 mt-1 flex-shrink-0"></i>
                                <div class="text-secondary" style="font-size: 0.95rem; line-height: 1.5;">${rec}</div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="alert alert-primary bg-opacity-10 border-0 rounded-3 mb-0 d-flex align-items-center gap-3">
                        <i class="fas fa-play-circle text-primary fs-3 flex-shrink-0"></i>
                        <div class="small">
                            <strong>Modalidad 100% Virtual Online:</strong> Todas nuestras capacitaciones cuentan con clases grabadas en alta definición, acceso digital 24/7 sin horarios fijos, plantillas editables y casuística real de proyectos.
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <button id="btn-download-pdf" class="btn btn-primary btn-lg me-3 px-4 shadow-sm">
                    <i class="fas fa-file-pdf me-2"></i>Descargar Informe Ejecutivo PDF
                </button>
                <a href="/capacitaciones" class="btn btn-outline-secondary btn-lg px-4 shadow-sm">
                    <i class="fas fa-graduation-cap me-2"></i>Ver Catálogo de Capacitaciones
                </a>
            </div>
        `;

        // Bind del botón PDF (carga jsPDF lazy)
        document.getElementById('btn-download-pdf')?.addEventListener('click', () => {
            downloadPdf(report, codigo);
        });
    }

    // Renderizar gráfico radar con Chart.js (lazy load)
    await renderRadarChart(summary.radar_data);
}

/**
 * Renderiza el gráfico radar con Chart.js cargado localmente (con fallback seguro).
 */
async function renderRadarChart(radarData) {
    const canvas = document.getElementById('radar-chart');
    if (!canvas) return;

    // Cargar Chart.js local versionado si no está definido en el contexto global
    if (typeof Chart === 'undefined') {
        const chartMetaUrl = document.querySelector('meta[name="asset-chartjs"]')?.getAttribute('content') || '/js/vendor/chart.umd.min.js';
        try {
            await new Promise((resolve, reject) => {
                const script   = document.createElement('script');
                script.src     = chartMetaUrl;
                script.onload  = resolve;
                script.onerror = () => reject(new Error('No se pudo cargar la librería local Chart.js (' + chartMetaUrl + ').'));
                document.head.appendChild(script);
            });
        } catch (err) {
            console.warn('[SoftSkills] Error al cargar Chart.js:', err.message);
            const parent = canvas.parentElement;
            if (parent) {
                parent.innerHTML = '<div class="alert alert-warning py-2 small"><i class="fas fa-chart-pie me-1"></i> El gráfico no pudo cargarse en este momento. Los resultados numéricos se detallan a continuación.</div>';
            }
            return;
        }
    }

    try {
        new Chart(canvas, {
            type: 'radar',
            data: radarData,
            options: {
                responsive:          true,
                maintainAspectRatio: true,
                scales: {
                    r: {
                        min:  0,
                        max:  100,
                        ticks: { stepSize: 25, font: { size: 11 } },
                        pointLabels: { font: { size: 13, weight: 'bold' } },
                    },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.raw}/100 pts`,
                        },
                    },
                },
            },
        });
    } catch (chartErr) {
        console.warn('[SoftSkills] Error al instanciar Chart.js:', chartErr.message);
    }
}

/**
 * Descarga el informe ejecutivo en PDF estilizado con branding corporativo de PMO Solutions.
 */
async function downloadPdf(report, codigo) {
    utils?.toast('Generando Informe Ejecutivo PDF...', 'info', 2000);

    try {
        if (typeof jspdf === 'undefined' && typeof window.jspdf === 'undefined') {
            const jspdfMetaUrl = document.querySelector('meta[name="asset-jspdf"]')?.getAttribute('content') || '/js/vendor/jspdf.umd.min.js';
            await new Promise((resolve, reject) => {
                const script   = document.createElement('script');
                script.src     = jspdfMetaUrl;
                script.onload  = resolve;
                script.onerror = () => reject(new Error('No se pudo cargar la librería local jsPDF (' + jspdfMetaUrl + ').'));
                document.head.appendChild(script);
            });
        }

        const jsPdfClass = (window.jspdf && window.jspdf.jsPDF) ? window.jspdf.jsPDF : (typeof jsPDF !== 'undefined' ? jsPDF : null);
        if (!jsPdfClass) {
            throw new Error('Librería jsPDF no disponible.');
        }

        // Cargar logotipo de PMO Solutions de forma asíncrona
        const logoImg = await new Promise((resolve) => {
            const img = new Image();
            img.crossOrigin = 'Anonymous';
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
            img.src = '/img/LogoPMO.png';
        });

        // Capturar canvas del gráfico Radar si fue renderizado
        let radarImgData = null;
        try {
            const radarCanvas = document.getElementById('radar-chart');
            if (radarCanvas) {
                radarImgData = radarCanvas.toDataURL('image/png');
            }
        } catch (canvasErr) {
            console.warn('[SoftSkills] No se pudo exportar el gráfico radar:', canvasErr);
        }

        const doc     = new jsPdfClass({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const summary = report.summary;

        // Helper de conversión hex a RGB
        const hexToRgb = (hex) => {
            if (!hex) return [26, 91, 130];
            const clean = hex.replace('#', '');
            const parsed = clean.length === 3 ? clean.split('').map(c => c + c).join('') : clean;
            const num = parseInt(parsed, 16);
            return [(num >> 16) & 255, (num >> 8) & 255, num & 255];
        };

        // Paleta Corporativa PMO Solutions
        const cNavy    = [15, 43, 72];     // #0f2b48
        const cBlue    = [26, 91, 130];    // #1a5b82
        const cGold    = [243, 156, 18];   // #f39c12
        const cDark    = [30, 41, 59];     // #1e293b
        const cMuted   = [100, 116, 139];  // #64748b
        const cBgLight = [248, 250, 252];  // #f8fafc
        const cBorder  = [226, 232, 240];  // #e2e8f0

        // =========================================================================
        // 1. CABECERA CORPORATIVA DE ÉLITE (HEADER)
        // =========================================================================
        doc.setFillColor(...cNavy);
        doc.rect(0, 0, 210, 28, 'F');

        doc.setFillColor(...cGold);
        doc.rect(0, 28, 210, 2, 'F');

        // Renderizar Logotipo
        if (logoImg) {
            try {
                doc.addImage(logoImg, 'PNG', 14, 4, 20, 20);
            } catch {
                doc.setFillColor(...cGold);
                doc.roundedRect(14, 5, 18, 18, 2, 2, 'F');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(8.5);
                doc.setTextColor(...cNavy);
                doc.text('PMO', 23, 15, { align: 'center' });
            }
        } else {
            doc.setFillColor(...cGold);
            doc.roundedRect(14, 5, 18, 18, 2, 2, 'F');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(8.5);
            doc.setTextColor(...cNavy);
            doc.text('PMO', 23, 15, { align: 'center' });
        }

        // Títulos de Marca
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(14);
        doc.setTextColor(255, 255, 255);
        doc.text('PMO SOLUTIONS', 38, 11);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(...cGold);
        doc.text('CONSTRUIMOS SOLUCIONES · CONSULTORÍA & CAPACITACIÓN', 38, 16);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(203, 213, 225);
        doc.text('Informe Ejecutivo de Competencias & Ruta de Especialización Técnica', 38, 22);

        // Badge de Código de Verificación en Cabecera
        doc.setFillColor(...cBlue);
        doc.roundedRect(150, 6, 46, 16, 2, 2, 'F');
        doc.setDrawColor(...cGold);
        doc.setLineWidth(0.35);
        doc.roundedRect(150, 6, 46, 16, 2, 2, 'S');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6.5);
        doc.setTextColor(...cGold);
        doc.text('CÓDIGO OFICIAL', 173, 11, { align: 'center' });

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.setTextColor(255, 255, 255);
        doc.text(codigo, 173, 17, { align: 'center' });

        // =========================================================================
        // 2. TARJETA DE DATOS DEL PARTICIPANTE
        // =========================================================================
        doc.setFillColor(...cBgLight);
        doc.setDrawColor(...cBorder);
        doc.setLineWidth(0.3);
        doc.roundedRect(14, 34, 182, 22, 2.5, 2.5, 'FD');

        doc.setFillColor(...cBlue);
        doc.rect(14, 34, 3, 22, 'F');

        // Columna 1
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6);
        doc.setTextColor(...cMuted);
        doc.text('PARTICIPANTE:', 21, 39.5);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor(...cDark);
        doc.text(report.participant.nombre || 'Participante', 21, 44.5);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6);
        doc.setTextColor(...cMuted);
        doc.text('CARGO / ROL:', 21, 49.5);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.8);
        doc.setTextColor(...cDark);
        doc.text(report.participant.cargo || 'Especialista / Ingeniero de Proyectos', 21, 53.5);

        // Columna 2
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6);
        doc.setTextColor(...cMuted);
        doc.text('EMPRESA / INSTITUCIÓN:', 90, 39.5);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.8);
        doc.setTextColor(...cDark);
        doc.text(report.participant.empresa || 'Sector Construcción e Infraestructura', 90, 44.5);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6);
        doc.setTextColor(...cMuted);
        doc.text('TIPO DE EVALUACIÓN:', 90, 49.5);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.8);
        doc.setTextColor(...cDark);
        doc.text(report.participant.tipo_evaluacion || 'Evaluación de Diagnóstico Individual', 90, 53.5);

        // Columna 3
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6);
        doc.setTextColor(...cMuted);
        doc.text('FECHA DE EMISIÓN:', 150, 39.5);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.8);
        doc.setTextColor(...cDark);
        doc.text(new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'short', day: 'numeric' }), 150, 44.5);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6);
        doc.setTextColor(...cMuted);
        doc.text('ESTADO DE INFORME:', 150, 49.5);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.8);
        doc.setTextColor(39, 174, 96);
        doc.text('Completado / Verificado', 150, 53.5);

        // =========================================================================
        // 3. SECCIÓN 1: RESULTADOS GLOBALES Y MAPA DE COMPETENCIAS
        // =========================================================================
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9.5);
        doc.setTextColor(...cNavy);
        doc.text('1. RESULTADOS GLOBALES Y MADUREZ DE COMPETENCIAS', 14, 61);

        doc.setDrawColor(...cGold);
        doc.setLineWidth(0.4);
        doc.line(14, 62.5, 196, 62.5);

        // Tarjeta Izquierda: Score & Desglose
        doc.setFillColor(...cBgLight);
        doc.setDrawColor(...cBorder);
        doc.setLineWidth(0.3);
        doc.roundedRect(14, 66, 92, 58, 2.5, 2.5, 'FD');

        // Score Global y Badge
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(22);
        doc.setTextColor(...cNavy);
        doc.text(String(summary.global_score), 20, 77);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.setTextColor(...cMuted);
        doc.text('/100 PTS', 36, 77);

        const matRgb = hexToRgb(summary.maturity_color);
        doc.setFillColor(...matRgb);
        doc.roundedRect(54, 67, 46, 8, 2, 2, 'F');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8);
        doc.setTextColor(255, 255, 255);
        doc.text(summary.maturity_level, 77, 72.5, { align: 'center' });

        doc.setDrawColor(...cBorder);
        doc.line(18, 81, 102, 81);

        // Desglose de 4 Competencias
        let barY = 87;
        Object.entries(summary.scores).forEach(([comp, score]) => {
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(7.2);
            doc.setTextColor(...cDark);
            doc.text(formatCompetencyLabel(comp), 20, barY);

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(7.2);
            doc.setTextColor(...cBlue);
            doc.text(`${score} pts`, 102, barY, { align: 'right' });

            // Barra de progreso
            doc.setFillColor(...cBorder);
            doc.roundedRect(20, barY + 1.2, 82, 2.2, 1, 1, 'F');

            doc.setFillColor(...cBlue);
            doc.roundedRect(20, barY + 1.2, Math.max(2, (score / 100) * 82), 2.2, 1, 1, 'F');

            barY += 8.2;
        });

        // Tarjeta Derecha: Gráfico Radar
        doc.setFillColor(...cBgLight);
        doc.setDrawColor(...cBorder);
        doc.roundedRect(110, 66, 86, 58, 2.5, 2.5, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(...cNavy);
        doc.text('MAPA MULTIAXIAL DE COMPETENCIAS', 115, 71);

        if (radarImgData) {
            try {
                doc.addImage(radarImgData, 'PNG', 116, 72, 74, 50);
            } catch {
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(8);
                doc.setTextColor(...cMuted);
                doc.text('Visualización gráfica registrada en el sistema.', 115, 95);
            }
        } else {
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8);
            doc.setTextColor(...cMuted);
            doc.text('Visualización de perfil disponible en plataforma web.', 115, 95);
        }

        // =========================================================================
        // 4. FORTALEZAS Y PRIORIDADES DE DESARROLLO
        // =========================================================================
        // Fortalezas (Verde)
        doc.setFillColor(240, 253, 244);
        doc.setDrawColor(187, 247, 208);
        doc.roundedRect(14, 127, 92, 20, 2, 2, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(22, 101, 52);
        doc.text('★ PRINCIPALES FORTALEZAS', 19, 132);

        let sY = 137;
        summary.strengths.slice(0, 2).forEach(s => {
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7);
            doc.setTextColor(...cDark);
            doc.text(`• ${s.label}:`, 19, sY);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(22, 101, 52);
            doc.text(`${s.score}/100 pts`, 58, sY);
            sY += 5;
        });

        // Prioridades de Desarrollo (Ámbar)
        doc.setFillColor(254, 249, 231);
        doc.setDrawColor(254, 215, 170);
        doc.roundedRect(110, 127, 86, 20, 2, 2, 'FD');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7);
        doc.setTextColor(180, 83, 9);
        doc.text('▲ PRIORIDADES DE DESARROLLO', 115, 132);

        let aY = 137;
        summary.areas_to_improve.slice(0, 2).forEach(a => {
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7);
            doc.setTextColor(...cDark);
            doc.text(`• ${a.label}:`, 115, aY);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(180, 83, 9);
            doc.text(`${a.score}/100 pts`, 154, aY);
            aY += 5;
        });

        // =========================================================================
        // 5. SECCIÓN 2: RECOMENDACIONES Y RUTA FORMATIVA PMO SOLUTIONS
        // =========================================================================
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9.5);
        doc.setTextColor(...cNavy);
        doc.text('2. RECOMENDACIONES Y RUTA DE ESPECIALIZACIÓN TÉCNICA', 14, 153);

        doc.setDrawColor(...cGold);
        doc.setLineWidth(0.4);
        doc.line(14, 154.5, 196, 154.5);

        let recY = 158;
        summary.recommendations.forEach((rec, idx) => {
            const lines = doc.splitTextToSize(rec, 170);
            const boxH = Math.max(11, lines.length * 3.6 + 4);

            doc.setFillColor(...cBgLight);
            doc.setDrawColor(...cBorder);
            doc.setLineWidth(0.25);
            doc.roundedRect(14, recY, 182, boxH, 1.8, 1.8, 'FD');

            // Indicador lateral alternado (Dorado / Azul)
            doc.setFillColor(...(idx % 2 === 0 ? cGold : cBlue));
            doc.rect(14, recY, 2.5, boxH, 'F');

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7.2);
            doc.setTextColor(...cDark);
            doc.text(lines, 20, recY + 3.8);

            recY += boxH + 2;
        });

        // Banner informativo de modalidad 100% Virtual Online
        const bannerY = Math.min(252, Math.max(recY + 2, 238));
        doc.setFillColor(241, 245, 249);
        doc.setDrawColor(...cBlue);
        doc.setLineWidth(0.3);
        doc.roundedRect(14, bannerY, 182, 19, 2, 2, 'FD');

        doc.setFillColor(...cGold);
        doc.rect(14, bannerY, 3, 19, 'F');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.2);
        doc.setTextColor(...cNavy);
        doc.text('MODALIDAD DE ESTUDIO: 100% VIRTUAL ONLINE CON CLASES GRABADAS EN ALTA DEFINICIÓN', 20, bannerY + 5.5);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6.6);
        doc.setTextColor(...cMuted);
        const bannerDesc = 'Acceso digital 24/7 permanente a los contenidos, resolución de casuística real de proyectos de infraestructura, plantillas y herramientas editables listas para aplicación profesional en obra y gestión contractual.';
        const bannerLines = doc.splitTextToSize(bannerDesc, 172);
        doc.text(bannerLines, 20, bannerY + 10);

        // =========================================================================
        // 6. PIE DE PÁGINA CORPORATIVO (FOOTER)
        // =========================================================================
        doc.setFillColor(...cNavy);
        doc.rect(0, 278, 210, 19, 'F');

        doc.setFillColor(...cGold);
        doc.rect(0, 277.5, 210, 0.5, 'F');

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6.8);
        doc.setTextColor(255, 255, 255);
        doc.text('PMO Solutions · www.pmo-solutions.com', 14, 284);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(6.2);
        doc.setTextColor(203, 213, 225);
        doc.text('Av. Javier Prado 757, piso 10, Magdalena, Lima · comercial@pmo-solutions.com · WhatsApp: +51 944 276 649', 14, 289);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(6.8);
        doc.setTextColor(...cGold);
        doc.text('Informe Ejecutivo de Competencias', 196, 284, { align: 'right' });

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(5.8);
        doc.setTextColor(203, 213, 225);
        doc.text('Documento oficial emitido por el Sistema de Evaluación PMO Solutions', 196, 289, { align: 'right' });

        // Guardar y descargar archivo
        doc.save(`PMO-Reporte-HabilidadesBlandas-${codigo}.pdf`);
        utils?.toast('Informe Ejecutivo PDF descargado exitosamente.', 'success');

    } catch (err) {
        utils?.toast('Error al generar el PDF. Por favor intenta nuevamente.', 'error');
        console.error('[SoftSkills] Error generando PDF:', err);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────────

function showStep(step) {
    ['participant', 'evaluation', 'results'].forEach(s => {
        const el = document.getElementById(`step-${s}`);
        if (el) el.style.display = s === step ? 'block' : 'none';
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showLoader(msg = 'Cargando...') {
    const loader = document.getElementById('eval-loader');
    if (loader) {
        loader.querySelector('.loader-msg') && (loader.querySelector('.loader-msg').textContent = msg);
        loader.style.display = 'flex';
    }
}

function hideLoader() {
    const loader = document.getElementById('eval-loader');
    if (loader) loader.style.display = 'none';
}

function showError(msg) {
    const errorEl = document.getElementById('eval-error');
    if (errorEl) { errorEl.textContent = msg; errorEl.style.display = 'block'; }
    utils?.toast(msg, 'error', 8000);
}

function showRecoveryPrompt(savedData) {
    // TODO: Mostrar banner de recuperación si existe progreso guardado
    console.info('[SoftSkills] Progreso anterior encontrado:', savedData);
}

function saveProgress() {
    utils?.setWithTTL(STORAGE_KEY, {
        currentComp:     state.currentComp,
        currentQuestion: state.currentQuestion,
        answers:         state.answers,
        participantData: state.participantData,
    }, 7200000); // 2 horas
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatCompetencyLabel(comp) {
    const labels = {
        comunicacion:          'Comunicación',
        trabajo_equipo:        'Trabajo en Equipo',
        resolucion_problemas:  'Resolución de Problemas',
        adaptabilidad:         'Adaptabilidad',
    };
    return labels[comp] || comp;
}

function getLevel(score) {
    if (score >= 80) return 'Sobresaliente';
    if (score >= 60) return 'Competente';
    if (score >= 40) return 'En Desarrollo';
    return 'Inicial';
}

function bindEvents() {
    els.formParticipant?.addEventListener('submit', (e) => {
        e.preventDefault();
    });

    els.btnStart?.addEventListener('click', startEvaluation);
    els.btnNext?.addEventListener('click', nextQuestion);
    els.btnPrev?.addEventListener('click', prevQuestion);

    // Teclas de flecha para navegar entre opciones
    document.addEventListener('keydown', (e) => {
        if (!els.stepEvaluation || els.stepEvaluation.style.display === 'none') return;
        if (e.key === 'ArrowRight' || e.key === 'Enter') nextQuestion();
        if (e.key === 'ArrowLeft')                        prevQuestion();
    });
}

// ─── Auto-init cuando el DOM esté listo ──────────────────────────────────────
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init());
} else {
    init();
}

export { init };

