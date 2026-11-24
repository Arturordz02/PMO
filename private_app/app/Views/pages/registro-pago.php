<?php
declare(strict_types=1);
use App\Core\View;
use App\Core\Csrf;

$courseTitle = (string)($course['title'] ?? 'Capacitación Profesional');
$slug = (string)($courseSlug ?? 'nec4');
$clase = (int)($selectedClass ?? 1);
if ($clase < 1 || $clase > 8) {
    $clase = 1;
}
$prices = $currentPrices ?? ($classPrices[$clase] ?? ['pen' => 10, 'usd' => 3]);
$pen = (int)($prices['pen'] ?? 10);
$usd = (int)($prices['usd'] ?? 3);
$csrfToken = Csrf::getToken();
$old = $oldInput ?? [];
$errors = $formErrors ?? [];
$globalError = $formError ?? null;
?>

<section class="py-5 bg-dark text-white position-relative" style="background: linear-gradient(180deg, #071326 0%, #0c1b33 100%);">
  <div class="container py-2 py-md-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="/" class="text-white-50 text-decoration-none">Inicio</a></li>
        <li class="breadcrumb-item"><a href="/capacitaciones" class="text-white-50 text-decoration-none">Capacitaciones</a></li>
        <li class="breadcrumb-item"><a href="/curso/<?= View::e($slug) ?>" class="text-white-50 text-decoration-none"><?= View::e($courseTitle) ?></a></li>
        <li class="breadcrumb-item"><a href="/pagar-curso?curso=<?= View::e($slug) ?>&clase=<?= $clase ?>" class="text-white-50 text-decoration-none">Pago por Clase</a></li>
        <li class="breadcrumb-item active text-warning" aria-current="page">Registro de Comprobante</li>
      </ol>
    </nav>

    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-8 col-xl-7">

        <div class="card border border-warning border-opacity-25 rounded-4 shadow-lg overflow-hidden" style="background: rgba(15, 39, 68, 0.95);">

          <div class="card-header bg-black bg-opacity-30 border-bottom border-white border-opacity-10 p-4 text-center">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-dark mx-auto mb-3" style="width: 56px; height: 56px;">
              <i class="fas fa-clipboard-check fs-4"></i>
            </div>
            <h1 class="h3 fw-bold text-white mb-2">Completa tu Registro de Inscripción</h1>
            <p class="text-white-50 small mb-0">
              Registra los datos del alumno y el número de referencia emitido por Niubiz para validar tu acceso a la sesión.
            </p>
          </div>

          <div class="card-body p-4 p-md-5">

            <!-- Resumen de Clase y Monto -->
            <div class="p-3 rounded-3 mb-4 border border-warning border-opacity-20" style="background: rgba(0, 0, 0, 0.3);">
              <div class="row align-items-center g-2">
                <div class="col-8">
                  <div class="small text-white-50 text-uppercase fw-semibold">Curso y Sesión</div>
                  <div class="text-white fw-bold"><?= View::e($courseTitle) ?></div>
                  <span class="badge bg-warning text-dark mt-1">Clase <?= $clase ?></span>
                </div>
                <div class="col-4 text-end">
                  <div class="small text-white-50 text-uppercase fw-semibold">Tarifa fija</div>
                  <div class="h5 fw-bold text-warning mb-0">US$ <?= $usd ?></div>
                  <div class="small text-white-50">S/ <?= $pen ?></div>
                </div>
              </div>
            </div>

            <!-- Alerta de Verificación Manual -->
            <div class="alert alert-warning bg-warning bg-opacity-10 border-warning text-white small d-flex align-items-start gap-2 mb-4" role="status">
              <i class="fas fa-shield-alt text-warning fs-5 mt-1"></i>
              <div>
                <strong>Aviso de seguridad:</strong> El pago quedará pendiente de verificación manual. Una vez validada tu referencia en la consola Niubiz, la coordinación académica de PMO Solutions te enviará los accesos y credenciales vía correo y WhatsApp.
              </div>
            </div>

            <?php if (!empty($globalError)): ?>
              <div class="alert alert-danger small mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-1"></i> <?= View::e($globalError) ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($isStatic)): ?>
              <div class="alert alert-info bg-opacity-10 border-info text-white text-center p-4 mb-4" role="status">
                <i class="fas fa-info-circle fs-4 text-info mb-2 d-block"></i>
                <h2 class="h5 fw-bold text-white mb-2">Vista Previa Estática</h2>
                <p class="text-white-50 small mb-0">El registro y procesamiento de pagos están habilitados únicamente en el servidor oficial con soporte PHP activo (GoDaddy). Esta versión de demostración estática no procesa envíos.</p>
                <a href="/capacitaciones" class="btn btn-sm btn-outline-light mt-3">Volver al catálogo</a>
              </div>
            <?php else: ?>
              <!-- Formulario de Registro -->
              <form action="/registro-pago/submit" method="POST" id="formRegistroPago" novalidate>

              <!-- Token CSRF -->
              <input type="hidden" name="csrf_token" value="<?= View::e($csrfToken) ?>">
              <input type="hidden" name="_csrf_token" value="<?= View::e($csrfToken) ?>">

              <!-- Campos de contexto -->
              <input type="hidden" name="curso" value="<?= View::e($slug) ?>">
              <input type="hidden" name="clase" value="<?= $clase ?>">

              <!-- Honeypot anti-spam -->
              <div class="visually-hidden" aria-hidden="true">
                <label for="_pmo_verification_hp">No completar este campo</label>
                <input type="text" id="_pmo_verification_hp" name="_pmo_verification_hp" tabindex="-1" autocomplete="off">
              </div>

              <!-- Nombres y Apellidos -->
              <div class="mb-3">
                <label for="nombres" class="form-label text-white small fw-bold">
                  Nombres y Apellidos Completos <span class="text-warning">*</span>
                </label>
                <input type="text"
                       class="form-control bg-dark text-white border-secondary<?= isset($errors['nombres']) ? ' is-invalid' : '' ?>"
                       id="nombres"
                       name="nombres"
                       maxlength="150"
                       required
                       autocomplete="name"
                       placeholder="Ej. Ing. Carlos Mendoza Ruiz"
                       value="<?= View::e((string)($old['nombres'] ?? '')) ?>">
                <?php if (isset($errors['nombres'])): ?>
                  <div class="invalid-feedback"><?= View::e((string)$errors['nombres']) ?></div>
                <?php endif; ?>
              </div>

              <!-- Correo Electrónico -->
              <div class="mb-3">
                <label for="email" class="form-label text-white small fw-bold">
                  Correo Electrónico del Alumno <span class="text-warning">*</span>
                </label>
                <input type="email"
                       class="form-control bg-dark text-white border-secondary<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                       id="email"
                       name="email"
                       maxlength="150"
                       required
                       autocomplete="email"
                       placeholder="correo@ejemplo.com"
                       value="<?= View::e((string)($old['email'] ?? '')) ?>">
                <div class="form-text text-white-50 small">Enviaremos los accesos y grabaciones a esta dirección.</div>
                <?php if (isset($errors['email'])): ?>
                  <div class="invalid-feedback"><?= View::e((string)$errors['email']) ?></div>
                <?php endif; ?>
              </div>

              <!-- Teléfono / WhatsApp y Documento -->
              <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                  <label for="telefono" class="form-label text-white small fw-bold">
                    Teléfono / WhatsApp <span class="text-warning">*</span>
                  </label>
                  <input type="tel"
                         class="form-control bg-dark text-white border-secondary<?= isset($errors['telefono']) ? ' is-invalid' : '' ?>"
                         id="telefono"
                         name="telefono"
                         maxlength="30"
                         required
                         autocomplete="tel"
                         placeholder="+51 999 888 777"
                         value="<?= View::e((string)($old['telefono'] ?? '')) ?>">
                  <?php if (isset($errors['telefono'])): ?>
                    <div class="invalid-feedback"><?= View::e((string)$errors['telefono']) ?></div>
                  <?php endif; ?>
                </div>

                <div class="col-12 col-md-6">
                  <label for="documento" class="form-label text-white small fw-bold">
                    DNI, CE o Pasaporte <span class="text-warning">*</span>
                  </label>
                  <input type="text"
                         class="form-control bg-dark text-white border-secondary<?= isset($errors['documento']) ? ' is-invalid' : '' ?>"
                         id="documento"
                         name="documento"
                         maxlength="30"
                         required
                         placeholder="N° de documento"
                         value="<?= View::e((string)($old['documento'] ?? '')) ?>">
                  <?php if (isset($errors['documento'])): ?>
                    <div class="invalid-feedback"><?= View::e((string)$errors['documento']) ?></div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Referencia Niubiz -->
              <div class="mb-4">
                <label for="referencia_niubiz" class="form-label text-white small fw-bold">
                  Número de Operación o Referencia Niubiz <span class="text-warning">*</span>
                </label>
                <input type="text"
                       class="form-control bg-dark text-white border-secondary font-monospace<?= isset($errors['referencia_niubiz']) ? ' is-invalid' : '' ?>"
                       id="referencia_niubiz"
                       name="referencia_niubiz"
                       maxlength="100"
                       required
                       placeholder="Ej. 2331259-XXXX o código de operación"
                       value="<?= View::e((string)($old['referencia_niubiz'] ?? '')) ?>">
                <div class="form-text text-white-50 small">
                  Ingresa el código o número de pedido que figura en el comprobante emitido por Niubiz tras tu pago.
                </div>
                <?php if (isset($errors['referencia_niubiz'])): ?>
                  <div class="invalid-feedback"><?= View::e((string)$errors['referencia_niubiz']) ?></div>
                <?php endif; ?>
                <?php if (isset($errors['seguridad'])): ?>
                  <div class="alert alert-danger small mt-2"><?= View::e((string)$errors['seguridad']) ?></div>
                <?php endif; ?>
              </div>

              <!-- Checkbox Política de Privacidad -->
              <div class="form-check mb-4">
                <input class="form-check-input<?= isset($errors['politica_privacidad']) ? ' is-invalid' : '' ?>"
                       type="checkbox"
                       name="politica_privacidad"
                       id="politica_privacidad"
                       value="1"
                       required
                       <?= !empty($old['politica_privacidad']) ? 'checked' : '' ?>>
                <label class="form-check-label text-white-50 small" for="politica_privacidad">
                  He leído y acepto la <a href="/politica-de-privacidad" target="_blank" class="text-warning text-decoration-underline">Política de Privacidad</a> y autorizo el tratamiento de mis datos personales para la verificación manual y coordinación académica de mi inscripción. <span class="text-warning">*</span>
                </label>
                <?php if (isset($errors['politica_privacidad'])): ?>
                  <div class="invalid-feedback d-block"><?= View::e((string)$errors['politica_privacidad']) ?></div>
                <?php endif; ?>
              </div>

              <!-- Mensajes AJAX dinámicos -->
              <div id="formFeedback" class="d-none alert mb-3 small" role="alert"></div>

              <!-- Botón de Envío -->
              <button type="submit" id="btnSubmitForm" class="btn btn-warning text-dark fw-bold btn-lg w-100 py-3 shadow hvr-grow">
                <i class="fas fa-paper-plane me-2"></i> Enviar mis Datos de Inscripción
              </button>

              <div class="text-center mt-3">
                <a href="/pagar-curso?curso=<?= View::e($slug) ?>&clase=<?= $clase ?>" class="text-white-50 small text-decoration-none">
                  <i class="fas fa-arrow-left me-1"></i> Modificar clase o volver al enlace de pago
                </a>
              </div>

            </form>
            <?php endif; ?>

          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('formRegistroPago');
  const btn = document.getElementById('btnSubmitForm');
  const feedback = document.getElementById('formFeedback');

  if (form && btn) {
    form.addEventListener('submit', function(e) {
      // Soporte AJAX con degradación elegante si falla
      if (!window.fetch) return;
      e.preventDefault();

      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Registrando datos…';
      if (feedback) {
        feedback.className = 'd-none alert mb-3 small';
        feedback.textContent = '';
      }

      const formData = new FormData(form);

      fetch('/registro-pago/submit', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      })
      .then(function(res) {
        return res.json().then(function(data) {
          return { ok: res.ok, status: res.status, data: data };
        });
      })
      .then(function(result) {
        if (result.ok && result.data && result.data.success) {
          if (feedback) {
            feedback.className = 'alert alert-success mb-3 small';
            feedback.textContent = result.data.message || 'Registro exitoso. Redireccionando…';
          }
          if (result.data.redirect) {
            window.location.assign(result.data.redirect);
          } else {
            window.location.assign('/pago-exitoso?curso=' + encodeURIComponent(formData.get('curso') || '') + '&clase=' + encodeURIComponent(formData.get('clase') || '1'));
          }
        } else {
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Enviar mis Datos de Inscripción';
          const errMsg = (result.data && result.data.message) ? result.data.message : 'Ocurrió un error al procesar el formulario. Por favor verifica tus datos.';
          if (feedback) {
            feedback.className = 'alert alert-danger mb-3 small';
            feedback.textContent = errMsg;
          }
        }
      })
      .catch(function(err) {
        // En caso de fallo de red o parseo, enviar formulario por POST tradicional
        form.submit();
      });
    });
  }
});
</script>
