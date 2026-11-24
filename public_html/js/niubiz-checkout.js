/**
 * PMO SOLUTIONS - Checkout Selector de Clases Niubiz
 *
 * Mejora progresiva para la selección de clase:
 * - Lee exclusivamente data-class-num, data-pen y data-usd generados por el servidor.
 * - Sincroniza estado visual y accesibilidad (aria-pressed).
 * - Actualiza montos oficiales en soles, dólares y el enlace post-pago.
 * - Sin scripts inline, sin eval y compatible con Content-Security-Policy restrictiva.
 */
(function() {
  'use strict';

  function initClassSelector() {
    var grid = document.getElementById('classSelectorGrid');
    if (!grid) {
      return;
    }

    var courseSlug = grid.getAttribute('data-course-slug') || '';
    var buttons = grid.querySelectorAll('.class-select-btn');
    if (!buttons || buttons.length === 0) {
      return;
    }

    var classTitle = document.getElementById('selectedClassTitle');
    var priceSummary = document.getElementById('selectedPriceSummary');
    var displayUsd = document.getElementById('displayUsd');
    var displayPen = document.getElementById('displayPen');
    var instructionUsd = document.getElementById('instructionUsd');
    var btnPayAmount = document.getElementById('btnPayAmount');
    var btnGoRegister = document.getElementById('btnGoRegister');

    function selectClass(btn, updateHistory) {
      var rawNum = btn.getAttribute('data-class-num');
      var rawPen = btn.getAttribute('data-pen');
      var rawUsd = btn.getAttribute('data-usd');

      var classNum = parseInt(rawNum, 10);
      var pen = parseInt(rawPen, 10);
      var usd = parseInt(rawUsd, 10);

      if (isNaN(classNum) || classNum < 1 || classNum > 8) {
        return;
      }
      if (isNaN(pen) || pen <= 0 || isNaN(usd) || usd <= 0) {
        return;
      }

      // Actualizar marcado visual y accesibilidad
      buttons.forEach(function(b) {
        b.classList.remove('btn-warning', 'text-dark', 'active-class');
        b.classList.add('btn-outline-light');
        b.setAttribute('aria-pressed', 'false');
      });

      btn.classList.remove('btn-outline-light');
      btn.classList.add('btn-warning', 'text-dark', 'active-class');
      btn.setAttribute('aria-pressed', 'true');

      // Actualizar información de la sesión seleccionada
      if (classTitle) {
        classTitle.textContent = 'Clase ' + classNum;
      }
      if (priceSummary) {
        priceSummary.textContent = 'S/ ' + pen + ' — US$ ' + usd;
      }
      if (displayUsd) {
        displayUsd.textContent = 'US$ ' + usd;
      }
      if (displayPen) {
        displayPen.textContent = pen.toString();
      }
      if (instructionUsd) {
        instructionUsd.textContent = 'US$ ' + usd;
      }
      if (btnPayAmount) {
        btnPayAmount.textContent = 'US$ ' + usd;
      }
      if (btnGoRegister) {
        btnGoRegister.href = '/registro-pago?curso=' + encodeURIComponent(courseSlug) + '&clase=' + classNum;
      }

      // Sincronizar URL para bookmarks / compartir manteniendo UX sin recarga
      if (updateHistory && window.history && typeof window.history.replaceState === 'function') {
        try {
          var url = new URL(window.location.href);
          url.searchParams.set('clase', classNum.toString());
          window.history.replaceState({}, '', url.toString());
        } catch (e) {
          // Ignorar navegadores sin soporte de URL
        }
      }
    }

    buttons.forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        selectClass(btn, true);
      });

      btn.addEventListener('keydown', function(e) {
        if (e.key === ' ' || e.key === 'Spacebar') {
          e.preventDefault();
          selectClass(btn, true);
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initClassSelector);
  } else {
    initClassSelector();
  }
})();
