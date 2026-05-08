<?php
/**
 * ============================================
 * DataForge — Security Middleware
 * ============================================
 *
 * Centralized security layer providing:
 * - Content Security Policy (CSP) headers
 * - Rate limiting (file-based or Redis-backed)
 * - Input sanitization helpers
 * - Additional security headers (Helmet-style)
 *
 * @package  DataForge
 * @module   Security
 * @version  3.2.0
 */

// ─── Content Security Policy ──────────────────────────────────────────────
function applySecurityHeaders(): void
{
    // Only send headers if not already sent
    if (headers_sent()) {
        return;
    }

    // CSP — Allow inline styles/scripts (required by the app's architecture)
    // but restrict everything else to same-origin + trusted CDNs
    $csp = implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline'",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data: blob:",
        "connect-src 'self'",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
    ]);

    header("Content-Security-Policy: {$csp}");
    header('X-Permitted-Cross-Domain-Policies: none');
    header('X-Download-Options: noopen');
    header('X-DNS-Prefetch-Control: off');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
}

// ─── Rate Limiting ────────────────────────────────────────────────────────

/**
 * Check rate limit for the current request.
 *
 * @param string $identifier  Unique key (usually IP or IP+route)
 * @param int    $maxRequests Maximum requests allowed in the window
 * @param int    $windowSecs  Time window in seconds
 * @return array{allowed: bool, remaining: int, retryAfter: int}
 */
function checkRateLimit(string $identifier, int $maxRequests = 60, int $windowSecs = 60): array
{
    $storage = $_ENV['RATE_LIMIT_STORAGE'] ?? getenv('RATE_LIMIT_STORAGE') ?: 'file';

    if ($storage === 'redis') {
        return checkRateLimitRedis($identifier, $maxRequests, $windowSecs);
    }

    return checkRateLimitFile($identifier, $maxRequests, $windowSecs);
}

/**
 * File-based rate limiting (no external dependencies).
 */
function checkRateLimitFile(string $identifier, int $maxRequests, int $windowSecs): array
{
    $dir = defined('LOGS_PATH') ? LOGS_PATH . '/rate_limits' : __DIR__ . '/../logs/rate_limits';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
        @file_put_contents($dir . '/.htaccess', 'Deny from all');
    }

    $file = $dir . '/' . md5($identifier) . '.json';
    $now = time();
    $data = ['count' => 0, 'window_start' => $now];

    if (file_exists($file)) {
        $content = @file_get_contents($file);
        if ($content !== false) {
            $data = json_decode($content, true) ?: $data;
        }
    }

    // Reset window if expired
    if (($now - $data['window_start']) >= $windowSecs) {
        $data = ['count' => 0, 'window_start' => $now];
    }

    $data['count']++;
    @file_put_contents($file, json_encode($data), LOCK_EX);

    $remaining = max(0, $maxRequests - $data['count']);
    $retryAfter = $windowSecs - ($now - $data['window_start']);

    return [
        'allowed'    => $data['count'] <= $maxRequests,
        'remaining'  => $remaining,
        'retryAfter' => max(0, $retryAfter),
    ];
}

/**
 * Redis-based rate limiting (atomic, production-grade).
 */
function checkRateLimitRedis(string $identifier, int $maxRequests, int $windowSecs): array
{
    try {
        $host = $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = (int) ($_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: 6379);

        $redis = new \Redis();
        $redis->connect($host, $port, 1.0); // 1 second timeout

        $key = "rate_limit:{$identifier}";
        $count = $redis->incr($key);

        if ($count === 1) {
            $redis->expire($key, $windowSecs);
        }

        $ttl = $redis->ttl($key);
        $remaining = max(0, $maxRequests - $count);

        return [
            'allowed'    => $count <= $maxRequests,
            'remaining'  => $remaining,
            'retryAfter' => max(0, $ttl),
        ];
    } catch (\Exception $e) {
        // Fallback to file-based if Redis is unavailable
        error_log("Redis rate limit failed, falling back to file: " . $e->getMessage());
        return checkRateLimitFile($identifier, $maxRequests, $windowSecs);
    }
}

/**
 * Apply rate limiting to the current request.
 * Sends 429 response if limit is exceeded.
 *
 * @param string|null $route Optional route identifier for per-route limits
 * @return void
 */
