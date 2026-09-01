<!-- A. Header e Identificación Legal del Proveedor -->
  <header class="py-5" style="background: linear-gradient(135deg, #0A192F 0%, #00509E 60%, #0A3663 100%); color: #ffffff;">
    <div class="container text-center py-2">
      <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2 shadow-sm animate__animated animate__fadeInDown">
        <i class="fas fa-gavel me-2"></i> CONFORME A LA LEY N° 29571 / D.S. 011-2011-PCM
      </span>
      <h1 class="hero-title mb-2 text-white animate__animated animate__fadeInDown">LIBRO DE RECLAMACIONES VIRTUAL</h1>
      <p class="text-white-50 mx-auto mb-4 animate__animated animate__fadeInUp animate__delay-1s" style="max-width: 760px; font-size: 1.05rem;">
        Conforme a lo establecido en el Código de Protección y Defensa del Consumidor, PMO Solutions pone a disposición de sus clientes corporativos y profesionales el Libro de Reclamaciones Virtual.
      </p>

      <!-- Tarjeta de Identificación del Proveedor -->
      <div data-aos="fade-right" data-aos-delay="100" class="card border-0 rounded-4 shadow p-4 text-start mx-auto hvr-float-shadow hvr-box-shadow-outset" data-aos-duration="800" data-aos-delay="100" style="max-width: 860px; background: rgba(255, 255, 255, 0.96); color: #1e293b;">
        <div class="row g-3 align-items-center">
          <div class="col-12 col-md-6">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-white p-2 rounded-circle shadow-sm border">
                <img src="img/LogoPMO.png" alt="Logo PMO Solutions" width="45" height="45" style="object-fit: contain;">
              </div>
              <div>
                <span class="fw-bold fs-5 text-primary d-block">PMO SOLUTIONS</span>
                <span class="text-muted small">Razón Social: PMO SOLUTIONS S.A.C.</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6 border-start-md ps-md-4">
            <div class="small mb-1">
              <i class="fas fa-map-marker-alt text-warning me-2"></i> <strong>Dirección Física:</strong> Av. Javier Prado 757, piso 10, Magdalena, Lima 17, Perú
            </div>
            <div class="small mb-1">
              <i class="fas fa-envelope text-warning me-2"></i> <strong>Correo Electrónico Comercial:</strong> comercial@pmo-solutions.com
            </div>
            <div class="small">
              <i class="fab fa-whatsapp text-success me-2"></i> <strong>Atención al Cliente / WhatsApp:</strong> +51 944 276 649
            </div>
          </div>
        </div>
      </div>

    </div>
  </header>

  <!-- B. Formulario Simplificado en 3 Bloques (B2B / Ejecutivo) -->
  <main class="container my-5">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10">
        
        <div id="claimFormFeedback" class="mb-4" style="display:none;"></div>

        <form id="claimForm" action="backend/submit-claim.php" method="POST" novalidate>
          <!-- Honeypot anti-spam (invisible para usuarios reales) -->
          <div class="d-none" aria-hidden="true">
            <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
          </div>

          <!-- 1. SECCIÓN 1: DATOS DEL CONSUMIDOR / CLIENTE (Profesional / Empresa) -->
          <div data-aos="fade-up" data-aos-delay="200" class="card border-0 rounded-4 shadow-sm p-4 p-md-5 mb-4 bg-white border-top border-4 border-primary hvr-box-shadow-outset" data-aos-delay="200">
            <div class="d-flex align-items-center gap-3 mb-4 pb-2 border-bottom">
              <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle fs-4">
                <i class="fas fa-user-tie"></i>
              </div>
              <div>
                <span class="badge bg-primary text-white mb-1">SECCIÓN 1</span>
                <h4 class="fw-bold text-dark mb-0">DATOS DEL CONSUMIDOR / CLIENTE</h4>
                <small class="text-muted">Información del profesional o razón social reclamante</small>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label fw-bold small text-secondary">Tipo de Documento <span class="text-danger">*</span></label>
                <select class="form-select" name="tipo_documento" required>
                  <option value="DNI" selected>DNI</option>
                  <option value="Carnet de Extranjería">Carnet de Extranjería</option>
                  <option value="Pasaporte">Pasaporte</option>
                  <option value="RUC">RUC</option>
                </select>
              </div>

              <div class="col-12 col-md-8">
                <label class="form-label fw-bold small text-secondary">Número de Documento <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="numero_documento" placeholder="Ingresa tu número de documento" required>
              </div>

              <div class="col-12">
                <label class="form-label fw-bold small text-secondary">Nombres y Apellidos / Razón Social <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="nombre_completo" placeholder="Ingresa tu nombre completo o razón social de la empresa" required>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-bold small text-secondary">Teléfono / WhatsApp <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" name="telefono" placeholder="+51 999 999 999" required>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label fw-bold small text-secondary">Correo Electrónico <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" placeholder="tu_correo@empresa.com" required>
                <div class="form-text small">Obligatorio para recibir constancia y respuesta formal.</div>
              </div>

              <div class="col-12">
                <label class="form-label fw-bold small text-secondary">Domicilio / Dirección <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="domicilio" placeholder="Av. / Jr. / Calle, Número, Distrito, Provincia, Departamento" required>
              </div>
            </div>
          </div>

          <!-- 2. SECCIÓN 2: IDENTIFICACIÓN DEL SERVICIO O CAPACITACIÓN CONTRATADA -->
          <div data-aos="fade-up" data-aos-delay="200" class="card border-0 rounded-4 shadow-sm p-4 p-md-5 mb-4 bg-white border-top border-4 border-info hvr-box-shadow-outset" data-aos-delay="300">
            <div class="d-flex align-items-center gap-3 mb-4 pb-2 border-bottom">
              <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle fs-4">
                <i class="fas fa-graduation-cap"></i>
              </div>
              <div>
                <span class="badge bg-info text-dark mb-1">SECCIÓN 2</span>
                <h4 class="fw-bold text-dark mb-0">IDENTIFICACIÓN DEL SERVICIO O CAPACITACIÓN CONTRATADA</h4>
                <small class="text-muted">Detalles del programa o asesoría contratada</small>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-bold small text-secondary d-block">Tipo de Contratación <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-4 pt-1">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="tipo_servicio" id="servCapacitacion" value="Servicio de Capacitación / Curso" checked>
                    <label class="form-check-label fw-semibold" for="servCapacitacion">Servicio de Capacitación / Curso / Programa Especializado</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="tipo_servicio" id="servConsultoria" value="Consultoría Corporativa">
                    <label class="form-check-label fw-semibold" for="servConsultoria">Consultoría Corporativa / Asesoría Técnica</label>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label fw-bold small text-secondary">Nombre del Curso, Taller o Servicio de Consultoría Contratado <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="nombre_servicio" placeholder="Ej. Control de Proyectos con Primavera P6 / Asesoría en JRD / Curso VDC-BIM" required>
              </div>

              <div class="col-12">
                <label class="form-label fw-bold small text-secondary">Detalle del Servicio o Código de Matrícula (Opcional)</label>
                <textarea class="form-control" rows="2" name="detalle_servicio" placeholder="Código de matrícula, comprobante de pago, fecha de inicio o modalidad contratada..."></textarea>
              </div>
            </div>
          </div>

          <!-- 3. SECCIÓN 3: DETALLE DE LA RECLAMACIÓN Y SOLICITUD -->
          <div data-aos="fade-up" data-aos-delay="200" class="card border-0 rounded-4 shadow-sm p-4 p-md-5 mb-4 bg-white border-top border-4 border-warning hvr-box-shadow-outset" data-aos-delay="100">
            <div class="d-flex align-items-center gap-3 mb-4 pb-2 border-bottom">
              <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-circle fs-4">
                <i class="fas fa-file-signature"></i>
              </div>
              <div>
                <span class="badge bg-warning text-dark mb-1">SECCIÓN 3</span>
                <h4 class="fw-bold text-dark mb-0">DETALLE DE LA RECLAMACIÓN Y SOLICITUD</h4>
                <small class="text-muted">Clasificación y fundamentación de los hechos</small>
              </div>
            </div>

            <!-- Selección del Tipo de Hoja: Reclamo / Queja -->
            <div class="p-3 rounded-4 bg-light mb-4 border">
              <label class="form-label fw-bold text-dark d-block mb-3">Selecciona el Tipo de Registro <span class="text-danger">*</span></label>
              
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <div class="form-check p-3 bg-white rounded-3 border h-100 hvr-box-shadow-outset">
                    <input class="form-check-input ms-0 me-2" type="radio" name="tipo_registro" id="regReclamo" value="Reclamo" checked required>
                    <label class="form-check-label fw-bold text-primary d-block" for="regReclamo">
                      <i class="fas fa-exclamation-triangle text-danger me-1"></i> RECLAMO
                    </label>
                    <span class="small text-muted d-block mt-1">Disconformidad relacionada directamente con los cursos, materiales o servicios contratados.</span>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="form-check p-3 bg-white rounded-3 border h-100 hvr-box-shadow-outset">
                    <input class="form-check-input ms-0 me-2" type="radio" name="tipo_registro" id="regQueja" value="Queja" required>
                    <label class="form-check-label fw-bold text-warning d-block" for="regQueja">
                      <i class="fas fa-comment-dots text-warning me-1"></i> QUEJA
                    </label>
                    <span class="small text-muted d-block mt-1">Disconformidad respecto a la atención al cliente, tiempos de respuesta o procesos administrativos.</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-bold small text-secondary">Detalle del Reclamo o Queja <span class="text-danger">*</span></label>
              <textarea class="form-control" rows="5" name="detalle_reclamacion" placeholder="Describa con claridad y precisión los hechos ocurridos..." required></textarea>
            </div>

            <div class="mb-2">
              <label class="form-label fw-bold small text-secondary">Pedido Concreto del Consumidor <span class="text-danger">*</span></label>
              <textarea class="form-control" rows="3" name="pedido_consumidor" placeholder="¿Qué solución o medida correctiva solicita formalmente a PMO Solutions?..." required></textarea>
            </div>
          </div>

          <!-- 4. CONFIRMACIÓN, AVISO LEGAL Y ENVÍO AUTOMÁTICO -->
          <div data-aos="fade-up" data-aos-delay="200" class="card border-0 rounded-4 shadow-sm p-4 p-md-5 mb-4 bg-white border-top border-4 border-success hvr-box-shadow-outset" data-aos-delay="200">
            <div class="d-flex align-items-center gap-3 mb-4 pb-2 border-bottom">
              <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle fs-4">
                <i class="fas fa-shield-alt"></i>
              </div>
              <div>
                <span class="badge bg-success text-white mb-1">CONFIRMACIÓN</span>
                <h4 class="fw-bold text-dark mb-0">DECLARACIÓN JURADA Y REGISTRO</h4>
              </div>
            </div>

            <div class="alert alert-info py-3 small mb-4">
              <i class="fas fa-info-circle me-2 fs-5 align-middle"></i>
              <strong>Aviso Legal INDECOPI:</strong> La respuesta a la presente Hoja de Reclamación será enviada a la dirección de correo electrónico consignada en un plazo máximo de <strong>15 días hábiles</strong> conforme a ley (D.S. N° 011-2011-PCM).
            </div>

            <div class="form-check mb-4">
              <input class="form-check-input" type="checkbox" name="declaracion_jurada" id="declaracionJurada" required>
              <label class="form-check-label small text-secondary" for="declaracionJurada">
                Declaro que los datos consignados son verdaderos y acepto recibir la respuesta formal a través de mi correo electrónico conforme a la Ley N° 29571.
              </label>
            </div>

            <button type="submit" id="claimSubmitBtn" class="btn btn-warning btn-lg w-100 py-3 fw-bold rounded-pill shadow text-white hvr-grow">
              <i class="fas fa-paper-plane me-2"></i> REGISTRAR HOJA DE RECLAMACIÓN
            </button>
          </div>

        </form>

      </div>
    </div>
  </main>

  <!-- Footer Corporativo de PMO Solutions -->