/**
 * PMO SOLUTIONS - Controlador de Filtros y Búsqueda del Catálogo de Cursos
 * Archivo único de lógica interactiva para /capacitaciones (43 cursos activos).
 */
(function () {
  'use strict';

  /**
   * Normaliza texto Unicode eliminando tildes/diacríticos y puntuación
   * para permitir búsquedas insensibles a acentos, mayúsculas o guiones.
   */
  function normalizeText(value) {
    if (!value) {
      return '';
    }
    var str = String(value);
    if (typeof str.normalize === 'function') {
      str = str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
    return str
      .toLowerCase()
      .replace(/[^a-z0-9\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function initCoursesCatalogFilter() {
    var root = document.querySelector('[data-courses-catalog]');
    if (!root) {
      return;
    }

    var searchInput = document.getElementById('catalogSearchInput');
    var clearSearchBtn = document.getElementById('catalogClearSearchBtn');
    var toolbarResetBtn = document.getElementById('catalogToolbarResetBtn');
    var emptyResetBtn = document.getElementById('catalogResetFilterBtn');
    var emptyState = document.getElementById('catalogEmptyState');
    var counterEl = document.getElementById('catalogResultsCounter');
    var visibleCountEl = document.getElementById('catalogVisibleCount');
    var grid = document.getElementById('coursesGridContainer');

    var cards = Array.prototype.slice.call(
      root.querySelectorAll('[data-course-card], .catalog-card-col')
    );
    var filterButtons = Array.prototype.slice.call(
      root.querySelectorAll('.catalog-filter-btn[data-filter]')
    );

    if (cards.length === 0) {
      return;
    }

    var totalCourses = cards.length;
    var activeCategory = 'all';
    var reducedMotionQuery = typeof window.matchMedia === 'function'
      ? window.matchMedia('(prefers-reduced-motion: reduce)')
      : null;

    // Precalcular el texto normalizado por tarjeta una sola vez al inicializar.
    var cardMeta = cards.map(function (card) {
      var rawSearch = card.getAttribute('data-search') || card.textContent || '';
      return {
        element: card,
        category: String(card.getAttribute('data-category') || '').trim(),
        normalizedSearch: normalizeText(rawSearch)
      };
    });

    function updateFilterButtonsState() {
      filterButtons.forEach(function (btn) {
        var btnCat = String(btn.getAttribute('data-filter') || 'all').trim();
        var isSelected = btnCat === activeCategory;
        btn.classList.toggle('active', isSelected);
        btn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      });
    }

    function updateCounter(visibleCount) {
      if (visibleCountEl) {
        visibleCountEl.textContent = String(visibleCount);
      } else if (counterEl) {
        counterEl.textContent = 'Mostrando ' + visibleCount + ' de ' + totalCourses + ' cursos';
      }
    }

    function applyFilters() {
      var rawQuery = searchInput ? searchInput.value : '';
      var normalizedQuery = normalizeText(rawQuery);
      var tokens = normalizedQuery ? normalizedQuery.split(' ') : [];
      var visibleCount = 0;
      var prefersReducedMotion = Boolean(reducedMotionQuery && reducedMotionQuery.matches);

      cardMeta.forEach(function (item) {
        var el = item.element;
        var matchesCategory = activeCategory === 'all' || item.category === activeCategory;
        var matchesSearch = tokens.length === 0 || tokens.every(function (token) {
          return item.normalizedSearch.indexOf(token) !== -1;
        });
        var isVisible = matchesCategory && matchesSearch;

        // Limpiar cualquier estilo inline o clase heredada para evitar estados bloqueados.
        el.style.removeProperty('display');
        el.classList.remove('d-none', 'hide-filter', 'fade-in-filter');

        if (isVisible) {
          el.classList.remove('is-filtered-out');
          el.hidden = false;
          if (!prefersReducedMotion) {
            el.classList.add('fade-in-filter');
          }
          visibleCount++;
        } else {
          el.classList.add('is-filtered-out');
          el.hidden = true;
        }
      });

      updateCounter(visibleCount);

      var hasActiveFilters = activeCategory !== 'all' || normalizedQuery.length > 0;
      if (clearSearchBtn) {
        clearSearchBtn.classList.toggle('d-none', !rawQuery || rawQuery.length === 0);
      }
      if (toolbarResetBtn) {
        toolbarResetBtn.hidden = !hasActiveFilters;
        toolbarResetBtn.classList.toggle('d-none', !hasActiveFilters);
      }
      if (emptyState) {
        emptyState.hidden = visibleCount > 0;
        emptyState.classList.toggle('d-none', visibleCount > 0);
      }
      if (grid) {
        grid.setAttribute('data-visible-courses', String(visibleCount));
      }
    }

    function resetAllFilters(shouldFocusSearch) {
      activeCategory = 'all';
      if (searchInput) {
        searchInput.value = '';
      }
      updateFilterButtonsState();
      applyFilters();
      if (shouldFocusSearch && searchInput && typeof searchInput.focus === 'function') {
        searchInput.focus();
      }
    }

    filterButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var nextCategory = String(btn.getAttribute('data-filter') || 'all').trim();
        // Cambiar o limpiar categoría ("all") preserva siempre el término de búsqueda activo.
        activeCategory = nextCategory || 'all';
        updateFilterButtonsState();
        applyFilters();
      });
    });

    if (searchInput) {
      var handleSearchInput = function () {
        applyFilters();
      };
      searchInput.addEventListener('input', handleSearchInput);
      searchInput.addEventListener('search', handleSearchInput);
    }

    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', function () {
        if (searchInput) {
          searchInput.value = '';
          searchInput.focus();
        }
        // Limpiar solo el buscador preserva la categoría seleccionada.
        applyFilters();
      });
    }

    if (toolbarResetBtn) {
      toolbarResetBtn.addEventListener('click', function () {
        resetAllFilters(false);
      });
    }

    if (emptyResetBtn) {
      emptyResetBtn.addEventListener('click', function () {
        resetAllFilters(true);
      });
    }

    // Sincronización inicial sin alterar el estado visible por defecto de los 43 cursos.
    updateFilterButtonsState();
    applyFilters();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCoursesCatalogFilter);
  } else {
    initCoursesCatalogFilter();
  }
})();
