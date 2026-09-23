<?php
use App\Core\View;

/**
 * PMO SOLUTIONS - Vista del Chatbot Guiado Corporativo
 *
 * Módulo aislado: Renderiza el botón flotante y el panel de interacción guiado.
 * Solo genera salida si 'chatbot_enabled' es true en config.php.
 */

$configPath = __DIR__ . '/config.php';
$chatbotConfig = file_exists($configPath) ? require $configPath : [];
$chatbotEnabled = !empty($chatbotConfig['chatbot_enabled']);

if (!$chatbotEnabled) {
    return;
}

$knowledgePath = __DIR__ . '/knowledge.json';
$knowledgeJson = file_exists($knowledgePath) ? (string)file_get_contents($knowledgePath) : '{}';
?>

<!-- Hoja de estilos del Chatbot Guiado PMO Solutions (Versionada) -->
<link rel="stylesheet" href="<?= View::asset('/modules/chatbot/chatbot.css') ?>">

<!-- Contenedor Principal del Chatbot (Esquina Inferior Izquierda) -->
<div id="pmo-chatbot-root" class="pmo-chatbot-container" data-knowledge="<?= htmlspecialchars($knowledgeJson, ENT_QUOTES, 'UTF-8') ?>">

  <!-- Botón Flotante Disparador -->
  <button type="button" class="pmo-chatbot-trigger" aria-label="Abrir asistente virtual PMO Solutions" aria-expanded="false" aria-controls="pmo-chatbot-window">
    <i class="fas fa-comments pmo-chatbot-trigger-icon-open" aria-hidden="true"></i>
    <i class="fas fa-times pmo-chatbot-trigger-icon-close" aria-hidden="true"></i>
    <span class="pmo-chatbot-tooltip">¿Deseas orientación? Consúltanos</span>
  </button>

  <!-- Ventana / Panel del Asistente Virtual -->
  <section id="pmo-chatbot-window" class="pmo-chatbot-window" role="dialog" aria-modal="false" aria-labelledby="pmo-chatbot-title" aria-hidden="true">
    
    <!-- Encabezado del Asistente -->
    <header class="pmo-chatbot-header">
      <div class="pmo-chatbot-header-info">
        <div class="pmo-chatbot-avatar" aria-hidden="true">
          <i class="fas fa-robot"></i>
        </div>
        <div class="pmo-chatbot-header-text">
          <h2 id="pmo-chatbot-title" class="pmo-chatbot-title"><?= htmlspecialchars($chatbotConfig['bot_name'] ?? 'Asistente PMO', ENT_QUOTES, 'UTF-8') ?></h2>
          <span class="pmo-chatbot-status">
            <span class="pmo-chatbot-status-indicator" aria-hidden="true"></span>
            En línea
          </span>
        </div>
      </div>
      <button type="button" class="pmo-chatbot-close-btn" aria-label="Cerrar asistente virtual">
        <i class="fas fa-times" aria-hidden="true"></i>
      </button>
    </header>

    <!-- Cuerpo del Diálogo / Mensajes -->
    <div class="pmo-chatbot-body" role="log" aria-live="polite">
      <!-- El contenido interactivo y opciones guiadas son inyectados dinámicamente por chatbot.js -->
    </div>

    <!-- Pie de Página / Acciones Globales -->
    <footer class="pmo-chatbot-footer">
      <button type="button" class="pmo-chatbot-reset-btn" aria-label="Reiniciar conversación">
        <i class="fas fa-redo-alt" aria-hidden="true"></i>
        <span>Inicio</span>
      </button>
      <p class="pmo-chatbot-disclaimer">
        Orientación guiada • Sin recolección de datos
      </p>
    </footer>

  </section>

</div>

<!-- Script del Chatbot Guiado PMO Solutions (Versionado y diferido) -->
<script src="<?= View::asset('/modules/chatbot/chatbot.js') ?>" defer></script>

