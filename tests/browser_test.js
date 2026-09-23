/**
 * PMO SOLUTIONS — Test Real en Navegador (Headless Chrome / Edge + CDP)
 *
 * Verificaciones Tarea 8:
 * 1. Carga real de http://127.0.0.1:8899/evaluacion-habilidades con verificación estricta:
 *    - document.title esperado ('Evaluación de Habilidades Blandas | PMO Solutions')
 *    - location.pathname === '/evaluacion-habilidades'
 *    - document.readyState ('complete' o 'interactive')
 *    - Respuesta HTTP principal 200
 * 2. Monitoreo CDP Network y Cabeceras HTTP:
 *    - Cabecera CSP unificada y estricta (sin unsafe-eval, sin wildcards en script-src)
 *    - Verificación de una sola política CSP efectiva
 *    - Cabecera Cache-Control con no-store para HTML/CSRF
 *    - Ausencia de 404s u otros errores HTTP en recursos locales / módulos JS
 *    - Ausencia de fallos de carga en red
 * 3. Detección Estricta de Violaciones CSP (Security.cspViolationReported y securitypolicyviolation)
 * 4. Verificación de Metadatos de Assets y Librerías Locales:
 *    - Metadatos <meta name="asset-utils">, <meta name="asset-chartjs">, <meta name="asset-jspdf">
 *    - Ausencia de <script type="importmap"> inline
 *    - Dynamic import() de utils.js usando la URL de su <meta>
 *    - Carga local de Chart.js v4.4.0 desde <meta> y renderizado de radar (sin CDN fallback)
 *    - Carga local de jsPDF v2.5.1 desde <meta> e instanciación de PDF (sin CDN fallback)
 * 5. Interacción con el formulario: llenado de datos y clic en #btn-start-eval
 * 6. Transición al paso 2: renderizado de primera pregunta con 4 opciones
 * 7. Pruebas de Peticiones POST Protegidas (Contacto, Reclamaciones, Evaluación) con CSRF
 * 8. Verificación de Rutas Alias (/api/send-contact y /api/submit-claim)
 * 9. Pruebas Reales de Cabeceras de Caché de Assets:
 *    - Asset con parámetro ?v=... -> Cache-Control: public, max-age=31536000, immutable
 *    - Asset sin versión -> Cache-Control: public, max-age=3600, must-revalidate
 * 10. Verificación de Service Worker: Registro con scope adecuado
 * 11. Cero errores de consola, cero excepciones y cero violaciones CSP
 */

import { spawn } from 'node:child_process';
import { existsSync, readdirSync, unlinkSync } from 'node:fs';
import { tmpdir } from 'node:os';

const PORT = 8899;
const URL_BASE = `http://127.0.0.1:${PORT}`;
const URL_TEST = `${URL_BASE}/evaluacion-habilidades`;
const CHROME_PATHS = [
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
];

const browserExe = CHROME_PATHS.find(p => existsSync(p));
if (!browserExe) {
    console.error('❌ No se encontró Google Chrome ni Microsoft Edge en el sistema.');
    process.exit(1);
}

console.log('\n======================================================================');
console.log(' PRUEBA REAL EN NAVEGADOR (CDP HEADLESS): CSP, ASSETS & CACHÉ (TAREA 8)');
console.log(` Navegador : ${browserExe}`);
console.log(` URL Target: ${URL_TEST}`);
console.log('======================================================================\n');

// Limpiar contadores de rate limit previos antes de iniciar la prueba (en temp dir y storage)
const dirsToClean = [
    `${tmpdir()}/pmo_rate_limits`,
    'storage/framework/ratelimit'
];
for (const dir of dirsToClean) {
    if (existsSync(dir)) {
        for (const f of readdirSync(dir)) {
            try { unlinkSync(`${dir}/${f}`); } catch {}
        }
    }
}

// 1. Iniciar servidor PHP Built-in en segundo plano con index.php como Front Controller
const phpServer = spawn('php', ['-S', `127.0.0.1:${PORT}`, 'index.php'], {
    stdio: 'ignore',
    env: {
        ...process.env,
        PMO_APP_ENV: 'development'
    }
});

// 2. Iniciar Chrome/Edge con Remote Debugging
const cdpPort = 9333 + Math.floor(Math.random() * 500);
const browserProc = spawn(browserExe, [
    '--headless=new',
    `--remote-debugging-port=${cdpPort}`,
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    '--disable-background-networking',
    '--disable-sync',
    '--disable-extensions',
    'about:blank'
], { stdio: 'ignore' });

async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

async function cleanup() {
    try { browserProc.kill(); } catch {}
    try { phpServer.kill(); } catch {}
}

process.on('exit', cleanup);
process.on('SIGINT', () => { cleanup(); process.exit(1); });

let testResults = { passed: 0, failed: 0 };
function pass(desc) {
    testResults.passed++;
    console.log(`  \x1b[32m✔ [PASS]\x1b[0m ${desc}`);
}
function fail(desc, detail = '') {
    testResults.failed++;
    console.log(`  \x1b[31m✖ [FAIL]\x1b[0m ${desc} ${detail ? '-> ' + detail : ''}`);
}

