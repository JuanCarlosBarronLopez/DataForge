<?php

declare(strict_types=1);

namespace DataForge\Security;

/**
 * Almacen de rate limiting sobre Redis.
 *
 * El incremento y la expiracion se ejecutan dentro de un script de Lua para
 * que sean atomicos incluso con peticiones concurrentes.
 */
final class RedisRateLimitStore implements RateLimitStore
{
    private const LUA_INCREMENT = <<<'LUA'
local current = redis.call('INCR', KEYS[1])
if current == 1 then
    redis.call('EXPIRE', KEYS[1], ARGV[1])
end
local ttl = redis.call('TTL', KEYS[1])
if ttl < 0 then
    ttl = tonumber(ARGV[1])
end
return {current, ttl}
LUA;

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 6379,
        private readonly float $timeout = 1.0,
        private readonly ?string $password = null,
    ) {}

    public function hit(string $key, int $windowSeconds): array
    {
        $redis = new \Redis();

        try {
            $redis->connect($this->host, $this->port, $this->timeout);

            if ($this->password !== null && $this->password !== '') {
                $redis->auth($this->password);
            }

            $result = $redis->eval(
                self::LUA_INCREMENT,
                [$key, $windowSeconds],
                1,
            );
        } catch (\RedisException $exception) {
            throw new \RuntimeException('Redis no esta disponible: '.$exception->getMessage(), 0, $exception);
        }

        if (!is_array($result) || count($result) < 2) {
            throw new \RuntimeException('Respuesta inesperada de Redis.');
        }

        return ['count' => (int) $result[0], 'ttl' => (int) $result[1]];
    }
}
