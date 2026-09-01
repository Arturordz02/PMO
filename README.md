# PMO Solutions - Arquitectura Modelo-Vista-Controlador (MVC)

¡Bienvenido al repositorio de **PMO Solutions**! Este proyecto ha sido estructurado bajo un patrón de arquitectura **Modelo-Vista-Controlador (MVC)** nativo, limpio y modular en **PHP 8.x**, diseñado tanto para producción empresarial como para fines didácticos y formativos en capacitaciones.

---

## 🏛️ ¿Por qué esta Arquitectura para Capacitaciones?

1. **Sin dependencias pesadas innecesarias:** No requiere Laravel, Symfony o Composer para funcionar. Utiliza PHP moderno puro (Vanilla PHP), lo que permite a los estudiantes entender el flujo real de una petición web de principio a fin.
2. **Principio DRY (Don't Repeat Yourself):** Los elementos comunes (Cabecera HTML, Navbar, Selector WhatsApp Multi-País, Footer, Toast de Notificación y Modal de Pago) se definen **una sola vez** en `app/Views/layouts/` y `app/Views/partials/`.
3. **URLs Amigables y RESTful:** Rutas limpias (`/`, `/capacitaciones`, `/nec4`, `/contacto`, `/libro-de-reclamaciones`) gestionadas por un `Router` centralizado.
4. **Seguridad Integrada (Security Hardening):** Sanitización contra ataques XSS, protección contra inyección SQL con PDO, rate limiting y trampas Honeypot anti-spam.

---

## 📂 Estructura del Proyecto

```text
PMO-Solutions/
├── app/
│   ├── Config/
│   │   └── config.php               # Credenciales de BD, SMTP y variables de entorno
│   ├── Core/
│   │   ├── Autoloader.php           # Autocarga de clases PSR-4 sin Composer
│   │   ├── Router.php               # Enrutador de URLs (GET / POST)
│   │   ├── Controller.php           # Controlador base (render() y json())
│   │   ├── Model.php                # Modelo base (conexión PDO)
│   │   ├── View.php                 # Motor de renderizado con Layouts y Partials
│   │   ├── Database.php             # Conexión Singleton segura a MySQL con PDO
│   │   ├── Security.php             # Sanitización XSS, Honeypot y Rate Limiting
│   │   └── SmtpMailer.php           # Envío de correos por sockets TLS/SSL
│   ├── Controllers/
│   │   ├── HomeController.php       # Portada principal
│   │   ├── CourseController.php     # Catálogo y landings de las 11 especializaciones
│   │   ├── ContactController.php    # Formulario y API de contacto
│   │   ├── ClaimController.php      # Formulario y API del Libro de Reclamaciones
│   │   └── ErrorController.php      # Página 404 interactiva
│   ├── Models/
│   │   ├── CourseModel.php          # Catálogo estructurado de cursos y temarios
│   │   ├── ContactModel.php         # Validación y persistencia de mensajes
│   │   └── ClaimModel.php           # Gestión legal de reclamos (Ley 29571)
│   └── Views/
│       ├── layouts/
│       │   ├── main.php             # Layout maestro (HTML5, head, scripts comunes)
│       │   └── error.php            # Layout para páginas de error
│       ├── partials/
│       │   ├── navbar.php           # Barra de navegación responsive
│       │   ├── footer.php           # Pie de página y selector WhatsApp por país
│       │   └── toast.php            # Toast emergente de Matrículas 2026 (CRO)
│       └── pages/
│           ├── home.php             # Vista de portada
│           ├── capacitaciones.php   # Catálogo interactivo de cursos
│           ├── courses/             # Vistas individuales de cursos
│           │   ├── nec4.php
│           │   ├── primavera-p6.php
│           │   ├── dab-jrd.php
│           │   ├── vdc-bim.php
│           │   ├── contratos-estado.php
│           │   ├── analisis-cuantitativo.php
│           │   ├── analisis-forense.php
│           │   ├── eventos-compensables.php
│           │   ├── compliance.php
│           │   └── riesgos-pmi.php
│           ├── contacto.php         # Formulario de contacto
│           ├── libro-de-reclamaciones.php # Formulario de reclamos INDECOPI
│           └── 404.php              # Escenario 404 animado
├── css/
│   └── styles.css                   # Hoja de estilos corporativa PMO Solutions
├── js/
│   └── main.js                      # Lógica interactiva del cliente (AJAX, Filtros, Toast)
├── img/                             # Recursos multimedia, logotipos e insignias
├── tests/
│   ├── SecurityTest.php             # Suite de pruebas unitarias de seguridad
│   └── route_test.php               # Suite de verificación de renderizado de rutas
├── index.php                        # Front Controller (Punto de entrada único)
├── .htaccess                        # Reescritura a index.php, forzado HTTPS y seguridad
├── robots.txt                       # Directivas de rastreo para Google/Bing
└── sitemap.xml                      # Mapa del sitio oficial
```

---

## 🔄 Flujo de una Petición (Request Lifecycle)

1. **Cliente:** El usuario ingresa a `https://pmo-solutions.com/nec4`.
2. **Servidor Web (.htaccess):** Apache detecta que no es un archivo físico y reescribe la petición a `index.php`.
3. **Front Controller (index.php):**
   - Registra el `Autoloader`.
   - Inicializa el `Router`.
   - Busca la ruta coincidente y delega en `CourseController::nec4()`.
4. **Controlador (CourseController):**
   - Solicita la información del curso al `CourseModel`.
   - Llama a `$this->render('courses/nec4', $data)`.
5. **Vista (View):**
   - Renderiza `app/Views/pages/courses/nec4.php` dentro del layout `app/Views/layouts/main.php`.
   - Incluye los partials (`navbar.php`, `footer.php`, `toast.php`, `payment-modal.php`).
6. **Respuesta:** Se devuelve el HTML5 completo y compilado al navegador.

---

## 🧪 Ejecución de Pruebas Unitarias y de Rutas

Desde la terminal del proyecto:

### 1. Pruebas de Seguridad y Sanitización (25 Aserciones)
```powershell
php tests/SecurityTest.php
```

### 2. Pruebas de Renderizado de Rutas MVC
```powershell
php tests/route_test.php
```

---

## 🚀 Despliegue en Servidores (GoDaddy / cPanel)

1. Sube todo el contenido de la carpeta `PMO-Solutions/` directamente al directorio `public_html/` de tu hosting.
2. Crea la base de datos MySQL en cPanel e importa `backend/schema.sql`.
3. Configura tus credenciales reales en `app/Config/config.php` y activa `'enabled' => true` en la sección de base de datos.