function applyRateLimit(?string $route = null): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // Stricter limits for authentication routes
    $isLoginRoute = ($route === 'login')
        || str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'login.php')
        || str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'register.php');

    if ($isLoginRoute) {
        $maxRequests = (int) ($_ENV['RATE_LIMIT_LOGIN_MAX'] ?? getenv('RATE_LIMIT_LOGIN_MAX') ?: 5);
        $windowSecs  = (int) ($_ENV['RATE_LIMIT_LOGIN_WINDOW'] ?? getenv('RATE_LIMIT_LOGIN_WINDOW') ?: 300);
        $identifier  = "login:{$ip}";
    } else {
        $maxRequests = (int) ($_ENV['RATE_LIMIT_MAX'] ?? getenv('RATE_LIMIT_MAX') ?: 60);
        $windowSecs  = (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? getenv('RATE_LIMIT_WINDOW') ?: 60);
        $identifier  = "global:{$ip}";
    }

    $result = checkRateLimit($identifier, $maxRequests, $windowSecs);

    // Always send rate limit headers
    if (!headers_sent()) {
        header("X-RateLimit-Limit: {$maxRequests}");
        header("X-RateLimit-Remaining: {$result['remaining']}");
    }

    if (!$result['allowed']) {
        if (!headers_sent()) {
            http_response_code(429);
            header("Retry-After: {$result['retryAfter']}");
            header('Content-Type: text/html; charset=UTF-8');
        }
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>429 — Demasiadas solicitudes</title>';
        echo '<style>body{font-family:Inter,system-ui,sans-serif;background:#0a0e1a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}';
        echo '.box{text-align:center;padding:3rem;border-radius:16px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)}';
        echo 'h1{font-size:4rem;margin:0;background:linear-gradient(135deg,#8b5cf6,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent}';
        echo 'p{color:#94a3b8;margin-top:1rem;font-size:1.1rem}</style></head>';
        echo "<body><div class='box'><h1>429</h1><p>Demasiadas solicitudes. Intenta de nuevo en {$result['retryAfter']} segundos.</p></div></body></html>";
        exit();
    }
}

// ─── Input Sanitization ───────────────────────────────────────────────────

/**
 * Sanitize a string input for safe display.
 * Strips tags, trims whitespace, and encodes special characters.
 *
 * @param string $input    Raw input string
 * @param bool   $stripTags Whether to strip HTML tags
 * @return string Sanitized string
 */
function sanitizeInput(string $input, bool $stripTags = true): string
{
    $input = trim($input);

    if ($stripTags) {
        $input = strip_tags($input);
    }

    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize an array of inputs recursively.
 *
 * @param array $data      Array of key => value pairs
 * @param array $exclude   Keys to exclude from sanitization
 * @return array Sanitized array
 */
function sanitizeArray(array $data, array $exclude = []): array
{
    $sanitized = [];
    foreach ($data as $key => $value) {
        if (in_array($key, $exclude, true)) {
            $sanitized[$key] = $value;
            continue;
        }
        if (is_string($value)) {
            $sanitized[$key] = sanitizeInput($value);
        } elseif (is_array($value)) {
            $sanitized[$key] = sanitizeArray($value, $exclude);
        } else {
            $sanitized[$key] = $value;
        }
    }
    return $sanitized;
}

/**
 * Validate and sanitize a database/table name.
 * Only allows alphanumeric characters and underscores.
 *
 * @param string $name Raw name
 * @return string|false Sanitized name or false if invalid
 */
function sanitizeDbIdentifier(string $name): string|false
{
    $name = trim($name);
    if (empty($name) || !preg_match('/^[a-zA-Z0-9_]{1,64}$/', $name)) {
        return false;
    }
    return $name;
}

// ─── Periodic Cleanup of Rate Limit Files ─────────────────────────────────

/**
 * Clean up expired rate limit files (run occasionally).
 * Called probabilistically (1% chance per request) to avoid overhead.
 */
function cleanupRateLimitFiles(): void
{
    // Only run 1% of the time
    if (mt_rand(1, 100) !== 1) {
        return;
    }

    $dir = defined('LOGS_PATH') ? LOGS_PATH . '/rate_limits' : __DIR__ . '/../logs/rate_limits';
    if (!is_dir($dir)) {
        return;
    }

    $maxAge = 3600; // 1 hour
    $now = time();

    foreach (glob($dir . '/*.json') as $file) {
        if (($now - filemtime($file)) > $maxAge) {
            @unlink($file);
        }
    }
}
