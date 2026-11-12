<?php

declare(strict_types=1);

namespace DataForge\Core;

/**
 * Configuracion de la aplicacion cargada desde archivos PHP en config/.
 * Se accede con notacion de punto: $config->get('security.rate_limit.max').
 */
final class Config
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(private array $items = []) {}

    public static function fromDirectory(string $directory): self
    {
        $items = [];

        foreach (glob(rtrim($directory, '/').'/*.php') ?: [] as $file) {
            $key         = basename($file, '.php');
            $items[$key] = require $file;
        }

        return new self($items);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $cursor   = &$this->items;

        foreach ($segments as $index => $segment) {
            if ($index === count($segments) - 1) {
                $cursor[$segment] = $value;

                break;
            }
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }
}
