# Guía de Despliegue en Producción — PMO Solutions

Esta guía detalla los pasos para realizar la preparación, aprovisionamiento, despliegue y verificación de PMO Solutions en un servidor de hosting cPanel / Apache (como GoDaddy u otro proveedor compatible).

---

## 1. Requisitos de Plataforma PHP y Extensiones

- **Versión PHP**: PHP 8.0.30 o superior (compatible con PHP 8.0.x, 8.1.x, 8.2.x y 8.3.x).
- **Extensiones requeridas**:
  - `pdo` (Acceso a base de datos)
  - `pdo_mysql` (Driver MySQL para persistencia transaccional)
  - `openssl` (Criptografía, tokens CSRF y hashing seguro)
  - `mbstring` (Manipulación de cadenas multibyte / UTF-8)
  - `json` (Codificación y decodificación de payloads JSON)
  - `fileinfo` (Detección de tipos MIME para subida de adjuntos)
  - `zip` (Manejo de archivos comprimidos)
- **Configuración en cPanel**:
  1. Ir a **Select PHP Version** (o Administrador MultiPHP).
  2. Seleccionar PHP 8.0, 8.1, 8.2 o 8.3.
  3. En la pestaña **Extensions**, marcar las 7 extensiones requeridas.
  4. En **Options**, configurar:
     - `display_errors = Off`
     - `log_errors = On`
     - `upload_max_filesize = 5M`
     - `post_max_size = 8M`
     - `memory_limit = 128M` o superior.

---

## 2. Base de Datos MySQL e Importación de Esquema

1. **Crear Base de Datos**:
   - En cPanel > **MySQL Databases**, crear una nueva base de datos (ej. `pmosol_prod_db`).
   - Crear un usuario MySQL con contraseña aleatoria y segura.
   - Asociar el usuario a la base de datos otorgando **ALL PRIVILEGES**.
2. **Importar Esquema Canónico**:
   - Abrir **phpMyAdmin**.
   - Seleccionar la base de datos creada.
   - Ir a la pestaña **Importar** y subir el archivo:
     `backend/schema_v2.sql`
   - Verificar la creación de tablas: `evaluaciones`, `contactos`, `reclamaciones`, `outbox_emails`, `idempotency_keys`, `admins` con collation `utf8mb4_unicode_ci`.

---

## 3. Configuración de Entorno en GoDaddy / Servidor (Variables `PMO_*`)

> **En producción, la aplicación NO carga automáticamente archivos `.env`.**
> El archivo `.env` está diseñado exclusivamente como una opción local de desarrollo (cuando `PMO_APP_ENV=development`) y no debe desplegarse ni existir en el servidor web de producción.

### Opción Principal (Recomendada): Variables de Entorno en el Servidor / cPanel

En GoDaddy o hosting cPanel / Apache, configure las variables de entorno reales del sistema con prefijo `PMO_*` mediante las directivas de servidor (por ejemplo, directivas `SetEnv` en Apache / FastCGI o variables de entorno PHP en cPanel):

- **Entorno y Seguridad**:
  - `PMO_APP_ENV=production`
  - `PMO_APP_DEBUG=false`
  - `PMO_SITE_URL=https://pmosolutions.pe`
- **Base de Datos MySQL**:
  - `PMO_DB_ENABLED=true`
  - `PMO_DB_HOST=localhost`
  - `PMO_DB_PORT=3306`
  - `PMO_DB_NAME=nombre_de_tu_bd`
  - `PMO_DB_USER=usuario_de_tu_bd`
  - `PMO_DB_PASSWORD=contraseña_segura_de_tu_bd`
- **Servidor de Correo SMTP**:
  - `PMO_SMTP_HOST=mail.pmosolutions.pe`
  - `PMO_SMTP_PORT=465` (o `587`)
  - `PMO_SMTP_ENCRYPTION=ssl` (o `tls`)
  - `PMO_SMTP_USER=contacto@pmosolutions.pe`
  - `PMO_SMTP_PASSWORD=contraseña_smtp_segura`
- **Desactivación de Módulos Inactivos**:
  - `PMO_ADMIN_ENABLED=false`
  - `PMO_REPORT_RETRIEVAL_ENABLED=false`

### Opción Alternativa (Si el Hosting no permite Variables de Entorno del Sistema)

