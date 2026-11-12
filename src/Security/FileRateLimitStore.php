<?php

declare(strict_types=1);

namespace DataForge\Security;

/**
 * Almacen de rate limiting sobre el sistema de archivos.
 *
 * Cada clave se guarda en un archivo propio. El contador se actualiza bajo
 * bloqueo exclusivo para que dos peticiones simultaneas no se cuenten como una.
 */
final class FileRateLimitStore implements RateLimitStore
{
    public function __construct(private readonly string $directory) {}

    public function hit(string $key, int $windowSeconds): array
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0o750, true) && !is_dir($this->directory)) {
            return ['count' => 1, 'ttl' => $windowSeconds];
        }

        $file   = $this->directory.'/'.hash('sha256', $key).'.json';
        $handle = @fopen($file, 'c+');

        if ($handle === false) {
            return ['count' => 1, 'ttl' => $windowSeconds];
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return ['count' => 1, 'ttl' => $windowSeconds];
            }

            $raw   = stream_get_contents($handle);
            $now   = time();
            $state = ['count' => 0, 'started_at' => $now];

            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && isset($decoded['count'], $decoded['started_at'])) {
                    $state = [
                        'count'      => (int) $decoded['count'],
                        'started_at' => (int) $decoded['started_at'],
                    ];
                }
            }

            if (($now - $state['started_at']) >= $windowSeconds) {
                $state = ['count' => 0, 'started_at' => $now];
            }

            $state['count']++;

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) json_encode($state));
            fflush($handle);
            flock($handle, LOCK_UN);

            $ttl = max(0, $windowSeconds - ($now - $state['started_at']));

            return ['count' => $state['count'], 'ttl' => $ttl];
        } finally {
            fclose($handle);
        }
    }
}
