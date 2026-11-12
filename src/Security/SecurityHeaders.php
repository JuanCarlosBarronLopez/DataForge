<?php

declare(strict_types=1);

namespace DataForge\Security;

/**
 * Cabeceras de seguridad de la respuesta.
 *
 * La CSP usa un nonce por respuesta para los scripts en linea, de modo que
 * `unsafe-inline` no es necesario en script-src. En style-src se mantiene
 * `unsafe-inline` porque el diseno aplica variables de color de forma
 * dinamica en el atributo style; el riesgo es menor porque CSS no puede
 * ejecutar codigo.
 */
final class SecurityHeaders
{
    public function __construct(
        private readonly bool $production = false,
        private readonly int $hstsMaxAge = 31_536_000,
    ) {}

    /**
     * @return array<string, string>
     */
    public function build(string $nonce): array
    {
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-".$nonce."'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self'",
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
            "manifest-src 'self'",
            'upgrade-insecure-requests',
        ]);

        $headers = [
            'Content-Security-Policy'           => $csp,
            'X-Content-Type-Options'            => 'nosniff',
            'X-Frame-Options'                   => 'DENY',
            'Referrer-Policy'                   => 'strict-origin-when-cross-origin',
            'Permissions-Policy'                => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            'Cross-Origin-Opener-Policy'        => 'same-origin',
            'Cross-Origin-Resource-Policy'      => 'same-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'X-Download-Options'                => 'noopen',
            'Cross-Origin-Embedder-Policy'      => 'require-corp',
        ];

        if ($this->production) {
            $headers['Strict-Transport-Security'] = 'max-age='.$this->hstsMaxAge.'; includeSubDomains';
        }

        return $headers;
    }
}
