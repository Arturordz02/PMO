<?php
/**
 * PMO SOLUTIONS - Configuración del Módulo Chatbot Guiado
 *
 * Para activar o desactivar el chatbot en todo el sitio, modifica 'chatbot_enabled':
 *   - true  : Activa el chatbot en el layout principal.
 *   - false : Desactiva completamente el chatbot (cero HTML, CSS y JS en la respuesta).
 */

return [
    'chatbot_enabled' => true,
    'version'         => '1.0.0',
    'bot_name'        => 'Asistente PMO',
    'bot_tagline'     => 'Construimos Soluciones',
    'whatsapp_url'    => 'https://api.whatsapp.com/send?phone=51944276649',
];

 