<?php
/**
 * PMO SOLUTIONS - Configuración del Backend
 * 
 * Este archivo centraliza la configuración de:
 * 1. Correo Corporativo SMTP
 * 2. Base de Datos MySQL (PDO)
 * 3. Parámetros de Seguridad y Aplicación
 * 
 * IMPORTANTE: Configure las credenciales reales de su servidor de hosting.
 * No comparta este archivo en repositorios públicos.
 */

// Evitar acceso directo si no está definido por el backend
if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}

return [
    // =========================================================================
    // 1. INFORMACIÓN DE LA APLICACIÓN / EMPRESA
    // =========================================================================
    'app' => [
        'name'             => 'PMO Solutions S.A.C.',
        'ruc'              => '20600000000', // Actualizar con RUC real de la empresa
        'legal_address'    => 'Av. Javier Prado 757, piso 10, Magdalena, Lima 17, Perú',
        'site_url'         => 'https://pmo-solutions.com',
        'timezone'         => 'America/Lima',
        'environment'      => 'production', // 'development' o 'production'
        'debug'            => false,        // En producción debe ser false
    ],

    // =========================================================================
    // 2. CONFIGURACIÓN DEL SERVIDOR DE CORREO SMTP
    // =========================================================================
    'smtp' => [
        // Servidor SMTP (ej. mail.pmo-solutions.com, smtp.gmail.com, smtp.office365.com, etc.)
        'host'             => 'mail.pmo-solutions.com',

        // Puerto SMTP: 587 para TLS/STARTTLS, 465 para SSL, o 25 (sin cifrado)
        'port'             => 587,

        // Tipo de cifrado: 'tls', 'ssl' o null (sin cifrado)
        'encryption'       => 'tls',

        // Requiere autenticación (usualmente true)
        'auth'             => true,

        // Usuario SMTP (usualmente su cuenta de correo corporativo)
        'username'         => 'comercial@pmo-solutions.com',

        // Contraseña de la cuenta de correo o contraseña de aplicación
        'password'         => 'TU_PASSWORD_SMTP_AQUI',

        // Correo remitente que aparecerá en el encabezado 'From'
        'from_email'       => 'comercial@pmo-solutions.com',
        'from_name'        => 'PMO Solutions - Notificaciones',

        // Correo receptor de las consultas de contacto y reclamos (Administración)
        'admin_email'      => 'comercial@pmo-solutions.com',
        'admin_name'       => 'Administración PMO Solutions',

        // Tiempo de espera para la conexión de socket en segundos
        'timeout'          => 15,
    ],

    // =========================================================================
    // 3. CONFIGURACIÓN DE BASE DE DATOS (MySQL / MariaDB con PDO)
    // =========================================================================
    'database' => [
        // Habilitar o Deshabilitar guardado en Base de Datos:
        // Mantener en 'false' hasta configurar las credenciales reales en el hosting.
        // Si está en 'false', el sistema seguirá enviando los correos normalmente.
        'enabled'          => false,

        // Servidor de base de datos (usualmente 'localhost' o IP asignada por el hosting)
        'host'             => 'localhost',

        // Puerto MySQL (por defecto 3306)
        'port'             => 3306,

        // Nombre de la base de datos creada en cPanel / phpMyAdmin
        'name'             => 'pmo_solutions_db',

        // Usuario de la base de datos con permisos otorgados
        'user'             => 'pmo_db_user',

        // Contraseña del usuario de la base de datos
        'password'         => 'TU_PASSWORD_DB_AQUI',

        // Codificación de caracteres recomendada para soporte completo UTF-8
        'charset'          => 'utf8mb4',
    ],

    // =========================================================================
    // 4. MEDIDAS DE SEGURIDAD Y PROTECCIÓN
    // =========================================================================
    'security' => [
        // Nombre del campo honeypot invisible para detectar bots de spam
        'honeypot_field'   => 'website_hp',

        // Orígenes permitidos para CORS (si el frontend se aloja en otro dominio)
        'allowed_origins'  => [
            'https://pmo-solutions.com',
            'https://www.pmo-solutions.com',
            'http://localhost',
            'http://127.0.0.1'
        ],

        // Límite de envíos por IP (minutos de bloqueo tras múltiples intentos)
        'rate_limit_enabled' => true,
        'rate_limit_requests' => 10,  // Máximo 10 envíos
        'rate_limit_window'   => 300, // Por cada 5 minutos (300 segundos)
    ]
];

