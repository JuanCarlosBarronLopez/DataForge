<?php

declare(strict_types=1);

namespace DataForge\Service;

use DataForge\Exception\ValidationException;

/**
 * Almacenamiento de avatares de usuario.
 *
 * Los archivos se guardan fuera del directorio publico y se vuelven a servir
 * a traves de una ruta controlada. Cada archivo se valida por contenido (no
 * solo por extension) y se vuelve a codificar con GD, de modo que un archivo
 * que se haga pasar por imagen no llega a ejecutarse como PHP.
 */
final class AvatarService
{
    private const ALLOWED = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    private const MAX_WIDTH  = 2048;
    private const MAX_HEIGHT = 2048;

    public function __construct(
        private readonly string $directory,
        private readonly int $maxBytes = 2_097_152,
    ) {}

    /**
     * Valida y guarda un avatar. Devuelve el nombre del archivo almacenado.
     *
     * @param array<string, mixed> $file
     *
     * @throws ValidationException
     */
    public function store(array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new ValidationException([$this->uploadErrorMessage($error)]);
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        $size          = (int) ($file['size'] ?? 0);

        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new ValidationException(['No se recibio ningun archivo valido.']);
        }

        if ($size <= 0 || $size > $this->maxBytes) {
            throw new ValidationException([
                'La imagen supera el tamano maximo permitido de '.round($this->maxBytes / 1024 / 1024, 1).' MB.',
            ]);
        }

        $mime = $this->detectMime($temporaryPath);
        if (!isset(self::ALLOWED[$mime])) {
            throw new ValidationException(['El archivo debe ser una imagen PNG, JPEG o WebP.']);
        }

        $dimensions = @getimagesize($temporaryPath);
        if ($dimensions === false) {
            throw new ValidationException(['El archivo no es una imagen legible.']);
        }

        [$width, $height] = $dimensions;
        if ($width > self::MAX_WIDTH || $height > self::MAX_HEIGHT) {
            throw new ValidationException([
                'La imagen excede '.self::MAX_WIDTH.'x'.self::MAX_HEIGHT.' pixeles.',
            ]);
        }

        $this->ensureDirectory();

        $extension = self::ALLOWED[$mime];
        $filename  = bin2hex(random_bytes(16)).'.'.$extension;
        $target    = $this->directory.'/'.$filename;

        if (!$this->reencode($temporaryPath, $target, $mime)) {
            throw new ValidationException(['No se pudo procesar la imagen.']);
        }

        @chmod($target, 0o640);

        return $filename;
    }

    /**
     * Elimina un avatar almacenado. El nombre se valida para impedir que la
     * ruta resuelva fuera del directorio de destino.
     */
    public function delete(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        if (preg_match('/^[a-f0-9]{32}\.(png|jpg|webp)$/', $filename) !== 1) {
            return;
        }

        $path = $this->directory.'/'.$filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function path(string $filename): string
    {
        return $this->directory.'/'.$filename;
    }

    public function exists(string $filename): bool
    {
        return preg_match('/^[a-f0-9]{32}\.(png|jpg|webp)$/', $filename) === 1
            && is_file($this->directory.'/'.$filename);
    }

    private function ensureDirectory(): void
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0o750, true);
        }
    }

    private function detectMime(string $path): string
    {
        if (!function_exists('finfo_open')) {
            throw new ValidationException(['El servidor no puede validar el tipo de archivo (ext finfo ausente).']);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new ValidationException(['El servidor no puede validar el tipo de archivo.']);
        }

        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return is_string($mime) ? $mime : '';
    }

    /**
     * Vuelve a codificar la imagen para eliminar metadatos y contenido
     * incrustado. Devuelve false si GD no puede procesar el formato.
     */
    private function reencode(string $source, string $target, string $mime): bool
    {
        $image = match ($mime) {
            'image/png'  => @imagecreatefrompng($source),
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default      => false,
        };

        if (!$image instanceof \GdImage) {
            return false;
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        $canvas = imagecreatetruecolor($width, $height);
        if (!$canvas instanceof \GdImage) {
            imagedestroy($image);

            return false;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        $written = match ($mime) {
            'image/png'  => imagepng($canvas, $target, 6),
            'image/jpeg' => imagejpeg($canvas, $target, 85),
            'image/webp' => function_exists('imagewebp') ? imagewebp($canvas, $target, 85) : false,
            default      => false,
        };

        imagedestroy($canvas);
        imagedestroy($image);

        return $written;
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamano maximo permitido.',
            UPLOAD_ERR_PARTIAL                        => 'La carga de la imagen se completo solo parcialmente.',
            UPLOAD_ERR_NO_FILE                        => 'No se selecciono ninguna imagen.',
            UPLOAD_ERR_NO_TMP_DIR                     => 'El servidor no tiene un directorio temporal disponible.',
            UPLOAD_ERR_CANT_WRITE                     => 'El servidor no pudo escribir el archivo temporal.',
            UPLOAD_ERR_EXTENSION                      => 'Una extension de PHP impidio completar la carga.',
            default                                   => 'No se pudo cargar la imagen.',
        };
    }
}