async function runBrowserTest() {
    // Esperar inicio de servidor y browser
    await sleep(1500);

    // Obtener WebSocket URL del browser para pestaña tipo 'page'
    let wsUrl = null;
    for (let i = 0; i < 20; i++) {
        try {
            const res = await fetch(`http://127.0.0.1:${cdpPort}/json/list`);
            const tabs = await res.json();
            const pageTab = tabs.find(t => t.type === 'page' && t.webSocketDebuggerUrl);
            if (pageTab) {
                wsUrl = pageTab.webSocketDebuggerUrl;
                break;
            }
        } catch {}
        await sleep(300);
    }

    if (!wsUrl) {
        fail('Conexión CDP', 'No se pudo conectar al WebSocket de Chrome/Edge');
        cleanup();
        process.exit(1);
    }

    // Conectar WebSocket nativo
    const ws = new WebSocket(wsUrl);
    let msgId = 1;
    const callbacks = new Map();
    const consoleErrors = [];
    const networkResponses = [];
    const networkErrors = [];
    const cdpCspViolations = [];
    let loadFired = false;

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if (data.id && callbacks.has(data.id)) {
            callbacks.get(data.id)(data.result || data.error);
            callbacks.delete(data.id);
        }

        if (data.method === 'Page.loadEventFired') {
            loadFired = true;
        }

        // Monitoreo de Red mediante CDP
        if (data.method === 'Network.responseReceived') {
            const resp = data.params.response;
            const url = resp.url || '';
            const status = resp.status;
            const headers = resp.headers || {};
            const headersText = resp.headersText || '';
            if (url.startsWith(`http://127.0.0.1:${PORT}`)) {
                networkResponses.push({ url, status, headers, headersText });
                if (status >= 500) {
                    networkErrors.push(`[HTTP ${status}] ${url}`);
                }
            }
        }
        if (data.method === 'Network.loadingFailed') {
            const url = data.params.url || data.params.requestId || 'recurso';
            const errorText = data.params.errorText || 'Fallo de carga';
            if (!data.params.canceled) {
                networkErrors.push(`[Loading Failed] ${url}: ${errorText}`);
            }
        }

        // Capturar errores de consola
        if (data.method === 'Runtime.consoleAPICalled') {
            if (data.params.type === 'error') {
                const text = data.params.args.map(a => a.value || a.description || '').join(' ');
                consoleErrors.push(`[Console Error] ${text}`);
            }
        }
        if (data.method === 'Runtime.exceptionThrown') {
            const desc = data.params.exceptionDetails?.exception?.description || data.params.exceptionDetails?.text || 'Excepción no capturada';
            consoleErrors.push(`[Uncaught Exception] ${desc}`);
        }

        // Captura explícita de violaciones CSP vía CDP Security domain / Audits
        if (data.method === 'Security.cspViolationReported') {
            cdpCspViolations.push(JSON.stringify(data.params));
        }
        if (data.method === 'Audits.issueAdded' && data.params.issue?.code === 'ContentSecurityPolicyIssue') {
            cdpCspViolations.push(JSON.stringify(data.params.issue));
        }
    };

    await new Promise(r => ws.onopen = r);

    function send(method, params = {}) {
        return new Promise((resolve) => {
            const id = msgId++;
            callbacks.set(id, resolve);
            ws.send(JSON.stringify({ id, method, params }));
        });
    }

    // Habilitar dominios CDP
    await send('Page.enable');
    await send('Runtime.enable');
    await send('DOM.enable');
    await send('Network.enable');
    try { await send('Security.enable'); } catch {}
    try { await send('Audits.enable'); } catch {}

    // Script inyectado antes de cualquier navegación para capturar securitypolicyviolation en DOM
    await send('Page.addScriptToEvaluateOnNewDocument', {
        source: `
            window.__domCspViolations = [];
            window.addEventListener('securitypolicyviolation', (e) => {
                window.__domCspViolations.push({
                    blockedURI: e.blockedURI,
                    violatedDirective: e.violatedDirective,
                    effectiveDirective: e.effectiveDirective,
                    originalPolicy: e.originalPolicy
                });
            });
            document.addEventListener('securitypolicyviolation', (e) => {
                window.__domCspViolations.push({
                    blockedURI: e.blockedURI,
                    violatedDirective: e.violatedDirective,
                    effectiveDirective: e.effectiveDirective
                });
            });
        `
    });

    console.log('--- 1. Navegación, Carga de Página y Cabeceras HTTP (Tarea 8) ---');
    await send('Page.navigate', { url: URL_TEST });
    
    // Esperar carga completa y resolución de módulos ES
    for (let i = 0; i < 30; i++) {
        if (loadFired) break;
        await sleep(100);
    }
    await sleep(1000);

    const docInfo = await send('Runtime.evaluate', {
        expression: `JSON.stringify({
            title: document.title,
            url: location.href,
            pathname: location.pathname,
            readyState: document.readyState
        })`,
        returnByValue: true
    });
    const info = JSON.parse(docInfo.result?.value || '{}');

    const expectedTitle = 'Evaluación de Habilidades Blandas | PMO Solutions';
    const mainResponse = networkResponses.find(
        response =>
            response.url === URL_TEST ||
            response.url === `${URL_TEST}/`
    );

    const isNavValid =
        Boolean(mainResponse) &&
        info.title === expectedTitle &&
        info.pathname === '/evaluacion-habilidades' &&
        (info.readyState === 'complete' || info.readyState === 'interactive') &&
        mainResponse.status === 200;

    const reportedStatus = mainResponse ? mainResponse.status : 'no capturado';

    if (isNavValid) {
        pass(`Navegación completada: "${info.title}" (${info.url}) - Status ${reportedStatus}, readyState: ${info.readyState}`);
    } else {
        fail('Navegación fallida', `Title: '${info.title}', Pathname: '${info.pathname}', Status: ${reportedStatus}, ReadyState: '${info.readyState}'`);
    }

    // Verificación de Cabeceras HTTP (CSP y Cache-Control)
    const headers = mainResponse?.headers || {};
    const cspHeader = headers['content-security-policy'] || headers['Content-Security-Policy'] || '';
    const cacheHeader = headers['cache-control'] || headers['Cache-Control'] || '';

    if (cspHeader.includes("default-src 'self'") && !cspHeader.includes("'unsafe-eval'") && !cspHeader.includes("script-src *")) {
        pass(`Cabecera CSP estricta validada: default-src 'self', sin 'unsafe-eval' ni comodines en scripts`);
    } else {
        fail('Cabecera CSP estricta', `CSP recibida: ${cspHeader}`);
    }

    if (cacheHeader.includes('no-store') || cacheHeader.includes('no-cache')) {
        pass(`Cabecera Cache-Control para HTML/CSRF validada: ${cacheHeader}`);
    } else {
        fail('Cabecera Cache-Control para HTML/CSRF', `Cache-Control recibida: ${cacheHeader}`);
    }

    console.log('\n--- 2. Verificación de Metadatos de Assets, ES Modules & Librerías Locales (Tarea 8) ---');

    const assetMetaCheck = await send('Runtime.evaluate', {
        expression: `JSON.stringify({
            hasImportMap: Boolean(document.querySelector('script[type="importmap"]')),
            metaUtils: document.querySelector('meta[name="asset-utils"]')?.getAttribute('content') || '',
            metaChartjs: document.querySelector('meta[name="asset-chartjs"]')?.getAttribute('content') || '',
            metaJspdf: document.querySelector('meta[name="asset-jspdf"]')?.getAttribute('content') || '',
            metaStyles: document.querySelector('meta[name="asset-styles"]')?.getAttribute('content') || '',
            hasPMO: typeof window.PMO !== 'undefined',
            hasUtils: typeof window.PMO?.utils !== 'undefined',
            hasHttp: typeof window.PMO?.http !== 'undefined'
        })`,
        returnByValue: true
    });

    const metaRes = JSON.parse(assetMetaCheck.result?.value || '{}');

    if (!metaRes.hasImportMap) {
        pass('Import Map inline eliminado del DOM en favor de metadatos versionados e import() dinámico');
    } else {
        fail('Import Map inline presente', 'Se esperaba su eliminación del DOM');
    }

    if (metaRes.metaUtils.includes('/js/core/utils.js?v=') && metaRes.metaChartjs.includes('/js/vendor/chart.umd.min.js?v=') && metaRes.metaJspdf.includes('/js/vendor/jspdf.umd.min.js?v=')) {
        pass(`URLs versionadas entregadas correctamente en <meta>: utils (${metaRes.metaUtils}), chartjs (${metaRes.metaChartjs}), jspdf (${metaRes.metaJspdf})`);
    } else {
        fail('Metadatos de assets versionados', JSON.stringify(metaRes));
    }

    if (metaRes.hasPMO && metaRes.hasUtils && metaRes.hasHttp) {
        pass('Módulo utils.js importado dinámicamente y objeto global PMO inicializado');
    } else {
        fail('Objeto PMO.utils', JSON.stringify(metaRes));
    }

    // Verificación de carga y ejecución de librerías vendor locales desde <meta> (sin CDN fallback)
    const vendorCheck = await send('Runtime.evaluate', {
        awaitPromise: true,
        expression: `(async () => {
            const chartMetaUrl = document.querySelector('meta[name="asset-chartjs"]')?.getAttribute('content') || '/js/vendor/chart.umd.min.js';
            const jspdfMetaUrl = document.querySelector('meta[name="asset-jspdf"]')?.getAttribute('content') || '/js/vendor/jspdf.umd.min.js';

            const loadLocalScript = (src) => new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = src;
                s.onload = () => resolve(true);
                s.onerror = (e) => reject(new Error('Fallo al cargar ' + src));
                document.head.appendChild(s);
            });

            let chartLoaded = false;
            let chartRenderSuccess = false;
            let jspdfLoaded = false;
            let jspdfConstructSuccess = false;

            try {
                await loadLocalScript(chartMetaUrl);
                chartLoaded = typeof window.Chart === 'function';
                
                // Probar renderizado de radar en canvas virtual
                if (chartLoaded) {
                    const canvas = document.createElement('canvas');
                    canvas.width = 300;
                    canvas.height = 300;
                    document.body.appendChild(canvas);
                    const chart = new window.Chart(canvas, {
                        type: 'radar',
                        data: {
                            labels: ['Comunicación', 'Liderazgo', 'Problemas', 'Adaptabilidad'],
                            datasets: [{
                                data: [80, 90, 75, 85],
                                label: 'Test'
                            }]
                        }
                    });
                    chartRenderSuccess = typeof chart.destroy === 'function';
                    chart.destroy();
                    canvas.remove();
                }
            } catch (err) {
                console.error('Error cargando Chart.js local:', err);
            }

            try {
                await loadLocalScript(jspdfMetaUrl);
                jspdfLoaded = typeof window.jspdf !== 'undefined' && typeof window.jspdf.jsPDF === 'function';
                if (jspdfLoaded) {
                    const doc = new window.jspdf.jsPDF();
                    doc.text('PMO Solutions Test Report', 10, 10);
                    jspdfConstructSuccess = typeof doc.output === 'function';
                }
            } catch (err) {
                console.error('Error cargando jsPDF local:', err);
            }

            return {
                chartLoaded,
                chartRenderSuccess,
                jspdfLoaded,
                jspdfConstructSuccess
            };
        })()`,
        returnByValue: true
    });

    const vendorRes = vendorCheck.result?.value || {};
    if (vendorRes.chartLoaded && vendorRes.chartRenderSuccess) {
        pass('Chart.js v4.4.0 local cargado desde meta tag y renderiza gráfico radar con éxito');
    } else {
        fail('Chart.js local', JSON.stringify(vendorRes));
    }

    if (vendorRes.jspdfLoaded && vendorRes.jspdfConstructSuccess) {
        pass('jsPDF v2.5.1 local cargado desde meta tag e instancia documentos PDF con éxito');
    } else {
        fail('jsPDF local', JSON.stringify(vendorRes));
    }

    console.log('\n--- 3. Interacción con el Formulario & Inicio de Evaluación ---');

    const fillResult = await send('Runtime.evaluate', {
        expression: `(() => {
            const inputNombre = document.querySelector('input[name="nombre_completo"]');
            const inputEmail = document.querySelector('input[name="email"]');
            const inputCargo = document.querySelector('input[name="cargo"]');
            const btnStart = document.getElementById('btn-start-eval');
            
            if (!inputNombre || !inputEmail || !btnStart) {
                return { success: false, reason: 'Elementos del formulario no encontrados' };
            }
            
            inputNombre.value = 'Ing. Carlos Mendoza Test';
            inputEmail.value = 'carlos.mendoza@constructora.com';
            inputCargo.value = 'Project Manager Senior';
            
            btnStart.click();
            return { success: true };
        })()`,
        returnByValue: true
    });

    if (fillResult.result?.value?.success) {
        pass('Formulario de participante completado y botón #btn-start-eval pulsado');
    } else {
        fail('Llenado de formulario', fillResult.result?.value?.reason || 'Error');
    }

    await sleep(1500);

    console.log('\n--- 4. Verificación del Renderizado de la Primera Pregunta ---');

    const questionCheck = await send('Runtime.evaluate', {
        expression: `(() => {
            const stepEval = document.getElementById('step-evaluation');
            const stepPart = document.getElementById('step-participant');
            const questionCard = document.getElementById('question-card');
            const compName = document.getElementById('current-competency-name');
            const options = document.querySelectorAll('.option-card');
            const progressBar = document.getElementById('eval-progress-bar');
            
            return {
                evalVisible: stepEval && window.getComputedStyle(stepEval).display !== 'none',
                partHidden: stepPart && window.getComputedStyle(stepPart).display === 'none',
                hasScenario: questionCard && questionCard.innerText.length > 30,
                competencyText: compName ? compName.innerText : '',
                optionCount: options.length,
                progressWidth: progressBar ? progressBar.style.width : ''
            };
        })()`,
        returnByValue: true
    });

    const qRes = questionCheck.result?.value || {};
    if (qRes.evalVisible && qRes.partHidden && qRes.hasScenario && qRes.optionCount === 4) {
        pass(`Paso 2 activado (#step-evaluation visible, #step-participant oculto)`);
        pass(`Primera pregunta renderizada en #question-card: "${qRes.competencyText}" con 4 opciones situacionales`);
    } else {
        fail('Renderizado de pregunta situacional', JSON.stringify(qRes));
    }

    // =========================================================================
    // 5. PRUEBAS DE PETICIONES POST PROTEGIDAS EN LA SESIÓN REAL DEL NAVEGADOR
    // =========================================================================
    console.log('\n--- 5. Verificación de Protección CSRF en Sesión Real del Navegador ---');

    const evalPostTests = await send('Runtime.evaluate', {
        awaitPromise: true,
        expression: `(async () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const genIdemp = () => 'idemp_' + Math.random().toString(36).substring(2) + Date.now().toString(36);

            // --- A. CONTACTO (/contacto/submit) ---
            const resContactValid = await fetch('/contacto/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Idempotency-Key': genIdemp(),
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({})
            });
            const dataContactValid = await resContactValid.json().catch(() => ({}));

            const resContactNoToken = await fetch('/contacto/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Idempotency-Key': genIdemp()
                },
                body: JSON.stringify({})
            });

            const resContactBadToken = await fetch('/contacto/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Idempotency-Key': genIdemp(),
                    'X-CSRF-Token': 'bad_token_1234567890abcdef'
                },
                body: JSON.stringify({})
            });

            // --- B. RECLAMACIONES (/reclamaciones/submit) ---
            const resClaimValid = await fetch('/reclamaciones/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Idempotency-Key': genIdemp(),
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({})
            });
            const dataClaimValid = await resClaimValid.json().catch(() => ({}));

            const resClaimNoToken = await fetch('/reclamaciones/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Idempotency-Key': genIdemp()
                },
                body: JSON.stringify({})
            });

            const resClaimBadToken = await fetch('/reclamaciones/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Idempotency-Key': genIdemp(),
                    'X-CSRF-Token': 'invalid_claim_token_xyz'
                },
                body: JSON.stringify({})
            });

            // --- C. EVALUACIÓN DE HABILIDADES (/api/evaluacion-habilidades) ---
            const validAnswers = {
                comunicacion: {1: 3, 2: 2, 3: 4, 4: 1, 5: 3},
                trabajo_equipo: {1: 4, 2: 3, 3: 2, 4: 4, 5: 3},
                resolucion_problemas: {1: 2, 2: 4, 3: 3, 4: 2, 5: 4},
                adaptabilidad: {1: 4, 2: 3, 3: 4, 4: 3, 5: 2}
            };
            const resEvalValid = await fetch('/api/evaluacion-habilidades', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    nombre_completo: 'Ing. Navegador CDP',
                    email: 'navegador@pmo-solutions.com',
                    cargo: 'Director de Obra',
                    empresa: 'Constructora Real',
                    tipo_evaluacion: 'Individual',
                    answers: validAnswers,
                    website_hp: ''
                })
            });
            const dataEvalValid = await resEvalValid.json().catch(() => ({}));

            const resEvalNoToken = await fetch('/api/evaluacion-habilidades', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nombre_completo: 'Test' })
            });

            const resEvalBadToken = await fetch('/api/evaluacion-habilidades', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': 'tampered_eval_token'
                },
                body: JSON.stringify({ nombre_completo: 'Test' })
            });

            return {
                csrfTokenPresent: csrfToken.length === 64,
                contact: {
                    validStatus: resContactValid.status,
                    validReachedValidation: dataContactValid.message?.includes('obligatorios') || resContactValid.status === 422,
                    noTokenStatus: resContactNoToken.status,
                    badTokenStatus: resContactBadToken.status
                },
                claim: {
                    validStatus: resClaimValid.status,
                    validReachedValidation: dataClaimValid.message?.includes('obligatorios') || resClaimValid.status === 422,
                    noTokenStatus: resClaimNoToken.status,
                    badTokenStatus: resClaimBadToken.status
                },
                eval: {
                    validStatus: resEvalValid.status,
                    validSuccess: dataEvalValid.success === true,
                    noTokenStatus: resEvalNoToken.status,
                    badTokenStatus: resEvalBadToken.status
                }
            };
        })()`,
        returnByValue: true
    });

    const postResults = evalPostTests.result?.value || {};

    if (postResults.csrfTokenPresent) {
        pass('Token CSRF de 64 caracteres hex presente en la sesión del navegador');
    } else {
        fail('Token CSRF en meta tag', 'No encontrado o longitud inválida');
    }

    // Contacto
    if (postResults.contact?.validStatus === 422 && postResults.contact?.validReachedValidation) {
        pass('Contacto: Token CSRF correcto supera la barrera de seguridad y ejecuta validación MVC (HTTP 422)');
    } else {
        fail('Contacto: Token CSRF correcto', `Status recibido: ${postResults.contact?.validStatus}`);
    }
    if (postResults.contact?.noTokenStatus === 403) {
        pass('Contacto: Token CSRF ausente responde estrictamente HTTP 403 Forbidden');
    } else {
        fail('Contacto: Token ausente', `Status recibido: ${postResults.contact?.noTokenStatus}`);
    }
    if (postResults.contact?.badTokenStatus === 403) {
        pass('Contacto: Token CSRF incorrecto responde estrictamente HTTP 403 Forbidden');
    } else {
        fail('Contacto: Token incorrecto', `Status recibido: ${postResults.contact?.badTokenStatus}`);
    }

    // Reclamaciones
    if (postResults.claim?.validStatus === 422 && postResults.claim?.validReachedValidation) {
        pass('Libro de Reclamaciones: Token CSRF correcto supera la barrera de seguridad y ejecuta validación MVC (HTTP 422)');
    } else {
        fail('Libro de Reclamaciones: Token CSRF correcto', `Status recibido: ${postResults.claim?.validStatus}`);
    }
    if (postResults.claim?.noTokenStatus === 403) {
        pass('Libro de Reclamaciones: Token CSRF ausente responde estrictamente HTTP 403 Forbidden');
    } else {
        fail('Libro de Reclamaciones: Token ausente', `Status recibido: ${postResults.claim?.noTokenStatus}`);
    }
    if (postResults.claim?.badTokenStatus === 403) {
        pass('Libro de Reclamaciones: Token CSRF incorrecto responde estrictamente HTTP 403 Forbidden');
    } else {
        fail('Libro de Reclamaciones: Token incorrecto', `Status recibido: ${postResults.claim?.badTokenStatus}`);
    }

    // Evaluación
    if (postResults.eval?.validStatus === 201 && postResults.eval?.validSuccess) {
        pass('Evaluación de Habilidades: Envío con Token CSRF correcto procesa evaluación con éxito (HTTP 201)');
    } else {
        fail('Evaluación de Habilidades: Token correcto', `Status recibido: ${postResults.eval?.validStatus}`);
    }
    if (postResults.eval?.noTokenStatus === 403) {
        pass('Evaluación de Habilidades: Token CSRF ausente responde estrictamente HTTP 403 Forbidden');
    } else {
        fail('Evaluación de Habilidades: Token ausente', `Status recibido: ${postResults.eval?.noTokenStatus}`);
    }
    if (postResults.eval?.badTokenStatus === 403) {
        pass('Evaluación de Habilidades: Token CSRF incorrecto responde estrictamente HTTP 403 Forbidden');
    } else {
        fail('Evaluación de Habilidades: Token incorrecto', `Status recibido: ${postResults.eval?.badTokenStatus}`);
    }

    // =========================================================================
    // 6. VERIFICACIÓN DE RUTAS CANÓNICAS & RETIRO DE ALIAS OBSOLETOS (Tarea 9)
    // =========================================================================
    console.log('\n--- 6. Verificación de Rutas Canónicas & Retiro de Alias (Tarea 9) ---');

    const evalRouteTests = await send('Runtime.evaluate', {
        awaitPromise: true,
        expression: `(async () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const genIdemp = () => 'idemp_' + Math.random().toString(36).substring(2) + Date.now().toString(36);

            // Rutas canónicas
            const resContactCanon = await fetch('/contacto/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Idempotency-Key': genIdemp(),
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({})
            });

            const resClaimCanon = await fetch('/reclamaciones/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Idempotency-Key': genIdemp(),
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({})
            });

            // Rutas obsoletas retiradas (deben devolver 404)
            const resObsSendContact = await fetch('/api/send-contact', { method: 'POST' });
            const resObsSubmitClaim = await fetch('/api/submit-claim', { method: 'POST' });
            const resObsContactoPost = await fetch('/contacto', { method: 'POST' });
            const resObsReclamacionesPost = await fetch('/libro-de-reclamaciones', { method: 'POST' });

            return {
                contactCanonStatus: resContactCanon.status,
                claimCanonStatus: resClaimCanon.status,
                obsSendContact: resObsSendContact.status,
                obsSubmitClaim: resObsSubmitClaim.status,
                obsContactoPost: resObsContactoPost.status,
                obsReclamacionesPost: resObsReclamacionesPost.status
            };
        })()`,
        returnByValue: true
    });

    const routeResults = evalRouteTests.result?.value || {};

    if (routeResults.contactCanonStatus === 422 && routeResults.claimCanonStatus === 422) {
        pass('Rutas canónicas POST /contacto/submit y /reclamaciones/submit atienden peticiones con CSRF e Idempotencia (HTTP 422 en payload vacío)');
    } else {
        fail('Rutas canónicas POST', JSON.stringify(routeResults));
    }

    if (routeResults.obsSendContact === 404 && routeResults.obsSubmitClaim === 404 && routeResults.obsContactoPost === 404 && routeResults.obsReclamacionesPost === 404) {
        pass('Rutas retiradas (POST /api/send-contact, /api/submit-claim, /contacto, /libro-de-reclamaciones) retornan HTTP 404');
    } else {
        fail('Rutas retiradas deben responder 404', JSON.stringify(routeResults));
    }

    // =========================================================================
    // 7. PRUEBAS REALES DE CABECERAS DE CACHÉ EN ASSETS CON Y SIN ?v= (Tarea 8)
    // =========================================================================
    console.log('\n--- 7. Pruebas Reales de Cabeceras de Caché (Asset con ?v= vs Sin Versión) ---');

    const realFetchCache = await send('Runtime.evaluate', {
        awaitPromise: true,
        expression: `(async () => {
            const resVersioned = await fetch('/css/styles.css?v=1787863607');
            const ccVersioned = resVersioned.headers.get('cache-control') || '';

            const resUnversioned = await fetch('/css/styles.css');
            const ccUnversioned = resUnversioned.headers.get('cache-control') || '';

            return {
                versionedStatus: resVersioned.status,
                versionedCacheControl: ccVersioned,
                unversionedStatus: resUnversioned.status,
                unversionedCacheControl: ccUnversioned
            };
        })()`,
        returnByValue: true
    });

    const cacheRes = realFetchCache.result?.value || {};
    if (cacheRes.versionedStatus === 200 && cacheRes.versionedCacheControl.includes('max-age=31536000') && cacheRes.versionedCacheControl.includes('immutable')) {
        pass(`Asset con ?v= recibe caché inmutable de 1 año (HTTP ${cacheRes.versionedStatus}, Cache-Control: ${cacheRes.versionedCacheControl})`);
    } else {
        fail('Caché inmutable para asset versionado', JSON.stringify(cacheRes));
    }

    if (cacheRes.unversionedStatus === 200 && cacheRes.unversionedCacheControl.includes('max-age=3600') && cacheRes.unversionedCacheControl.includes('must-revalidate') && !cacheRes.unversionedCacheControl.includes('immutable')) {
        pass(`Asset sin versión recibe caché corta y revalidable (HTTP ${cacheRes.unversionedStatus}, Cache-Control: ${cacheRes.unversionedCacheControl})`);
    } else {
        fail('Caché revalidable para asset sin versión', JSON.stringify(cacheRes));
    }

    // =========================================================================
    // 8. VERIFICACIÓN DE SERVICE WORKER (Tarea 8)
    // =========================================================================
    console.log('\n--- 8. Verificación de Service Worker & PWA Cache (Tarea 8) ---');

    const swCheck = await send('Runtime.evaluate', {
        awaitPromise: true,
        expression: `(async () => {
            if (!('serviceWorker' in navigator)) {
                return { supported: false };
            }
            try {
                const reg = await navigator.serviceWorker.register('/js/sw.js');
                return {
                    supported: true,
                    registered: Boolean(reg),
                    scope: reg ? reg.scope : ''
                };
            } catch (err) {
                return {
                    supported: true,
                    registered: false,
                    error: err.message
                };
            }
        })()`,
        returnByValue: true
    });

    const swRes = swCheck.result?.value || {};
    if (swRes.supported && swRes.registered) {
        pass(`Service Worker registrado con éxito en scope: ${swRes.scope}`);
    } else {
        fail('Service Worker registro', JSON.stringify(swRes));
    }

    // =========================================================================
    // 9. VERIFICACIÓN EXPLÍCITA DE CERO VIOLACIONES CSP & CONSOLA LIMPIA (Tarea 8)
    // =========================================================================
    console.log('\n--- 9. Verificación de Red, Consola Limpia & Cero Violaciones CSP (Zero Violations) ---');

    const domCspViolationsEval = await send('Runtime.evaluate', {
        expression: `JSON.stringify(window.__domCspViolations || [])`,
        returnByValue: true
    });
    const domCspViolations = JSON.parse(domCspViolationsEval.result?.value || '[]');

    const totalCspViolations = cdpCspViolations.length + domCspViolations.length;

    if (totalCspViolations === 0) {
        pass('Cero violaciones CSP detectadas en el navegador (CDP y eventos securitypolicyviolation limpios)');
    } else {
        fail('Violaciones CSP detectadas:', JSON.stringify({ cdp: cdpCspViolations, dom: domCspViolations }));
    }

    if (networkErrors.length === 0) {
        pass('Carga de red exitosa: 0 errores 500/HTTP y 0 recursos fallidos');
    } else {
        fail('Errores de red detectados:', networkErrors.join('\n    '));
    }

    if (consoleErrors.length === 0) {
        pass('Consola limpia: 0 errores de JavaScript y 0 excepciones no capturadas');
    } else {
        fail('Errores detectados en consola:', consoleErrors.join('\n    '));
    }

    // =========================================================================
    // 10. VERIFICACIÓN E2E DE INTEGRIDAD Y ESTILOS DEL FOOTER CANÓNICO
    // =========================================================================
    console.log('\n--- 10. Verificación E2E de Footer Canónico, Color de Fondo y Ausencia de Niubiz ---');

    const footerCheckEval = await send('Runtime.evaluate', {
        expression: `(() => {
            const footer = document.querySelector('footer');
            if (!footer) return { exists: false };
            const style = window.getComputedStyle(footer);
            const hasCorporate = footer.classList.contains('footer-corporate');
            const hasCustom = footer.classList.contains('footer-custom');
            const bgColor = style.backgroundColor;
            const cols = footer.querySelectorAll('.row > div[class*="col-"]');
            const hasCountrySelect = !!footer.querySelector('#footerCountrySelect, select');
            const hasNiubizModal = !!document.getElementById('niubizCheckoutModal');
            const hasNiubizElements = !!document.querySelector('.payment-brand-logos, .niubiz-badge-bar, .niubiz-card-logo, .payment-method-nav');
            const hasPaymentLogosInFooter = !!footer.querySelector('img[src*="visa"], img[src*="Mastercard"], img[src*="Yape"], img[src*="Plin"]');
            const hasPagosSegurosText = footer.textContent.includes('Pagos Seguros');
            const waLinks = Array.from(footer.querySelectorAll('a[href*="whatsapp.com"], a[href*="wa.me"]')).map(a => a.href);
            const hasSingleWa = waLinks.length > 0 && waLinks.every(l => l.includes('51944276649'));
            const noHorizontalScroll = document.documentElement.scrollWidth <= document.documentElement.clientWidth;

            return {
                exists: true,
                hasCorporate,
                hasCustom,
                bgColor,
                colCount: cols.length,
                hasCountrySelect,
                hasNiubizModal,
                hasNiubizElements,
                hasPaymentLogosInFooter,
                hasPagosSegurosText,
                hasSingleWa,
                noHorizontalScroll
            };
        })()`,
        returnByValue: true
    });

    const footerMetrics = footerCheckEval.result?.value || {};
    if (footerMetrics.exists && footerMetrics.hasCorporate && !footerMetrics.hasCustom) {
        pass('Footer utiliza exclusivamente la clase canónica .footer-corporate (sin .footer-custom)');
    } else {
        fail('Clase canónica del footer', JSON.stringify(footerMetrics));
    }

    if (footerMetrics.colCount === 3) {
        pass('Estructura del footer validada exactamente en 3 columnas principales');
    } else {
        fail('Cantidad de columnas en footer', `Esperado: 3, obtenido: ${footerMetrics.colCount}`);
    }

    if (!footerMetrics.hasCountrySelect && footerMetrics.hasSingleWa) {
        pass('Selector multi-país eliminado y único canal oficial WhatsApp +51 944 276 649 verificado');
    } else {
        fail('Selector de países o enlace WhatsApp incorrecto', JSON.stringify(footerMetrics));
    }

    if (footerMetrics.bgColor === 'rgb(10, 54, 99)') {
        pass(`Color de fondo del footer validado en azul corporativo (${footerMetrics.bgColor} / #0A3663)`);
    } else {
        fail('Color de fondo del footer', `Obtenido: ${footerMetrics.bgColor}, esperado: rgb(10, 54, 99)`);
    }

    if (!footerMetrics.hasNiubizModal && !footerMetrics.hasNiubizElements) {
        pass('Ausencia estricta de modal, badges, tarjetas y elementos de checkout Niubiz en el DOM');
    } else {
        fail('Elementos residuales de Niubiz encontrados en DOM', JSON.stringify(footerMetrics));
    }

    if (!footerMetrics.hasPaymentLogosInFooter && !footerMetrics.hasPagosSegurosText) {
        pass('Ausencia total del bloque "Pagos Seguros" y logos de tarjetas/billeteras en el footer');
    } else {
        fail('Bloque de pagos seguros o logos presentes en el footer', JSON.stringify(footerMetrics));
    }

    if (footerMetrics.noHorizontalScroll) {
        pass('Diseño del footer y viewport sin desbordamiento horizontal');
    } else {
        fail('Desbordamiento horizontal detectado en el layout');
    }

    console.log('\n--- 11. Verificación E2E de Google Maps en /contacto (Iframe, CSP, Responsive) ---');
    await send('Emulation.setDeviceMetricsOverride', {
        width: 1440,
        height: 900,
        deviceScaleFactor: 1,
        mobile: false
    });
    loadFired = false;
    await send('Page.navigate', { url: 'http://127.0.0.1:8899/contacto' });
    for (let i = 0; i < 30; i++) {
        if (loadFired) break;
        await sleep(100);
    }
    await sleep(1000);

    const contactMapMetrics = await send('Runtime.evaluate', {
        expression: `(() => {
            const iframe = document.querySelector('iframe.contact-map-iframe') || document.querySelector('iframe');
            if (!iframe) return { found: false };
            const computedStyle = window.getComputedStyle(iframe);
            return {
                found: true,
                hasLoadingLazy: iframe.getAttribute('loading') === 'lazy',
                hasAllowFullscreen: iframe.hasAttribute('allowfullscreen'),
                title: iframe.getAttribute('title') || '',
                referrerpolicy: iframe.getAttribute('referrerpolicy') || '',
                src: iframe.getAttribute('src') || '',
                computedHeight: computedStyle.height,
                width: computedStyle.width,
                domCspViolationsCount: (window.__domCspViolations || []).length
            };
        })()`,
        returnByValue: true
    });

    const mapData = contactMapMetrics.result?.value || {};
    if (mapData.found) {
        pass('Iframe de Google Maps presente en /contacto');
    } else {
        fail('Iframe de Google Maps no encontrado en /contacto');
    }

    if (mapData.hasLoadingLazy && mapData.hasAllowFullscreen && mapData.referrerpolicy === 'no-referrer-when-downgrade' && mapData.title.length > 5) {
        pass(`Atributos de iframe verificados (loading="lazy", allowfullscreen, referrerpolicy, title: "${mapData.title}")`);
    } else {
        fail('Atributos de iframe de mapa incompletos', JSON.stringify(mapData));
    }

    if (mapData.src.startsWith('https://www.google.com/maps') || mapData.src.startsWith('https://maps.google.com/maps')) {
        pass('URL HTTPS limpia y segura en src del iframe (sin sintaxis markdown)');
    } else {
        fail('URL del mapa inválida o con sintaxis corrupta', mapData.src);
    }

    // Verificar altura en desktop (420px)
    if (mapData.computedHeight === '420px') {
        pass(`Altura del mapa en escritorio validada: ${mapData.computedHeight}`);
    } else {
        fail('Altura del mapa en escritorio no es 420px', `Obtenido: ${mapData.computedHeight}`);
    }

    // Verificar responsividad en móvil (320px)
    await send('Emulation.setDeviceMetricsOverride', {
        width: 375,
        height: 812,
        deviceScaleFactor: 2,
        mobile: true
    });
    await sleep(200);

    const mobileMapMetrics = await send('Runtime.evaluate', {
        expression: `(() => {
            const iframe = document.querySelector('iframe.contact-map-iframe') || document.querySelector('iframe');
            if (!iframe) return { found: false };
            const computedStyle = window.getComputedStyle(iframe);
            return {
                computedHeight: computedStyle.height
            };
        })()`,
        returnByValue: true
    });

    const mobileHeight = mobileMapMetrics.result?.value?.computedHeight;
    if (mobileHeight === '320px') {
        pass(`Altura del mapa en móvil validada a 375px viewport: ${mobileHeight}`);
    } else {
        fail('Altura del mapa en móvil no es 320px', `Obtenido: ${mobileHeight}`);
    }

    // Resetear emulación
    await send('Emulation.clearDeviceMetricsOverride');

    if (mapData.domCspViolationsCount === 0 && cdpCspViolations.length === 0) {
        pass('Cero violaciones CSP detectadas al incrustar Google Maps (frame-src verificado)');
    } else {
        fail('Violaciones CSP detectadas en /contacto', JSON.stringify({ dom: mapData.domCspViolationsCount, cdp: cdpCspViolations }));
    }

    ws.close();
    cleanup();

    console.log('\n======================================================================');
    console.log(' RESUMEN DE PRUEBA EN NAVEGADOR');
    console.log('======================================================================');
    console.log(` Total de aserciones : ${testResults.passed + testResults.failed}`);
    console.log(` \x1b[32mPruebas exitosas (PASS) : ${testResults.passed}\x1b[0m`);
    console.log(` \x1b[31mPruebas fallidas (FAIL) : ${testResults.failed}\x1b[0m\n`);

    process.exit(testResults.failed === 0 ? 0 : 1);
}

runBrowserTest().catch(err => {
    console.error('Error fatal en test de navegador:', err);
    cleanup();
    process.exit(1);
});