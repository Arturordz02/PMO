/**
 * PMO SOLUTIONS - Main JavaScript
 * Handles multi-country WhatsApp selector, interactive modals, counters and dynamic UI
 */

document.addEventListener('DOMContentLoaded', () => {
  // Multi-country WhatsApp configuration
  const whatsappConfig = {
    pe: {
      name: 'Perú',
      code: '+51',
      number: '51944276649',
      display: '+51 944 276 649',
      flag: '🇵🇪',
      defaultMsg: 'Hola PMO Solutions Perú, deseo solicitar información sobre sus programas y consultoría.'
    },
    cl: {
      name: 'Chile',
      code: '+56',
      number: '56987654321',
      display: '+56 9 8765 4321',
      flag: '🇨🇱',
      defaultMsg: 'Hola PMO Solutions Chile, deseo consultar por capacitaciones y asesorías de proyectos.'
    },
    ec: {
      name: 'Ecuador',
      code: '+593',
      number: '593987654321',
      display: '+593 9 8765 4321',
      flag: '🇪🇨',
      defaultMsg: 'Hola PMO Solutions Ecuador, requiero información de sus cursos especializados.'
    },
    pa: {
      name: 'Panamá',
      code: '+507',
      number: '50761234567',
      display: '+507 6123 4567',
      flag: '🇵🇦',
      defaultMsg: 'Hola PMO Solutions Panamá, solicito asesoría en gestión contractual y proyectos.'
    },
    mx: {
      name: 'México',
      code: '+52',
      number: '525512345678',
      display: '+52 55 1234 5678',
      flag: '🇲🇽',
      defaultMsg: 'Hola PMO Solutions México, deseo informes sobre sus programas de especialización.'
    }
  };

  // Setup Footer WhatsApp Selector
  const countrySelect = document.getElementById('footerCountrySelect');
  const footerWaBtn = document.getElementById('footerWaBtn');
  const footerWaDisplay = document.getElementById('footerWaDisplay');

  function updateFooterWhatsApp(countryKey) {
    const data = whatsappConfig[countryKey] || whatsappConfig['pe'];
    const encodedMsg = encodeURIComponent(data.defaultMsg);
    const waUrl = `https://wa.me/${data.number}?text=${encodedMsg}`;
    
    if (footerWaBtn) {
      footerWaBtn.href = waUrl;
    }
    if (footerWaDisplay) {
      footerWaDisplay.textContent = `${data.flag} ${data.display}`;
    }
  }

  if (countrySelect) {
    countrySelect.addEventListener('change', (e) => {
      updateFooterWhatsApp(e.target.value);
    });
    // Initialize with Peru
    updateFooterWhatsApp('pe');
  }

  // Back to Top Button
  const backToTopBtn = document.getElementById('backToTop');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 350) {
      backToTopBtn?.classList.add('show');
    } else {
      backToTopBtn?.classList.remove('show');
    }

    // Sticky Navbar shadow on scroll
    const navbar = document.querySelector('.navbar-custom');
    if (navbar) {
      if (window.scrollY > 50) {
        navbar.classList.add('navbar-scrolled');
      } else {
        navbar.classList.remove('navbar-scrolled');
      }
    }
  });

  if (backToTopBtn) {
    backToTopBtn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  // Niubiz Pago Web / Checkout Gateway Listener
  document.addEventListener('click', (e) => {
    const niubizBtn = e.target.closest('#btn-pagar-niubiz') || (e.target.id === 'btn-pagar-niubiz') || e.target.closest('.btn-trigger-niubiz');
    if (niubizBtn) {
      // If clicking from an external page button, open modal or invoke SDK
      const modalEl = document.getElementById('niubizCheckoutModal');
      if (modalEl && !e.target.closest('#niubizCheckoutModal')) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        return;
      }
      
      // Inside modal checkout button invocation
      if (typeof VisanetCheckout !== 'undefined') {
        VisanetCheckout.open();
      } else {
        alert('Abriendo pasarela de pago segura y tokenizada de Niubiz...\n\nProcesamiento 100% seguro certificado con PCI-DSS y CyberSource (Visa, Mastercard, Amex, Diners y Yape).');
      }
    }
  });

  // ── ANIMATED STATS COUNTER ─────────────────────────────────────────────────
  // Counts up numbers when the stats band enters the viewport
  const statNumbers = document.querySelectorAll('.stat-number[data-target]');
  if (statNumbers.length > 0) {
    const animateCounter = (el) => {
      const target  = parseInt(el.getAttribute('data-target'), 10);
      const prefix  = el.getAttribute('data-prefix') || '';
      const suffix  = el.getAttribute('data-suffix') || '';
      const duration = 1800; // ms
      const step    = Math.ceil(target / (duration / 16));
      let current   = 0;

      const tick = () => {
        current = Math.min(current + step, target);
        el.textContent = prefix + current + suffix;
        if (current < target) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };

    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });

    statNumbers.forEach(el => observer.observe(el));
  }

  // ── CATALOG INTERACTIVE CATEGORY FILTER (capacitaciones.html) ───────────────
  const filterBtns = document.querySelectorAll('.catalog-filter-btn');
  const courseCards = document.querySelectorAll('.catalog-card-col');

  if (filterBtns.length > 0 && courseCards.length > 0) {
    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        // Toggle active button state
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const selectedCategory = btn.getAttribute('data-filter');

        courseCards.forEach(card => {
          const cardCategory = card.getAttribute('data-category');
          card.classList.remove('fade-in-filter');

          if (selectedCategory === 'all' || cardCategory === selectedCategory) {
            card.classList.remove('hide-filter');
            // Trigger reflow to restart animation
            void card.offsetWidth;
            card.classList.add('fade-in-filter');
          } else {
            card.classList.add('hide-filter');
          }
        });
      });
    });
  }

  // ===========================================================================
  // ── BACKEND INTEGRATION: FORMULARIO DE CONTACTO (contacto.html) ────────────
  // ===========================================================================
  const contactForm = document.getElementById('contactForm');
  const contactFeedback = document.getElementById('contactFormFeedback');
  const contactSubmitBtn = document.getElementById('contactSubmitBtn');

  if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Validación de campos en el cliente
      const nombre = contactForm.querySelector('[name="nombre"]')?.value.trim();
      const telefono = contactForm.querySelector('[name="telefono"]')?.value.trim();
      const email = contactForm.querySelector('[name="email"]')?.value.trim();
      const mensaje = contactForm.querySelector('[name="mensaje"]')?.value.trim();

      if (!nombre || !telefono || !email || !mensaje) {
        showFeedback(
          contactFeedback,
          'danger',
          '<i class="fas fa-exclamation-circle me-2"></i> <strong>Campos incompletos:</strong> Por favor completa todos los campos requeridos marcados con (*).'
        );
        return;
      }

      // Validar formato de email
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        showFeedback(
          contactFeedback,
          'danger',
          '<i class="fas fa-envelope-open me-2"></i> <strong>Correo inválido:</strong> Por favor ingresa una dirección de correo electrónico válida.'
        );
        return;
      }

      // Estado de carga en el botón
      const originalBtnHtml = contactSubmitBtn.innerHTML;
      contactSubmitBtn.disabled = true;
      contactSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> ENVIANDO MENSAJE...';
      hideFeedback(contactFeedback);

      try {
        const formData = new FormData(contactForm);
        const endpoint = contactForm.getAttribute('action') || 'backend/send-contact.php';

        const response = await fetch(endpoint, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const data = await response.json();

        if (response.ok && data.success) {
          showFeedback(
            contactFeedback,
            'success',
            `<div class="d-flex align-items-center gap-3">
              <div class="fs-2 text-success"><i class="fas fa-check-circle"></i></div>
              <div>
                <h5 class="alert-heading fw-bold mb-1">¡Mensaje Enviado con Éxito!</h5>
                <p class="mb-0 small">${escapeHtml(data.message)}</p>
              </div>
            </div>`
          );
          // Limpiar formulario únicamente cuando el servidor confirme recepción exitosa
          contactForm.reset();
        } else {
          let errorMsg = data.message || 'Ocurrió un error al enviar el formulario. Por favor, verifica tus datos e inténtalo nuevamente.';
          if (data.errors && typeof data.errors === 'object') {
            const errorList = Object.values(data.errors).map(err => `<li>${escapeHtml(err)}</li>`).join('');
            errorMsg += `<ul class="mb-0 mt-2 ps-3 small">${errorList}</ul>`;
          }
          showFeedback(
            contactFeedback,
            'danger',
            `<div class="d-flex align-items-start gap-2">
              <i class="fas fa-exclamation-triangle mt-1"></i>
              <div>${errorMsg}</div>
            </div>`
          );
        }
      } catch (err) {
        showFeedback(
          contactFeedback,
          'danger',
          '<i class="fas fa-wifi me-2"></i> <strong>Error de conexión:</strong> No fue posible comunicarse con el servidor. Por favor, revisa tu conexión a internet o contáctanos directamente por WhatsApp.'
        );
      } finally {
        contactSubmitBtn.disabled = false;
        contactSubmitBtn.innerHTML = originalBtnHtml;
      }
    });
  }

  // ===========================================================================
  // ── BACKEND INTEGRATION: LIBRO DE RECLAMACIONES (libro-de-reclamaciones.html)
  // ===========================================================================
  const claimForm = document.getElementById('claimForm');
  const claimFeedback = document.getElementById('claimFormFeedback');
  const claimSubmitBtn = document.getElementById('claimSubmitBtn');

  if (claimForm) {
    claimForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Validación de checkbox de declaración jurada
      const declaracion = claimForm.querySelector('[name="declaracion_jurada"]');
      if (declaracion && !declaracion.checked) {
        showFeedback(
          claimFeedback,
          'warning',
          '<i class="fas fa-shield-alt me-2"></i> <strong>Declaración obligatoria:</strong> Debe aceptar la declaración jurada para registrar la Hoja de Reclamación conforme a ley.'
        );
        declaracion.focus();
        return;
      }

      // Validar HTML5 nativo
      if (!claimForm.checkValidity()) {
        claimForm.classList.add('was-validated');
        showFeedback(
          claimFeedback,
          'danger',
          '<i class="fas fa-exclamation-circle me-2"></i> <strong>Campos obligatorios incompletos:</strong> Por favor revisa y completa todos los campos requeridos marcados con (*).'
        );
        return;
      }

      // Estado de carga en el botón
      const originalBtnHtml = claimSubmitBtn ? claimSubmitBtn.innerHTML : '';
      if (claimSubmitBtn) {
        claimSubmitBtn.disabled = true;
        claimSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> REGISTRANDO RECLAMACIÓN...';
      }
      hideFeedback(claimFeedback);

      try {
        const formData = new FormData(claimForm);
        const endpoint = claimForm.getAttribute('action') || 'backend/submit-claim.php';

        const response = await fetch(endpoint, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const data = await response.json();

        if (response.ok && data.success) {
          const codigo = escapeHtml(data.codigo_reclamacion || 'REC-2026');
          const email = escapeHtml(data.email || 'su correo');
          const tipo = escapeHtml(data.tipo_registro || 'Reclamación');

          showFeedback(
            claimFeedback,
            'success',
            `<div class="card border-success border-2 shadow-sm rounded-4 p-4 bg-white text-dark">
              <div class="text-center mb-3">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 64px; height: 64px;">
                  <i class="fas fa-check-circle fs-2"></i>
                </div>
                <h4 class="fw-bold text-success mb-1">¡Hoja de ${tipo} Procesada!</h4>
                <p class="text-muted small mb-3">Conforme al Código de Protección y Defensa del Consumidor (Ley N° 29571 / INDECOPI)</p>
                <div class="p-3 bg-warning bg-opacity-25 rounded-3 d-inline-block border border-warning">
                  <span class="small text-dark text-uppercase fw-bold d-block">Código de Seguimiento</span>
                  <span class="fs-4 fw-bold text-dark font-monospace">${codigo}</span>
                </div>
              </div>
              <div class="alert alert-info py-2 small mb-3">
                <i class="fas fa-info-circle me-1"></i> ${escapeHtml(data.message)}
              </div>
              <div class="text-center pt-2">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-4" onclick="location.reload();">
                  <i class="fas fa-redo me-1"></i> Registrar Otro Reclamo
                </button>
              </div>
            </div>`
          );

          // Ocultar formulario para evitar duplicados accidentales
          claimForm.style.display = 'none';
        } else {
          let errorMsg = data.message || 'Ocurrió un error al registrar la reclamación.';
          if (data.errors && typeof data.errors === 'object') {
            const errorList = Object.values(data.errors).map(err => `<li>${escapeHtml(err)}</li>`).join('');
            errorMsg += `<ul class="mb-0 mt-2 ps-3 small">${errorList}</ul>`;
          }
          showFeedback(
            claimFeedback,
            'danger',
            `<div class="d-flex align-items-start gap-2">
              <i class="fas fa-exclamation-triangle mt-1"></i>
              <div>${errorMsg}</div>
            </div>`
          );
        }
      } catch (err) {
        showFeedback(
          claimFeedback,
          'danger',
          '<i class="fas fa-wifi me-2"></i> <strong>Error de conexión:</strong> No fue posible enviar la reclamación al servidor. Por favor, comprueba tu conexión o contáctanos directamente a comercial@pmo-solutions.com.'
        );
      } finally {
        if (claimSubmitBtn) {
          claimSubmitBtn.disabled = false;
          claimSubmitBtn.innerHTML = originalBtnHtml;
        }
      }
    });
  }

  // Helper para mostrar feedback en contenedor
  function showFeedback(container, type, html) {
    if (!container) return;
    container.className = `alert alert-${type} alert-dismissible fade show shadow-sm rounded-4`;
    container.innerHTML = `
      ${html}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    `;
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  // Helper para ocultar feedback
  function hideFeedback(container) {
    if (!container) return;
    container.style.display = 'none';
    container.innerHTML = '';
  }

  // Helper para sanitizar texto en frontend
  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // ── TOAST NOTIFICACIÓN AUTOMÁTICA: MATRÍCULAS 2026 ────────────────────────
  // Aparece de forma no intrusiva a los 4 segundos tras cargar la página
  setTimeout(() => {
    const promoToastEl = document.getElementById('promoToast');
    if (promoToastEl && typeof bootstrap !== 'undefined') {
      const promoToast = bootstrap.Toast.getOrCreateInstance(promoToastEl);
      promoToast.show();
    }
  }, 4000);
});

