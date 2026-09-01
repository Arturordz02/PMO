<?php
/**
 * PMO SOLUTIONS - Configuración Global de la Aplicación
 * 
 * Centraliza parámetros de entorno, credenciales de Base de Datos,
 * configuración del servidor de correo SMTP y políticas de seguridad.
 */

if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}

return [
    // -------------------------------------------------------------------------
    // 1. Información General de la Aplicación
    // -------------------------------------------------------------------------
    'app' => [
        'name'          => 'PMO Solutions S.A.C.',
        'ruc'           => '20600000000',
        'legal_address' => 'Av. Javier Prado 757, piso 10, Magdalena, Lima 17, Perú',
        'site_url'      => 'https://pmo-solutions.com',
        'timezone'      => 'America/Lima',
        'environment'   => 'production', // 'development' o 'production'
        'debug'         => false,
    ],

    // -------------------------------------------------------------------------
    // 2. Servidor de Correo SMTP Corporativo
    // -------------------------------------------------------------------------
    'smtp' => [
        'host'        => 'mail.pmo-solutions.com',
        'port'        => 587,
        'encryption'  => 'tls', // 'tls', 'ssl' o null
        'auth'        => true,
        'username'    => 'comercial@pmo-solutions.com',
        'password'    => 'TU_PASSWORD_SMTP_AQUI',
        'from_email'  => 'comercial@pmo-solutions.com',
        'from_name'   => 'PMO Solutions - Notificaciones',
        'admin_email' => 'comercial@pmo-solutions.com',
        'admin_name'  => 'Administración PMO Solutions',
        'timeout'     => 15,
    ],

    // -------------------------------------------------------------------------
    // 3. Base de Datos MySQL (PDO)
    // -------------------------------------------------------------------------
    'database' => [
        'enabled'  => false, // Cambiar a true al configurar en cPanel
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'pmo_solutions_db',
        'user'     => 'pmo_db_user',
        'password' => 'TU_PASSWORD_DB_AQUI',
        'charset'  => 'utf8mb4',
    ],

    // -------------------------------------------------------------------------
    // 4. Parámetros de Seguridad y Anti-Spam
    // -------------------------------------------------------------------------
    'security' => [
        'honeypot_field'      => 'website_hp',
        'allowed_origins'     => [
            'https://pmo-solutions.com',
            'https://www.pmo-solutions.com',
            'http://localhost',
            'http://127.0.0.1'
        ],
        'rate_limit_enabled'  => true,
        'rate_limit_requests' => 10,
        'rate_limit_window'   => 300, // 5 minutos en segundos
    ]
];

