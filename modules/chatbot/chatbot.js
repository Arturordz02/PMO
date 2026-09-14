/**
 * PMO SOLUTIONS - Lógica del Chatbot Guiado Corporativo
 * 
 * Características:
 * - Vanilla JS puro (sin frameworks ni dependencias externas)
 * - Cero recolección ni persistencia de datos personales
 * - Navegación accesible por teclado (Esc, Enter, Tab) y atributos ARIA
 * - Carga segura local de knowledge.json con manejo de errores controlado
 * - Prefijo estricto 'pmo-chatbot-' en todas las clases y elementos dinámicos
 */

(function () {
  'use strict';

  function initChatbot() {
    var container = document.getElementById('pmo-chatbot-root');
    if (!container) {
      return;
    }

    var triggerBtn = container.querySelector('.pmo-chatbot-trigger');
    var closeBtn = container.querySelector('.pmo-chatbot-close-btn');
    var resetBtn = container.querySelector('.pmo-chatbot-reset-btn');
    var bodyContainer = container.querySelector('.pmo-chatbot-body');
    var windowPanel = container.querySelector('.pmo-chatbot-window');

    if (!triggerBtn || !bodyContainer) {
      return;
    }

    var knowledgeData = null;
    var currentNodeKey = 'main_menu';
    var isInitialized = false;

    // ─────────────────────────────────────────────────────────────────────────
    // Carga segura y local de la base de conocimiento
    // ─────────────────────────────────────────────────────────────────────────
    function loadKnowledge(callback) {
      if (knowledgeData) {
        callback(null, knowledgeData);
        return;
      }

      // 1. Intentar leer del atributo de datos pre-renderizado en el contenedor
      var rawKnowledge = container.getAttribute('data-knowledge');
      if (rawKnowledge) {
        try {
          knowledgeData = JSON.parse(rawKnowledge);
          callback(null, knowledgeData);
          return;
        } catch (e) {
          // Si el JSON embebido fuera corrupto, continuar al fallback
        }
      }

      // 2. Fallback controlado mediante fetch() local
      if (typeof fetch === 'function') {
        fetch('/modules/chatbot/knowledge.json', {
          headers: { 'Accept': 'application/json' },
          cache: 'default'
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error('HTTP ' + response.status);
            }
            return response.json();
          })
          .then(function (data) {
            knowledgeData = data;
            callback(null, data);
          })
          .catch(function (err) {
            callback(err, null);
          });
      } else {
        callback(new Error('Fetch no soportado'), null);
      }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Formateador seguro de Markdown básico (negrita y saltos de línea)
    // ─────────────────────────────────────────────────────────────────────────
    function formatMessageText(text) {
      if (!text) return '';
      // Escapar caracteres HTML para mitigar vectores XSS
      var div = document.createElement('div');
      div.textContent = text;
      var escaped = div.innerHTML;

      // Transformar **texto** en <strong>texto</strong>
      escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
      // Transformar saltos de línea en <br>
      escaped = escaped.replace(/\n/g, '<br>');

      return escaped;
    }

    function getCurrentTime() {
      var now = new Date();
      var hours = now.getHours().toString().padStart(2, '0');
      var minutes = now.getMinutes().toString().padStart(2, '0');
      return hours + ':' + minutes;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Renderizado de Mensajes en el DOM
    // ─────────────────────────────────────────────────────────────────────────
    function appendBotMessage(htmlContent) {
      var messageEl = document.createElement('div');
      messageEl.className = 'pmo-chatbot-message pmo-chatbot-message-bot';

      var bubbleEl = document.createElement('div');
      bubbleEl.className = 'pmo-chatbot-bubble';
      bubbleEl.innerHTML = htmlContent;

      var timeEl = document.createElement('span');
      timeEl.className = 'pmo-chatbot-time';
      timeEl.textContent = getCurrentTime();

      messageEl.appendChild(bubbleEl);
      messageEl.appendChild(timeEl);
      bodyContainer.appendChild(messageEl);

      scrollToBottom();
      return messageEl;
    }

    function appendUserMessage(text) {
      var messageEl = document.createElement('div');
      messageEl.className = 'pmo-chatbot-message pmo-chatbot-message-user';

      var bubbleEl = document.createElement('div');
      bubbleEl.className = 'pmo-chatbot-bubble';
      bubbleEl.textContent = text;

      var timeEl = document.createElement('span');
      timeEl.className = 'pmo-chatbot-time';
      timeEl.textContent = getCurrentTime();

      messageEl.appendChild(bubbleEl);
      messageEl.appendChild(timeEl);
      bodyContainer.appendChild(messageEl);

      scrollToBottom();
    }

    function renderNodeOptions(options) {
      if (!options || !options.length) {
        return;
      }

      var optionsContainer = document.createElement('div');
      optionsContainer.className = 'pmo-chatbot-options-container';

      options.forEach(function (opt) {
        if (opt.type === 'link' || opt.type === 'external_link') {
          var link = document.createElement('a');
          link.className = 'pmo-chatbot-option-link';
          link.href = opt.url;
          link.textContent = opt.label;

          if (opt.type === 'external_link') {
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
          }

          var arrow = document.createElement('span');
          arrow.className = 'pmo-chatbot-option-arrow';
          arrow.innerHTML = opt.type === 'external_link' ? '↗' : '→';
          link.appendChild(arrow);

          optionsContainer.appendChild(link);
        } else {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'pmo-chatbot-option-btn';
          btn.textContent = opt.label;

          var arrowBtn = document.createElement('span');
          arrowBtn.className = 'pmo-chatbot-option-arrow';
          arrowBtn.textContent = '→';
          btn.appendChild(arrowBtn);

          btn.addEventListener('click', function () {
            handleOptionSelect(opt);
          });

          optionsContainer.appendChild(btn);
        }
      });

      bodyContainer.appendChild(optionsContainer);
      scrollToBottom();
    }

    function handleOptionSelect(option) {
      // Registrar elección visual del usuario
      appendUserMessage(option.label);

      // Deshabilitar botones anteriores para guiar la interacción
      var oldButtons = bodyContainer.querySelectorAll('.pmo-chatbot-option-btn');
      oldButtons.forEach(function (b) {
        b.disabled = true;
        b.classList.add('pmo-chatbot-option-disabled');
      });

      // Navegar al siguiente nodo
      if (option.next_node) {
        goToNode(option.next_node);
      }
    }

    function goToNode(nodeKey) {
      currentNodeKey = nodeKey;
      loadKnowledge(function (err, data) {
        if (err || !data || !data.nodes || !data.nodes[nodeKey]) {
          appendBotMessage('Lo sentimos, ha ocurrido un problema al cargar esta sección. Por favor selecciona otra opción o contáctanos directamente por nuestros canales oficiales.');
          renderNodeOptions([
            { label: '↩️ Volver al Menú Principal', next_node: 'main_menu' }
          ]);
          return;
        }

        var node = data.nodes[nodeKey];
        appendBotMessage(formatMessageText(node.message));
        renderNodeOptions(node.options);
      });
    }

    function scrollToBottom() {
      bodyContainer.scrollTop = bodyContainer.scrollHeight;
    }

    function resetConversation() {
      bodyContainer.innerHTML = '';
      goToNode('main_menu');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Control de Apertura / Cierre y Accesibilidad
    // ─────────────────────────────────────────────────────────────────────────
    function toggleChatbot() {
      var isActive = container.classList.contains('pmo-chatbot-active');
      if (isActive) {
        closeChatbot();
      } else {
        openChatbot();
      }
    }

    function openChatbot() {
      container.classList.add('pmo-chatbot-active');
      triggerBtn.setAttribute('aria-expanded', 'true');
      if (windowPanel) {
        windowPanel.removeAttribute('aria-hidden');
      }

      if (!isInitialized) {
        isInitialized = true;
        resetConversation();
      }

      if (closeBtn) {
        closeBtn.focus();
      }
    }

    function closeChatbot() {
      container.classList.remove('pmo-chatbot-active');
      triggerBtn.setAttribute('aria-expanded', 'false');
      if (windowPanel) {
        windowPanel.setAttribute('aria-hidden', 'true');
      }
      triggerBtn.focus();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Event Listeners
    // ─────────────────────────────────────────────────────────────────────────
    triggerBtn.addEventListener('click', toggleChatbot);

    if (closeBtn) {
      closeBtn.addEventListener('click', closeChatbot);
    }

    if (resetBtn) {
      resetBtn.addEventListener('click', resetConversation);
    }

    // Sincronizar desplazamiento para evitar superposición con Toast Promocional
    var promoToastEl = document.getElementById('promoToast');
    if (promoToastEl) {
      if (promoToastEl.classList.contains('show')) {
        container.classList.add('pmo-chatbot-shifted');
      }
      promoToastEl.addEventListener('show.bs.toast', function () {
        container.classList.add('pmo-chatbot-shifted');
      });
      promoToastEl.addEventListener('hidden.bs.toast', function () {
        container.classList.remove('pmo-chatbot-shifted');
      });
    }

    // Tecla Escape para cerrar accesiblemente
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && container.classList.contains('pmo-chatbot-active')) {
        closeChatbot();
      }
    });
  }

  // Inicializar al cargar el DOM
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChatbot);
  } else {
    initChatbot();
  }
})();
