<?php

declare(strict_types=1);

namespace DataForge\Core;

/**
 * Motor de plantillas. Cada vista se renderiza dentro de un layout.
 *
 * Las plantillas deben escapar la salida con e() o con el helper attr().
 */
final class View
{
    /**
     * @param array<string, mixed> $shared
     */
    public function __construct(
        private readonly string $pagePath,
        private readonly string $layoutPath,
        private readonly string $partialPath,
        private array $shared = [],
    ) {}

    /**
     * Registra un valor disponible para todas las plantillas.
     */
    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * Renderiza una pagina dentro de un layout.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], string $layout = 'app'): string
    {
        $content = $this->page($template, $data);

        return $this->layout($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * Renderiza una pagina sin layout.
     *
     * @param array<string, mixed> $data
     */
    public function page(string $template, array $data = []): string
    {
        return $this->renderFile($this->pagePath.'/'.$template.'.php', $data);
    }

    /**
     * Renderiza un layout.
     *
     * @param array<string, mixed> $data
     */
    public function layout(string $name, array $data = []): string
    {
        return $this->renderFile($this->layoutPath.'/'.$name.'.php', $data);
    }

    /**
     * Renderiza una plantilla parcial.
     *
     * @param array<string, mixed> $data
     */
    public function partial(string $template, array $data = []): string
    {
        return $this->renderFile($this->partialPath.'/'.$template.'.php', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(string $file, array $data): string
    {
        if (!is_file($file)) {
            throw new \RuntimeException(sprintf('No existe la plantilla "%s".', $file));
        }

        $scope = array_merge($this->shared, $data);

        $level = ob_get_level();
        ob_start();

        try {
            (static function (string $path, array $scope): void {
                extract($scope, EXTR_SKIP);
                require $path;
            })($file, $scope);
        } catch (\Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $exception;
        }

        return (string) ob_get_clean();
    }
}
