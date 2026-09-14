<?php
/**
 * PMO SOLUTIONS - Configuración Global de la Aplicación
 * 
 * Centraliza parámetros de entorno, credenciales de Base de Datos,
 * configuración del servidor de correo SMTP y políticas de seguridad.
 * 
 * Todas las credenciales sensibles se leen desde variables de entorno (PMO_*)
 * para evitar almacenar secretos dentro del código fuente.
 */

if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}

use App\Core\Env;

// Cargar .env si está disponible en entornos locales de desarrollo
Env::load();

$environment = Env::string('PMO_APP_ENV', 'production');
$debug = Env::bool('PMO_APP_DEBUG', false);

// REGLA DE SEGURIDAD ESTRICTA: En entorno de producción, debug siempre es false
if ($environment === 'production') {
    $debug = false;
}

$dbEnabled = Env::bool('PMO_DB_ENABLED', false);
$dbUser    = Env::string('PMO_DB_USER', '');
$dbName    = Env::string('PMO_DB_NAME', '');
$dbPass    = Env::string('PMO_DB_PASSWORD', '');

// Si falta usuario, nombre de base de datos o contraseña, deshabilitar automáticamente por seguridad
if (empty($dbUser) || empty($dbName) || empty($dbPass)) {
    $dbEnabled = false;
}

return [
    // -------------------------------------------------------------------------
    // 1. Información General de la Aplicación
    // -------------------------------------------------------------------------
    'app' => [
        'name'          => 'PMO Solutions S.A.C.',
        'ruc'           => '20600000000',
        'legal_address' => 'Av. Javier Prado 757, piso 10, Magdalena, Lima 17, Perú',
        'site_url'      => Env::string('PMO_SITE_URL', 'https://pmo-solutions.com'),
        'timezone'      => 'America/Lima',
        'environment'   => $environment,
        'debug'         => $debug,
    ],

    // -------------------------------------------------------------------------
    // 2. Servidor de Correo SMTP Corporativo
    // -------------------------------------------------------------------------
    'smtp' => [
        'host'        => Env::string('PMO_SMTP_HOST', 'mail.pmo-solutions.com'),
        'port'        => Env::int('PMO_SMTP_PORT', 587),
        'encryption'  => Env::string('PMO_SMTP_ENCRYPTION', 'tls'), // 'tls', 'ssl' o null
        'auth'        => true,
        'username'    => Env::string('PMO_SMTP_USER', 'comercial@pmo-solutions.com'),
        'password'    => Env::string('PMO_SMTP_PASSWORD', ''),
        'from_email'  => Env::string('PMO_SMTP_USER', 'comercial@pmo-solutions.com'),
        'from_name'   => 'PMO Solutions - Notificaciones',
        'admin_email' => 'comercial@pmo-solutions.com',
        'admin_name'  => 'Administración PMO Solutions',
        'timeout'     => 15,
    ],

    // -------------------------------------------------------------------------
    // 3. Base de Datos MySQL (PDO)
    // -------------------------------------------------------------------------
    'database' => [
        'enabled'  => $dbEnabled,
        'host'     => Env::string('PMO_DB_HOST', 'localhost'),
        'port'     => Env::int('PMO_DB_PORT', 3306),
        'name'     => $dbName,
        'user'     => $dbUser,
        'password' => $dbPass,
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
        'rate_limits'         => [
            'contact'          => [
                'requests' => Env::int('PMO_RL_CONTACT_REQ', 10),
                'window'   => Env::int('PMO_RL_CONTACT_WIN', 300), // 5 min
            ],
            'claim'            => [
                'requests' => Env::int('PMO_RL_CLAIM_REQ', 10),
                'window'   => Env::int('PMO_RL_CLAIM_WIN', 300), // 5 min
            ],
            'soft_skills_eval' => [
                'requests' => Env::int('PMO_RL_EVAL_REQ', 20),
                'window'   => Env::int('PMO_RL_EVAL_WIN', 600), // 10 min
            ],
            'report_access'    => [
                'requests' => Env::int('PMO_RL_REPORT_REQ', 15),
                'window'   => Env::int('PMO_RL_REPORT_WIN', 300), // 5 min
            ],
            'auth_login'       => [
                'requests' => Env::int('PMO_RL_AUTH_REQ', 5),
                'window'   => Env::int('PMO_RL_AUTH_WIN', 900), // 15 min
            ],
            'default'          => [
                'requests' => Env::int('PMO_RL_DEFAULT_REQ', 60),
                'window'   => Env::int('PMO_RL_DEFAULT_WIN', 60), // 1 min
            ],
        ],
        'rate_limit_requests' => 10,
        'rate_limit_window'   => 300, // 5 minutos en segundos
        'admin_username'      => '',
        'admin_password_hash' => '',
        'trusted_proxies'     => Env::array('PMO_TRUSTED_PROXIES', []),
    ],

    // -------------------------------------------------------------------------
    // 5. Control de Funcionalidades (Feature Flags)
    // -------------------------------------------------------------------------
    'features' => [
        'admin_enabled'            => false, // Administración deshabilitada en esta versión
        'report_retrieval_enabled' => false, // Consulta posterior por código/token deshabilitada
    ]
];
