/**
 * PMO SOLUTIONS — Service Worker (sw.js)
 *
 * Estrategia de caché:
 *  - Cache-First para assets estáticos versionados (CSS, JS, fonts, imágenes)
 *  - Network-Only con respuesta JSON ante fallos para APIs y formularios (sin persistir CSRF)
 *  - Network-First con fallback HTML offline para navegación de páginas
 *
 * @version 2.2.0
 */

'use strict';

// ─── Configuración ────────────────────────────────────────────────────────────
const CACHE_VERSION    = 'pmo-v2.2.0';
const STATIC_CACHE     = `${CACHE_VERSION}-static`;

/** Assets estáticos críticos precacheados en la instalación (solo assets puros sin CSRF/HTML dinámico) */
const PRECACHE_ASSETS = [
    '/css/styles.css',
    '/js/core/utils.js',
    '/js/main.js',
    '/js/modules/soft-skills.js',
    '/js/vendor/chart.umd.min.js',
    '/js/vendor/jspdf.umd.min.js',
    '/img/LogoPMO.png'
];

/** Rutas dinámicas que SIEMPRE van a la red y devuelven JSON ante error (nunca se cachean) */
const NETWORK_ONLY_PATTERNS = [
    /\/api\//,
    /\/contacto(\/submit)?/,
    /\/reclamaciones(\/submit)?/,
    /\/evaluacion-habilidades(\/submit)?/,
    /\/backend\//,
    /\/admin\//,
    /\/auth\//,
];

/** Extensiones de assets estáticos puros (Cache-First) */
const STATIC_EXTENSIONS = /\.(css|js|woff2?|ttf|otf|eot|png|jpg|jpeg|gif|webp|avif|svg|ico)$/i;

// ─────────────────────────────────────────────────────────────────────────────
// EVENTO: INSTALL — Precachear assets críticos estáticos
// ─────────────────────────────────────────────────────────────────────────────
self.addEventListener('install', (event) => {
    console.log(`[SW] Instalando ${CACHE_VERSION}`);

    event.waitUntil(
        caches.open(STATIC_CACHE).then(async (cache) => {
            const results = await Promise.allSettled(
                PRECACHE_ASSETS.map(url => cache.add(url).catch(e => {
                    console.warn(`[SW] Precache ignorado o fallido: ${url}`, e.message);
                }))
            );
            console.log(`[SW] Precache completado: ${results.filter(r => r.status === 'fulfilled').length}/${PRECACHE_ASSETS.length} assets`);
        }).then(() => self.skipWaiting())
    );
});

// ─────────────────────────────────────────────────────────────────────────────
// EVENTO: ACTIVATE — Limpiar cachés antiguas
// ─────────────────────────────────────────────────────────────────────────────
self.addEventListener('activate', (event) => {
    console.log(`[SW] Activando ${CACHE_VERSION}`);

    event.waitUntil(
        caches.keys().then(async (cacheNames) => {
            const validCaches = [STATIC_CACHE];
            const toDelete = cacheNames.filter(name => !validCaches.includes(name));

            await Promise.all(toDelete.map(name => {
                console.log(`[SW] Eliminando caché obsoleta: ${name}`);
                return caches.delete(name);
            }));

            return self.clients.claim();
        })
    );
});

// ─────────────────────────────────────────────────────────────────────────────
// EVENTO: FETCH — Interceptar peticiones con políticas estrictas
// ─────────────────────────────────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Solo interceptar peticiones del mismo origen
    if (url.origin !== location.origin) {
        return;
    }

    // No interceptar peticiones no-GET (POST, PUT, DELETE van siempre a la red)
    if (request.method !== 'GET') {
        return;
    }

    // ── Network-Only: APIs, formularios con CSRF, reportes y backend ──────────
    if (NETWORK_ONLY_PATTERNS.some(pattern => pattern.test(url.pathname))) {
        event.respondWith(networkOnlyJson(request));
        return;
    }

    // ── Cache-First para assets estáticos versionados ────────────────────────
    if (STATIC_EXTENSIONS.test(url.pathname)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    // ── Navegación de páginas HTML: Network con fallback offline HTML ────────
    if (request.mode === 'navigate' || request.headers.get('Accept')?.includes('text/html')) {
        event.respondWith(networkWithHtmlFallback(request));
        return;
    }

    // Fallback por defecto para otras peticiones GET
    event.respondWith(networkOnlyJson(request));
});

// ─────────────────────────────────────────────────────────────────────────────
// ESTRATEGIAS DE CACHÉ
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Cache-First: Retorna del caché, si no existe va a la red y guarda si está permitido.
 * No guarda respuestas con Cache-Control: no-store o private.
 */
async function cacheFirst(request, cacheName) {
    const cache  = await caches.open(cacheName);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cc = networkResponse.headers.get('Cache-Control') || '';
            if (!cc.includes('no-store') && !cc.includes('private')) {
                cache.put(request, networkResponse.clone());
            }
        }
        return networkResponse;
    } catch (error) {
        return new Response('Asset no disponible offline', { status: 503 });
    }
}

/**
 * Network-Only con respuesta JSON estructurada ante fallos de conexión.
 */
async function networkOnlyJson(request) {
    try {
        return await fetch(request);
    } catch (error) {
        return new Response(
            JSON.stringify({ success: false, message: 'Sin conexión. Por favor verifica tu internet.' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}

/**
 * Network para páginas HTML con entrega de fallback HTML offline cuando falla la red.
 */
async function networkWithHtmlFallback(request) {
    try {
        return await fetch(request);
    } catch (error) {
        return offlineFallback();
    }
}

/**
 * Respuesta de fallback cuando se está completamente offline.
 * Cumple estrictamente con CSP: sin onclick ni scripts inline.
 */
function offlineFallback() {
    return new Response(
        `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin conexión — PMO Solutions</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; text-align: center; padding: 3rem 1rem; color: #333; background: #f8fafc; }
        .card { max-width: 480px; margin: 0 auto; background: #fff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        h1 { color: #1a5b82; font-size: 1.5rem; margin: 1rem 0; }
        p { color: #64748b; line-height: 1.5; margin-bottom: 1.5rem; }
        .icon { font-size: 3.5rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #1a5b82; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">📡</div>
        <h1>Sin conexión a internet</h1>
        <p>No se pudo establecer conexión con el servidor. Por favor verifica tu red e intenta nuevamente.</p>
        <a href="" class="btn">🔄 Reintentar</a>
    </div>
</body>
</html>`,
        { status: 200, headers: { 'Content-Type': 'text/html; charset=UTF-8' } }
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// EVENTO: MESSAGE — Comunicación con el cliente
// ─────────────────────────────────────────────────────────────────────────────
self.addEventListener('message', (event) => {
    const { type } = event.data || {};

    if (type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (type === 'GET_VERSION') {
        event.ports[0]?.postMessage({ version: CACHE_VERSION });
    }

    if (type === 'CLEAR_CACHE') {
        caches.keys().then(keys => Promise.all(keys.map(k => caches.delete(k)))).then(() => {
            event.ports[0]?.postMessage({ success: true });
        });
    }
});

console.log(`[SW] Service Worker ${CACHE_VERSION} cargado.`);

