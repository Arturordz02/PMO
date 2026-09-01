# Skill: Bastionamiento de Seguridad Web y Protección de Datos

## Propósito
Implementar reglas de seguridad en el servidor y sanitización de código para proteger formularios y datos sensibles.

## Instrucciones para Antigravity
Cuando el usuario solicite auditar o proteger un formulario o el backend del sitio:
1. Aplica sanitización de entradas (Input Sanitization) en PHP/JS para prevenir inyección SQL y ataques XSS (Cross-Site Scripting).
2. Genera configuraciones de cabeceras HTTP seguras (`X-Frame-Options`, `Content-Security-Policy`) en `.htaccess` o servidores Nginx/Apache.
3. Asegura el cumplimiento de normativas de protección de datos personales (GDPR / Ley 29733).