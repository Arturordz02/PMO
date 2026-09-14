# Módulo de Chatbot Guiado para PMO Solutions

Módulo desacoplado de atención y orientación guiada para **PMO Solutions**, implementado en PHP 8.x nativo, JavaScript estándar (Vanilla JS) y CSS corporativo accesible.

No utiliza Inteligencia Artificial, bases de datos, cookies de rastreo ni servicios externos.

---

## 📁 Estructura del Módulo

```text
modules/chatbot/
├── config.php             # Configuración y bandera de activación (chatbot_enabled)
├── knowledge.json         # Base de conocimiento con los 10 cursos reales y rutas MVC
├── chatbot-view.php       # Vista accesible del componente y botón flotante
├── chatbot.css            # Estilos corporativos (todas las clases con prefijo 'pmo-chatbot-')
├── chatbot.js             # Lógica del árbol de decisiones guiado (Vanilla JS)
├── .htaccess              # Permisos Apache para servir assets y proteger configs
├── README.md              # Esta documentación
└── tests/
    └── ChatbotTest.php    # Suite de pruebas automatizadas del módulo
```

---

## ⚙️ Cómo Activar y Desactivar el Chatbot

El módulo cuenta con un interruptor centralizado en su archivo de configuración:

### Para Activar:
En `modules/chatbot/config.php`, asegúrate de que el valor sea `true`:
```php
return [
    'chatbot_enabled' => true,
    // ...
];
```

### Para Desactivar:
En `modules/chatbot/config.php`, cambia el valor a `false`:
```php
return [
    'chatbot_enabled' => false,
    // ...
];
```
*Cuando está desactivado, el sistema no emite ningún elemento HTML, hoja de estilos CSS ni script JavaScript en la respuesta de las vistas.*

---

## 🗑️ Cómo Eliminar Completamente el Chatbot

Si se desea desinstalar el módulo de forma permanente sin afectar el resto del sistema:

1. **Retirar el punto de montaje en el layout principal:**
   Abre el archivo `app/Views/layouts/main.php` y elimina el siguiente bloque (ubicado antes de `</body>`):
   ```php
   <!-- Chatbot Guiado PMO Solutions (Módulo Aislado) -->
   <?php
   $chatbotView = dirname(__DIR__, 3) . '/modules/chatbot/chatbot-view.php';
   if (file_exists($chatbotView)) {
       require $chatbotView;
   }
   ?>
   ```

2. **Eliminar el directorio del módulo:**
   Elimina físicamente la carpeta `modules/chatbot/`.

*Nota:* Si la carpeta `modules/chatbot/` es eliminada sin retirar el bloque en `main.php`, el sistema seguirá funcionando normalmente gracias a la verificación defensiva `file_exists()`.

---

## 🔒 Cumplimiento de Seguridad & CSP

- **Content Security Policy (CSP):** Compatible al 100% con la directiva estricta del proyecto (`script-src 'self' ...; style-src 'self' 'unsafe-inline' ...`).
- **Cero Inline Scripts & Handlers:** No se utilizan etiquetas `<script>` inline ni atributos `onclick`, `onload` o funciones `eval()`.
- **Versionado Automático:** Los assets se cargan mediante `View::asset()` (`/modules/chatbot/chatbot.css?v=...` y `/modules/chatbot/chatbot.js?v=...`).
- **Privacidad:** No se solicitan, registran ni almacenan datos personales ni cookies.

---

## 🧪 Ejecución de Pruebas Automatizadas

Para ejecutar la suite de pruebas del módulo desde la terminal:

```powershell
$env:PATH = "C:\xampp\php;" + $env:PATH
php modules/chatbot/tests/ChatbotTest.php
```

