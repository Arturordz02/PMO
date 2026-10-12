<?php
/**
 * PMO SOLUTIONS - Plantilla de Configuración PHP Privada (Alternativa a .env)
 * 
 * Si prefiere un archivo PHP nativo en lugar de .env,
 * copie este archivo como 'private_app/env.php' y complete sus credenciales reales.
 * Este archivo reside FUERA de public_html y está completamente protegido del acceso web.
 */
return [
    'PMO_APP_ENV'         => 'production',
    'PMO_APP_DEBUG'       => 'false',
    'PMO_SITE_URL'        => 'https://pmo-solutions.com',
    'PMO_DB_HOST'         => '',
    'PMO_DB_NAME'         => '',
    'PMO_DB_USER'         => '',
    'PMO_DB_PASSWORD'     => '',
    'PMO_SMTP_HOST'       => '',
    'PMO_SMTP_PORT'       => '587',
    'PMO_SMTP_USER'       => '',
    'PMO_SMTP_PASSWORD'   => '',
    'PMO_SMTP_ENCRYPTION' => 'tls',
    'PMO_SMTP_FROM'       => '',

    // Consulta de Documentos Peruanos (Opcional / Plan Gratis sin tarjeta)
    'PMO_DOC_API_PROVIDER' => 'decolecta',
    'PMO_DOC_API_TOKEN'    => '',
];

