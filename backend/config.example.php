<?php
/**
 * PMO SOLUTIONS - Plantilla de Configuración (Ejemplo)
 * 
 * Copie este archivo como 'config.php' y complete los valores correspondientes
 * a su servidor SMTP y base de datos MySQL.
 */

if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}

return [
    'app' => [
        'name'             => 'PMO Solutions S.A.C.',
        'ruc'              => '20600000000',
        'legal_address'    => 'Av. Javier Prado 757, piso 10, Magdalena, Lima 17, Perú',
        'site_url'         => 'https://pmo-solutions.com',
        'timezone'         => 'America/Lima',
        'environment'      => 'production',
        'debug'            => false,
    ],

    'smtp' => [
        'host'             => 'mail.pmo-solutions.com',
        'port'             => 587,
        'encryption'       => 'tls', // 'tls' (587), 'ssl' (465) o null
        'auth'             => true,
        'username'         => 'comercial@pmo-solutions.com',
        'password'         => 'TU_PASSWORD_SMTP_AQUI',
        'from_email'       => 'comercial@pmo-solutions.com',
        'from_name'        => 'PMO Solutions - Notificaciones',
        'admin_email'      => 'comercial@pmo-solutions.com',
        'admin_name'       => 'Administración PMO Solutions',
        'timeout'          => 15,
    ],

    'database' => [
        'enabled'          => false, // Cambiar a true al tener credenciales activas
        'host'             => 'localhost',
        'port'             => 3306,
        'name'             => 'pmo_solutions_db',
        'user'             => 'pmo_db_user',
        'password'         => 'TU_PASSWORD_DB_AQUI',
        'charset'          => 'utf8mb4',
    ],

    'security' => [
        'honeypot_field'   => 'website_hp',
        'allowed_origins'  => [
            'https://pmo-solutions.com',
            'https://www.pmo-solutions.com',
            'http://localhost',
            'http://127.0.0.1'
        ],
        'rate_limit_enabled' => true,
        'rate_limit_requests' => 10,
        'rate_limit_window'   => 300,
    ]
];

