/**
 * PMO SOLUTIONS — Core Utilities (utils.js)
 * Funciones de utilidad compartidas entre todos los módulos.
 * Este archivo se carga de forma sincrónica o como módulo ES6.
 *
 * @module utils
 */

'use strict';

// ─── Namespace global de PMO & Declaración Explícita ─────────────────────────
const PMO = window.PMO = window.PMO || {};

if (!PMO._initialized) {
  PMO._initialized = true;

  /**
   * Objeto de utilidades generales.
   */
  PMO.utils = {

    /**
     * Debounce: limita la frecuencia de ejecución de una función.
     * @param {Function} fn    - Función a ejecutar
     * @param {number}   delay - Milisegundos de espera
     * @returns {Function}
     *
     * @example
     *   window.addEventListener('resize', PMO.utils.debounce(() => { ... }, 200));
     */
    debounce(fn, delay = 300) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    },

    /**
     * Throttle: ejecuta la función como máximo una vez por intervalo.
     * @param {Function} fn       - Función a ejecutar
     * @param {number}   interval - Milisegundos entre ejecuciones
     * @returns {Function}
     */
    throttle(fn, interval = 300) {
        let lastTime = 0;
        return function (...args) {
            const now = Date.now();
            if (now - lastTime >= interval) {
                lastTime = now;
                return fn.apply(this, args);
            }
        };
    },

    /**
     * Carga diferida (lazy) de un módulo JS con importación dinámica.
     * @param {string}   modulePath  - Ruta al archivo JS
     * @param {Function} [callback]  - Callback cuando el módulo cargue
     * @returns {Promise<any>}
     *
     * @example
     *   PMO.utils.lazyLoad('/js/modules/forms.js').then(mod => mod.init());
     */
    async lazyLoad(modulePath, callback = null) {
        try {
            const module = await import(modulePath);
            if (typeof callback === 'function') callback(module);
            return module;
        } catch (err) {
            console.warn(`[PMO] No se pudo cargar el módulo: ${modulePath}`, err);
            return null;
        }
    },

    /**
     * IntersectionObserver factory para Lazy Loading de elementos.
     * @param {string}   selector  - Selector CSS de los elementos a observar
     * @param {Function} onVisible - Callback cuando el elemento sea visible
     * @param {Object}   [options] - Opciones del IntersectionObserver
     */
    observeWhenVisible(selector, onVisible, options = { threshold: 0.1, rootMargin: '0px 0px 100px 0px' }) {
        if (!('IntersectionObserver' in window)) {
            // Fallback: ejecutar inmediatamente si no hay soporte
            document.querySelectorAll(selector).forEach(el => onVisible(el));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    onVisible(entry.target);
                    observer.unobserve(entry.target); // Dejar de observar después del primer disparo
                }
            });
        }, options);

        document.querySelectorAll(selector).forEach(el => observer.observe(el));
    },

    /**
     * Formatea un número como porcentaje con 1 decimal.
     * @param   {number} value - Valor numérico
     * @returns {string}
     */
    formatPercent: (value) => `${Number(value).toFixed(1)}%`,

    /**
     * Retorna el color de un nivel de madurez PMO.
     * @param   {string} level - 'Inicial' | 'En Desarrollo' | 'Competente' | 'Sobresaliente'
     * @returns {string}       - Color hexadecimal
     */
    maturityColor(level) {
        const colors = {
            'Sobresaliente': '#27ae60',
            'Competente':    '#2980b9',
            'En Desarrollo': '#f39c12',
            'Inicial':       '#e74c3c',
        };
        return colors[level] || '#95a5a6';
    },

    /**
     * Genera un ID único de cliente (para rastrear sesiones de evaluación).
     * @returns {string}
     */
    generateSessionId() {
        return 'pmo_' + Math.random().toString(36).substring(2, 11) + '_' + Date.now().toString(36);
    },

    /**
     * Almacena datos en localStorage con TTL.
     * @param {string} key   - Clave de almacenamiento
     * @param {any}    value - Valor a guardar (serializable)
     * @param {number} ttlMs - Tiempo de vida en milisegundos
     */
    setWithTTL(key, value, ttlMs = 3600000) {
        try {
            localStorage.setItem(key, JSON.stringify({
                value,
                expires: Date.now() + ttlMs,
            }));
        } catch (e) { /* localStorage puede no estar disponible */ }
    },

    /**
     * Recupera datos de localStorage verificando el TTL.
     * @param   {string} key - Clave de almacenamiento
     * @returns {any|null}   - Valor o null si expiró o no existe
     */
    getWithTTL(key) {
        try {
            const raw = localStorage.getItem(key);
            if (!raw) return null;
            const data = JSON.parse(raw);
            if (Date.now() > data.expires) {
                localStorage.removeItem(key);
                return null;
            }
            return data.value;
        } catch (e) { return null; }
    },

    /**
     * Muestra una notificación toast accesible.
     * @param {string} message - Mensaje a mostrar
     * @param {string} type    - 'success' | 'error' | 'warning' | 'info'
     * @param {number} duration - Duración en ms (default 4000)
     */
    toast(message, type = 'info', duration = 4000) {
        const container = document.getElementById('toast-container')
                         || this._createToastContainer();

        const toast = document.createElement('div');
        toast.className  = `pmo-toast pmo-toast--${type}`;
        toast.textContent = message;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');

        container.appendChild(toast);

        // Animar entrada
        requestAnimationFrame(() => toast.classList.add('pmo-toast--visible'));

        setTimeout(() => {
            toast.classList.remove('pmo-toast--visible');
            toast.addEventListener('transitionend', () => toast.remove(), { once: true });
        }, duration);
    },

    _createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.setAttribute('aria-atomic', 'false');
        container.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(container);
        return container;
    },
  };

  /**
   * HTTP Client ligero con soporte retry y timeout.
   */
  PMO.http = {

    /**
     * Realiza una petición HTTP con fetch y manejo de errores.
     * @param   {string} url     - URL del endpoint
     * @param   {Object} options - Opciones de fetch + { timeout, retries }
     * @returns {Promise<any>}   - Datos JSON de la respuesta
     */
    async request(url, options = {}) {
        const { timeout = 10000, retries = 2, ...fetchOptions } = options;

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        };
        if (csrfMeta && csrfMeta.getAttribute('content')) {
            defaultHeaders['X-CSRF-Token'] = csrfMeta.getAttribute('content');
        }

        for (let attempt = 0; attempt <= retries; attempt++) {
            const controller = new AbortController();
            const timeoutId  = setTimeout(() => controller.abort(), timeout);

            try {
                const response = await fetch(url, {
                    ...fetchOptions,
                    signal: controller.signal,
                    headers: {
                        ...defaultHeaders,
                        ...fetchOptions.headers,
                    },
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP ${response.status}`);
                }

                return await response.json();

            } catch (error) {
                clearTimeout(timeoutId);

                if (error.name === 'AbortError') {
                    throw new Error('La solicitud tardó demasiado. Por favor intenta nuevamente.');
                }

                if (attempt < retries) {
                    // Exponential backoff: 200ms, 400ms, ...
                    await new Promise(r => setTimeout(r, 200 * Math.pow(2, attempt)));
                    continue;
                }

                throw error;
            }
        }
    },

    /**
     * POST JSON con CSRF protection básica.
     */
    async post(url, data = {}) {
        return this.request(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
    },

    /**
     * GET con parámetros de query string.
     */
    async get(url, params = {}) {
        const qs = new URLSearchParams(params).toString();
        return this.request(qs ? `${url}?${qs}` : url, { method: 'GET' });
    },
  };
}

// ─── Exportar para uso como módulo ES6 ───────────────────────────────────────
export { PMO };
