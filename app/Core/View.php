<?php
namespace App\Core;

/**
 * PMO SOLUTIONS — Motor de Vistas v2 (View Engine)
 *
 * Gestiona el renderizado de vistas con:
 *  - Layouts maestros y componentes parciales reutilizables
 *  - Minificación automática del HTML output (elimina whitespace innecesario)
 *  - Inyección de <link rel="preload"> para assets críticos
 *  - Fingerprinting de assets JS/CSS para cache busting eficiente
 *  - Output buffering con compresión Gzip como fallback
 */
class View {

    /** Versión del manifest de assets (para cache busting global) */
    private static string $assetVersion = '';

    /** Assets críticos a precargar (CSS/JS above-the-fold) */
    private static array $criticalAssets = [
        ['href' => '/css/styles.css',     'as' => 'style'],
        ['href' => '/js/core/utils.js',   'as' => 'script'],
    ];

    /** Si activar la minificación HTML (desactivar en desarrollo) */
    private static bool $minifyHtml = true;

    /**
     * Renderiza una vista dentro de un Layout maestro.
     *
     * @param string      $view   Ruta relativa de la vista (ej. 'home', 'courses/nec4')
     * @param array       $data   Datos que se pasarán a la vista
     * @param string|null $layout Nombre del layout maestro (default 'main', null = sin layout)
     */
    public static function render(string $view, array $data = [], ?string $layout = 'main'): void {
        $baseDir  = dirname(__DIR__) . '/Views/';
        $viewFile = $baseDir . 'pages/' . ltrim($view, '/') . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("La vista '{$view}' no existe en '{$viewFile}'.");
        }

        // Inyectar versión de assets y utilidades de View
        $data['_assetVersion']   = self::getAssetVersion();
        $data['_criticalAssets'] = self::$criticalAssets;

        // Extraer variables para hacerlas accesibles en la vista
        extract($data, EXTR_SKIP);

        // ── Capturar contenido de la vista ───────────────────────────────────
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // ── Renderizar layout maestro ─────────────────────────────────────────
        if ($layout) {
            $layoutFile = $baseDir . 'layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new \Exception("El layout '{$layout}' no existe en '{$layoutFile}'.");
            }

