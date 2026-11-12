<?php

declare(strict_types=1);

namespace DataForge\Security;

use DataForge\Core\Request;

/**
 * Resuelve la direccion IP del cliente.
 *
 * La cabecera X-Forwarded-For solo se toma en cuenta cuando la peticion llega
 * desde un proxy declarado como confiable. De lo contrario, cualquier cliente
 * podria falsear su IP y evadir el rate limiting.
 */
final class ClientIp
{
    /**
     * @param list<string> $trustedProxies
     */
    public function __construct(private readonly array $trustedProxies = []) {}

    public function resolve(Request $request): string
    {
        $remote = $request->server('REMOTE_ADDR', '') ?? '';

        if ($remote === '' || !$this->isTrustedProxy($remote)) {
            return $remote !== '' ? $remote : '0.0.0.0';
        }

        $forwarded = $request->header('X-Forwarded-For', '') ?? '';
        if ($forwarded === '') {
            return $remote;
        }

        foreach (explode(',', $forwarded) as $candidate) {
            $ip = trim($candidate);
            if ($ip !== '' && !$this->isPrivate($ip)) {
                return $ip;
            }
        }

        return $remote;
    }

    private function isTrustedProxy(string $ip): bool
    {
        foreach ($this->trustedProxies as $trusted) {
            if ($trusted === $ip) {
                return true;
            }

            if (str_contains($trusted, '/') && $this->matchesCidr($ip, $trusted)) {
                return true;
            }
        }

        return false;
    }

    private function matchesCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, '32');
        $bits            = (int) $bits;

        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false || $bits < 0 || $bits > 32) {
            return false;
        }

        $mask = $bits === 0 ? 0 : -1 << (32 - $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    private function isPrivate(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }
}