Si el entorno de hosting compartido no permite definir variables de entorno del servidor ni directivas `SetEnv`, la alternativa segura recomendada es:
- Ubicar un archivo de configuración PHP privado en una ruta externa fuera de la raíz web pública (`public_html`), por ejemplo en `/home/USUARIO/config_secure.php`, con permisos restringidos (`600`).
- Dicho archivo debe devolver un array con los valores requeridos y ser cargado por el punto de entrada, asegurando que nunca sea accesible vía HTTP ni forme parte del repositorio público.
*(Nota: No cree este archivo en el repositorio ni incluya credenciales en el código fuente).*

### Certificado SSL / HTTPS
- Instalar certificado SSL/TLS en cPanel (**AutoSSL** o Let's Encrypt).
- El archivo `.htaccess` raíz ya incluye directivas para forzar HTTPS automáticamente y configurar Content-Security-Policy (CSP), HSTS y cabeceras de seguridad.

---

## 4. Permisos de Archivos y Directorios de Almacenamiento

1. **Estructura de Directorios**:
   - Establecer permisos `755` para carpetas y `644` para archivos estándar.
   - Las carpetas dentro de `storage/` deben tener permisos de escritura para el proceso web (`755` o `775` según configuración de suPHP/PHP-FPM):
     - `storage/cache/`
     - `storage/logs/`
     - `storage/outbox/`
     - `storage/reports/`
2. **Protección HTTP de Almacenamiento**:
   - Verificar que los archivos `storage/.htaccess` y `storage/outbox/.htaccess` estén presentes (`Require all denied` / HTTP 403).

---

## 5. Configuración de Tareas Cron (Procesamiento de Correo Outbox)

Para garantizar el envío asíncrono y tolerante a fallos de los correos electrónicos mediante la cola transaccional:

1. En cPanel, ir a **Cron Jobs** (Tareas Cron).
2. **Procesador de Outbox (cada 1 o 2 minutos)**:
   - Intervalo: `*/2 * * * *` (o `* * * * *`)
   - Comando:
     ```bash
     /usr/local/bin/php /home/USUARIO/public_html/artisan/process-outbox.php > /dev/null 2>&1
     ```
3. **Rotación y Archivado de Logs (semanal)**:
   - Intervalo: `0 2 * * 0` (Todos los domingos a las 02:00 AM)
   - Comando:
     ```bash
     /usr/local/bin/php /home/USUARIO/public_html/artisan/archive-logs.php > /dev/null 2>&1
     ```

*(Reemplazar `/home/USUARIO/public_html` por la ruta absoluta real del hosting).*

---

## 6. Verificación Post-Despliegue

Realizar las siguientes pruebas funcionales en el sitio en vivo:

1. **Rutas Públicas Canónicas**:
   - `GET /` — Inicio
   - `GET /nosotros` — Quiénes somos
   - `GET /servicios` — Servicios corporativos
   - `GET /contacto` — Contacto y mapa Google Maps interactivo
   - `GET /libro-de-reclamaciones` — Formulario de reclamos
   - `GET /evaluacion-habilidades` — Test de habilidades blandas
   - `GET /politica-de-privacidad` y `GET /terminos-y-condiciones` — Políticas legales
2. **Envío de Formularios**:
   - Formulario de Contacto: Enviar mensaje de prueba vía `POST /contacto/submit` y comprobar respuesta JSON `{"success":true,...}`.
   - Libro de Reclamaciones: Registrar reclamo de prueba vía `POST /reclamaciones/submit` y verificar generación de código correlativo `REC-YYYY-...`.
   - Evaluación de Habilidades: Completar test de 24 preguntas vía `POST /api/evaluacion-habilidades` y verificar generación de reporte PDF / gráfico de radar.
3. **Entrega de Correos**:
   - Comprobar que los registros se inserten en la tabla `outbox_emails` y sean procesados a estado `sent` por el worker cron.
4. **Endpoints Deshabilitados**:
   - Comprobar que `/admin/login`, `/admin/logout` y `/evaluacion-habilidades/resultados` respondan HTTP 404.

---

## 7. Plan de Rollback (Reversión ante Contingencias)

Si se detecta alguna anomalía crítica durante o después del despliegue:

1. **Reversión de Código**:
   - Restaurar el paquete o copia de seguridad anterior en el directorio raíz.
2. **Reversión de Base de Datos**:
   - Si se aplicaron migraciones incrementales, ejecutar los scripts de rollback correspondientes:
     - `backend/migrations/002_rollback.sql`
     - `backend/migrations/001_rollback.sql`
   - O restaurar el dump MySQL de respaldo previo al despliegue.
3. **Limpieza de Caché**:
   - Vaciar los archivos `.cache` en `storage/cache/`.
4. **Verificación de Logs**:
   - Inspeccionar `storage/logs/app-YYYY-MM-DD.log` para diagnosticar la causa raíz del incidente.
