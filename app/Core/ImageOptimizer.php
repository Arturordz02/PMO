<?php
namespace App\Core;

/**
 * PMO SOLUTIONS — Optimizador de Imágenes (ImageOptimizer)
 *
 * Convierte automáticamente imágenes PNG/JPG/JPEG a formato WebP
 * y opcionalmente AVIF, reduciendo el tamaño entre 25-50% sin pérdida
 * perceptual de calidad.
 *
 * Características:
 *  - Convierte a WebP con calidad configurable (default: 85)
 *  - Crea versiones @2x (original) y @1x (50% reducción)
 *  - Preserva imágenes originales (nunca las elimina)
 *  - Genera un manifiesto JSON con el mapeo original → WebP
 *  - Soporta GD (universal) e Imagick (si disponible, mejor calidad)
 *  - Almacena versiones optimizadas en storage/img/optimized/
 *
 * @example
 *   // Optimizar imagen individual
 *   $result = ImageOptimizer::convert('/path/to/img/photo.jpg');
 *
 *   // Optimizar directorio completo
 *   $report = ImageOptimizer::convertDirectory('/path/to/img/');
 *
 *   // Obtener URL WebP con fallback a original
 *   $src = ImageOptimizer::getOptimizedUrl('img/photo.jpg');
 */
class ImageOptimizer {

    /** Calidad WebP (0-100). 85 ofrece excelente balance calidad/tamaño */
    private const WEBP_QUALITY = 85;

    /** Directorio de imágenes optimizadas relativo al project root */
    private const OPTIMIZED_DIR = 'storage/img/optimized';

    /** Archivo de manifiesto de conversiones */
    private const MANIFEST_FILE = 'storage/img/optimized/manifest.json';

    /** Extensiones de imagen soportadas */
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

    /**
     * Convierte una imagen a WebP y crea versión reducida.
     *
     * @param  string $sourcePath Ruta absoluta a la imagen fuente
     * @param  bool   $forceRegen Forzar regeneración aunque ya exista el WebP
     * @return array              Resultado con rutas de versiones generadas
     */
    public static function convert(string $sourcePath, bool $forceRegen = false): array {
        $result = [
            'source'    => $sourcePath,
            'success'   => false,
            'webp_path' => null,
            'webp_url'  => null,
            'original_size'  => 0,
            'optimized_size' => 0,
            'saving_percent' => 0,
            'error'     => '',
        ];

        if (!file_exists($sourcePath)) {
            $result['error'] = "Archivo no encontrado: {$sourcePath}";
            return $result;
        }

        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (!in_array($extension, self::SUPPORTED_EXTENSIONS, true)) {
            $result['error'] = "Extensión no soportada: {$extension}";
            return $result;
        }

        $projectRoot  = self::resolveProjectRoot();
        $optimizedDir = $projectRoot . DIRECTORY_SEPARATOR . self::OPTIMIZED_DIR;

        // Crear directorio si no existe
        if (!is_dir($optimizedDir)) {
            @mkdir($optimizedDir, 0755, true);
        }

        // Generar nombre del archivo WebP (hash del path para evitar colisiones)
        $baseName    = pathinfo($sourcePath, PATHINFO_FILENAME);
        $safeHash    = substr(md5($sourcePath), 0, 8);
        $webpFile    = "{$baseName}_{$safeHash}.webp";
        $webpPath    = $optimizedDir . DIRECTORY_SEPARATOR . $webpFile;
        $webpPathSmall = $optimizedDir . DIRECTORY_SEPARATOR . "{$baseName}_{$safeHash}_sm.webp";

        // Si ya existe y no se fuerza regeneración, retornar info existente
        if (!$forceRegen && file_exists($webpPath)) {
            $result['success']        = true;
            $result['webp_path']      = $webpPath;
            $result['webp_path_sm']   = $webpPathSmall;
            $result['webp_url']       = self::pathToUrl($webpPath, $projectRoot);
            $result['original_size']  = filesize($sourcePath);
            $result['optimized_size'] = filesize($webpPath);
            $result['saving_percent'] = self::calculateSaving($result['original_size'], $result['optimized_size']);
            return $result;
        }

        // Usar Imagick si disponible (mejor calidad), sino GD
        if (extension_loaded('imagick')) {
            $conversionResult = self::convertWithImagick($sourcePath, $webpPath, $webpPathSmall);
        } else {
            $conversionResult = self::convertWithGD($sourcePath, $webpPath, $webpPathSmall);
        }

        if (!$conversionResult['success']) {
            $result['error'] = $conversionResult['error'];
            return $result;
        }

        $originalSize  = filesize($sourcePath);
        $optimizedSize = file_exists($webpPath) ? filesize($webpPath) : 0;

        $result['success']        = true;
        $result['webp_path']      = $webpPath;
        $result['webp_path_sm']   = $webpPathSmall;
        $result['webp_url']       = self::pathToUrl($webpPath, $projectRoot);
        $result['original_size']  = $originalSize;
        $result['optimized_size'] = $optimizedSize;
        $result['saving_percent'] = self::calculateSaving($originalSize, $optimizedSize);

        // Actualizar manifiesto
        self::updateManifest($sourcePath, $result, $projectRoot);

        return $result;
    }