            ob_start();
            require $layoutFile;
            $fullHtml = ob_get_clean();
        } else {
            $fullHtml = $content;
        }

        // ── Minificar HTML (en producción) ────────────────────────────────────
        if (self::$minifyHtml) {
            $fullHtml = self::minifyHtml($fullHtml);
        }

        echo $fullHtml;
    }

    /**
     * Incluye un componente parcial reutilizable.
     *
     * @param string $partial Nombre del parcial (ej. 'navbar', 'footer', 'toast')
     * @param array  $data    Datos adicionales para el parcial
     */
    public static function partial(string $partial, array $data = []): void {
        $baseDir     = dirname(__DIR__) . '/Views/';
        $partialFile = $baseDir . 'partials/' . ltrim($partial, '/') . '.php';

        if (file_exists($partialFile)) {
            extract($data, EXTR_SKIP);
            require $partialFile;
        }
    }

    /**
     * Retorna la URL versionada de un asset (CSS/JS) para cache busting.
     * Agrega ?v={mtime} basado en el filemtime del archivo físico, con fallback estable.
     *
     * @param  string $relativePath Ruta relativa al asset (ej. '/css/styles.css' o 'js/main.js')
     * @return string               URL normalizada con parámetro de versión
     *
     * @example
     *   echo View::asset('/css/styles.css');
     *   // → /css/styles.css?v=1725839000
     */
    public static function asset(string $relativePath): string {
        $cleanPath = explode('?', $relativePath)[0];
        $normalizedRel = '/' . ltrim($cleanPath, '/');
        
        $absolutePath = dirname(__DIR__, 2) . str_replace('/', DIRECTORY_SEPARATOR, $normalizedRel);

        $version = null;
        if (file_exists($absolutePath)) {
            clearstatcache(true, $absolutePath);
            $mtime = @filemtime($absolutePath);
            if ($mtime !== false && $mtime > 0) {
                $version = (string) $mtime;
            }
        }

        if ($version === null) {
            $version = self::getAssetVersion();
        }

        return $normalizedRel . '?v=' . $version;
    }



    /**
     * Genera las etiquetas <link rel="preload"> para los assets críticos.
     * Llámalo en el <head> del layout maestro.
     *
     * @return string HTML de los preload hints
     */
    public static function renderPreloadHints(): string {
        $html = '';
        foreach (self::$criticalAssets as $asset) {
            $as      = htmlspecialchars($asset['as'], ENT_QUOTES, 'UTF-8');
            $href    = htmlspecialchars(self::asset($asset['href']), ENT_QUOTES, 'UTF-8');
            $crossorigin = ($asset['crossorigin'] ?? false) ? ' crossorigin' : '';
            $html   .= "<link rel=\"preload\" href=\"{$href}\" as=\"{$as}\"{$crossorigin}>\n    ";
        }
        return $html;
    }

    /**
     * Genera etiqueta <picture> con soporte WebP/AVIF y fallback al original.
     * Usa el manifiesto de ImageOptimizer si existe.
     *
     * @param  string $src    Ruta relativa de la imagen (ej. 'img/photo.jpg')
     * @param  string $alt    Texto alternativo
     * @param  string $class  Clases CSS adicionales
     * @param  bool   $lazy   Si agregar loading="lazy"
     * @return string         Etiqueta <picture> o <img> con fallback
     */
    public static function picture(string $src, string $alt = '', string $class = '', bool $lazy = true): string {
        $optimized = ImageOptimizer::getOptimizedUrl($src);
        $loading   = $lazy ? ' loading="lazy"' : '';
        $altSafe   = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        $classSafe = $class ? " class=\"{$class}\"" : '';
        $srcSafe   = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');

        if ($optimized['has_webp']) {
            $webpSafe = htmlspecialchars($optimized['webp'], ENT_QUOTES, 'UTF-8');
            $smSafe   = !empty($optimized['webp_sm'])
                ? htmlspecialchars($optimized['webp_sm'], ENT_QUOTES, 'UTF-8')
                : $webpSafe;

            return <<<HTML
<picture>
  <source type="image/webp" srcset="{$smSafe} 640w, {$webpSafe} 1280w" sizes="(max-width:640px) 640px, 1280px">
  <img src="{$srcSafe}" alt="{$altSafe}"{$classSafe}{$loading} decoding="async">
</picture>
HTML;
        }

        return "<img src=\"{$srcSafe}\" alt=\"{$altSafe}\"{$classSafe}{$loading} decoding=\"async\">";
    }

    /**
     * Helper para escapar HTML y prevenir XSS.
     *
     * @param  mixed $value Valor a escapar
     * @return string       Valor escapado
     */
    public static function e(mixed $value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Activa/desactiva la minificación HTML.
     */
    public static function setMinifyHtml(bool $enabled): void {
        self::$minifyHtml = $enabled;
    }

    /**
     * Agrega un asset crítico al listado de preload hints.
     */
    public static function addCriticalAsset(string $href, string $as, bool $crossorigin = false): void {
        self::$criticalAssets[] = compact('href', 'as', 'crossorigin');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE METHODS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Minifica el HTML eliminando whitespace innecesario.
     * Preserva el contenido dentro de <pre>, <textarea>, <script> y <style>.
     *
     * @param  string $html HTML completo a minificar
     * @return string       HTML minificado
     */
    private static function minifyHtml(string $html): string {
        // Extraer bloques que no deben minificarse
        $preserve  = [];
        $pattern   = '/(<pre[^>]*>.*?<\/pre>|<textarea[^>]*>.*?<\/textarea>|<script[^>]*>.*?<\/script>|<style[^>]*>.*?<\/style>)/is';

        $html = preg_replace_callback($pattern, function ($match) use (&$preserve) {
            $token = '<!--PRESERVE_' . count($preserve) . '-->';
            $preserve[$token] = $match[0];
            return $token;
        }, $html);

        // Eliminar comentarios HTML (excepto IE conditionals y preservados)
        $html = preg_replace('/<!--(?!\[if|\s*PRESERVE_).*?-->/s', '', $html);

        // Colapsar whitespace entre tags y reducir múltiples espacios/saltos
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        $html = preg_replace('/\s+>/', '>', $html);
        $html = preg_replace('/>\s+/', '> ', $html);

        // Restaurar bloques preservados
        foreach ($preserve as $token => $original) {
            $html = str_replace($token, $original, $html);
        }

        return trim($html);
    }

    /**
     * Retorna la versión global de assets (hash del deploy o timestamp).
     */
    private static function getAssetVersion(): string {
        if (self::$assetVersion === '') {
            // Intentar leer de un archivo de versión generado en el deploy
            $versionFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'version.txt';
            if (file_exists($versionFile)) {
                self::$assetVersion = trim((string) file_get_contents($versionFile));
            } else {
                // Fallback: usar fecha del mes (se actualiza mensualmente)
                self::$assetVersion = date('Ym');
            }
        }

        return self::$assetVersion;
    }
}
