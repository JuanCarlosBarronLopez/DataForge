<?php

declare(strict_types=1);

namespace DataForge\Http;

use DataForge\Core\Request;
use DataForge\Core\Response;
use DataForge\Core\View;
use DataForge\Security\Csrf;
use DataForge\Support\Flash;

/**
 * Base de los controladores: acceso a la vista, a los mensajes de una lectura
 * y a la generacion de tokens CSRF.
 */
abstract class Controller implements RequestAware
{
    private ?Request $request = null;

    public function __construct(
        protected readonly View $view,
        protected readonly Flash $flash,
        protected readonly Csrf $csrf,
    ) {}

    /**
     * El enrutador inyecta la peticion antes de invocar la accion.
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    protected function request(): Request
    {
        if ($this->request === null) {
            throw new \RuntimeException('No hay ninguna peticion en curso.');
        }

        return $this->request;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $template, array $data = [], int $status = 200): Response
    {
        $data['flash']       = $this->flash->pull();
        $data['csrf']        = $this->csrf;
        $data['view']        = $this->view;
        $data['currentUser'] = $data['currentUser'] ?? null;

        return Response::html($this->view->render($template, $data), $status);
    }

    public function redirect(string $location): Response
    {
        return Response::redirect($location);
    }

    /**
     * Redirecciona estableciendo una cookie de corta duracion.
     */
    protected function redirectTo(string $location, string $cookie): Response
    {
        $value = rawurlencode($cookie);

        return Response::redirect($location)->withHeader(
            'Set-Cookie',
            'df_mode='.$value.'; Path=/; Max-Age=31536000; SameSite=Strict; HttpOnly',
        );
    }

    /**
     * Campo oculto con el token CSRF ligado a la accion indicada.
     */
    protected function csrfField(string $action): string
    {
        return $this->csrf->field($action);
    }

    /**
     * Extrae los campos de texto enviados en un formulario.
     *
     * @return array<string, string>
     */
    protected function stringFields(Request $request): array
    {
        $fields = [];

        foreach ($request->all() as $key => $value) {
            if (is_string($value)) {
                $fields[$key] = $value;
            }
        }

        return $fields;
    }
}