    /**
     * Convierte todas las imágenes de un directorio.
     *
     * @param  string $directory Directorio con imágenes fuente
     * @param  bool   $recursive Buscar en subdirectorios
     * @return array             Reporte global de conversiones
     */
    public static function convertDirectory(string $directory, bool $recursive = false): array {
        $report = [
            'converted'    => 0,
            'skipped'      => 0,
            'failed'       => 0,
            'total_saved'  => 0,
            'errors'       => [],
        ];

        $pattern = $recursive
            ? iterator_to_array(new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
              ))
            : array_map(fn($f) => new \SplFileInfo($f), glob($directory . DIRECTORY_SEPARATOR . '*'));

        foreach ($pattern as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, self::SUPPORTED_EXTENSIONS, true)) {
                continue;
            }

            $result = self::convert($file->getRealPath());

            if ($result['success']) {
                $report['converted']++;
                $report['total_saved'] += max(0, $result['original_size'] - $result['optimized_size']);
            } elseif (!empty($result['error']) && str_contains($result['error'], 'no encontrado')) {
                $report['skipped']++;
            } else {
                $report['failed']++;
                $report['errors'][] = $result['error'];
            }
        }

        $report['total_saved_human'] = self::formatBytes($report['total_saved']);
        return $report;
    }

    /**
     * Retorna la URL del WebP optimizado para un asset dado, con fallback al original.
     * Útil para generar el atributo `src` o `srcset` en las vistas.
     *
     * @param  string $originalRelativePath Ruta relativa desde el proyecto (ej: 'img/photo.jpg')
     * @return array                        ['webp' => url|null, 'original' => url, 'has_webp' => bool]
     */
    public static function getOptimizedUrl(string $originalRelativePath): array {
        $projectRoot   = self::resolveProjectRoot();
        $manifestFile  = $projectRoot . DIRECTORY_SEPARATOR . self::MANIFEST_FILE;

        if (!file_exists($manifestFile)) {
            return ['webp' => null, 'original' => $originalRelativePath, 'has_webp' => false];
        }

        $manifest = json_decode(file_get_contents($manifestFile), true) ?? [];
        $absPath  = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $originalRelativePath);
        $key      = md5($absPath);

        if (isset($manifest[$key])) {
            return [
                'webp'      => $manifest[$key]['webp_url'],
                'webp_sm'   => $manifest[$key]['webp_url_sm'] ?? null,
                'original'  => $originalRelativePath,
                'has_webp'  => true,
                'saving'    => $manifest[$key]['saving_percent'] ?? 0,
            ];
        }

        return ['webp' => null, 'original' => $originalRelativePath, 'has_webp' => false];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Convierte imagen usando la extensión GD (fallback universal).
     */
    private static function convertWithGD(string $source, string $webpDest, string $webpDestSm): array {
        $ext   = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $image = match ($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($source),
            'png'         => imagecreatefrompng($source),
            'gif'         => imagecreatefromgif($source),
            default       => false,
        };

        if ($image === false) {
            return ['success' => false, 'error' => "GD no pudo cargar la imagen: {$source}"];
        }

        // Mantener transparencia para PNG
        if ($ext === 'png') {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        // Versión full (WebP @2x)
        $fullOk = imagewebp($image, $webpDest, self::WEBP_QUALITY);

        // Versión reducida al 50% (WebP @1x)
        $width  = imagesx($image);
        $height = imagesy($image);
        $small  = imagecreatetruecolor((int)($width / 2), (int)($height / 2));

        if ($ext === 'png') {
            imagealphablending($small, false);
            imagesavealpha($small, true);
            $transparent = imagecolorallocatealpha($small, 255, 255, 255, 127);
            imagefilledrectangle($small, 0, 0, (int)($width / 2), (int)($height / 2), $transparent);
        }

        imagecopyresampled($small, $image, 0, 0, 0, 0, (int)($width / 2), (int)($height / 2), $width, $height);
        imagewebp($small, $webpDestSm, self::WEBP_QUALITY);

        imagedestroy($image);
        imagedestroy($small);

        return ['success' => $fullOk !== false, 'error' => $fullOk === false ? "GD falló al escribir WebP" : ''];
    }

    /**
     * Convierte imagen usando Imagick (mayor calidad y control).
     */
    private static function convertWithImagick(string $source, string $webpDest, string $webpDestSm): array {
        try {
            $imagick = new \Imagick($source);
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality(self::WEBP_QUALITY);
            $imagick->stripImage(); // Eliminar metadatos EXIF para reducir tamaño

            // Versión completa
            $imagick->writeImage($webpDest);

            // Versión reducida al 50%
            $clone = clone $imagick;
            $clone->resizeImage(
                (int)($imagick->getImageWidth() / 2),
                (int)($imagick->getImageHeight() / 2),
                \Imagick::FILTER_LANCZOS,
                1
            );
            $clone->writeImage($webpDestSm);

            $imagick->destroy();
            $clone->destroy();

            return ['success' => true, 'error' => ''];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => "Imagick error: " . $e->getMessage()];
        }
    }

    /**
     * Actualiza el manifiesto JSON con la entrada de la imagen optimizada.
     */
    private static function updateManifest(string $sourcePath, array $result, string $projectRoot): void {
        $manifestFile = $projectRoot . DIRECTORY_SEPARATOR . self::MANIFEST_FILE;
        $manifest     = [];

        if (file_exists($manifestFile)) {
            $manifest = json_decode(@file_get_contents($manifestFile), true) ?? [];
        }

        $key = md5($sourcePath);
        $manifest[$key] = [
            'source'         => $sourcePath,
            'webp_path'      => $result['webp_path'],
            'webp_url'       => $result['webp_url'],
            'webp_url_sm'    => $result['webp_url'] ? str_replace('.webp', '_sm.webp', $result['webp_url']) : null,
            'original_size'  => $result['original_size'],
            'optimized_size' => $result['optimized_size'],
            'saving_percent' => $result['saving_percent'],
            'converted_at'   => date('Y-m-d H:i:s'),
        ];

        @file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Convierte una ruta absoluta a URL relativa del proyecto.
     */
    private static function pathToUrl(string $absolutePath, string $projectRoot): string {
        $relative = str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $absolutePath);
        return '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    }

    /**
     * Calcula el porcentaje de ahorro.
     */
    private static function calculateSaving(int $original, int $optimized): float {
        if ($original === 0) {
            return 0.0;
        }
        return round((($original - $optimized) / $original) * 100, 1);
    }

    /**
     * Formatea bytes a unidad legible.
     */
    private static function formatBytes(int $bytes): string {
        if ($bytes >= 1_048_576) return round($bytes / 1_048_576, 2) . ' MB';
        if ($bytes >= 1_024)     return round($bytes / 1_024, 2)     . ' KB';
        return $bytes . ' B';
    }

    /**
     * Resuelve la ruta raíz del proyecto.
     */
    private static function resolveProjectRoot(): string {
        return dirname(__DIR__, 2);
    }
}

