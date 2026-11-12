<?php

declare(strict_types=1);

namespace DataForge\Providers;

use DataForge\Core\Config;
use DataForge\Core\Container;
use DataForge\Core\View;
use DataForge\Database\ConnectionFactory;
use DataForge\Http\Middleware\ErrorHandlerMiddleware;
use DataForge\Http\Middleware\RateLimitMiddleware;
use DataForge\Repository\ActivityRepository;
use DataForge\Repository\LoginAttemptRepository;
use DataForge\Repository\ManagedDatabaseRepository;
use DataForge\Repository\QueryHistoryRepository;
use DataForge\Repository\UserRepository;
use DataForge\Security\ClientIp;
use DataForge\Security\Csrf;
use DataForge\Security\FileRateLimitStore;
use DataForge\Security\PasswordHasher;
use DataForge\Security\RateLimiter;
use DataForge\Security\RateLimitStore;
use DataForge\Security\RedisRateLimitStore;
use DataForge\Security\SecurityHeaders;
use DataForge\Security\SessionManager;
use DataForge\Service\AvatarService;
use DataForge\Service\QueryService;
use DataForge\Support\CspNonce;
use DataForge\Support\Flash;
use DataForge\Support\Logger;

/**
 * Registro de las dependencias de la aplicacion.
 */
final class AppServiceProvider
{
    public function register(Container $container, Config $config): void
    {
        $container->instance(Config::class, $config);

        $container->singleton(Logger::class, static function (Container $c): Logger {
            /** @var Config $config */
            $config = $c->get(Config::class);

            return new Logger((string) $config->get('app.paths.logs', ''));
        });

        $container->singleton(CspNonce::class, static fn(): CspNonce => new CspNonce());

        $container->singleton(ConnectionFactory::class, static function (Container $c): ConnectionFactory {
            /** @var Config $config */
            $config = $c->get(Config::class);

            return new ConnectionFactory(
                (string) $config->get('database.host', '127.0.0.1'),
                (int) $config->get('database.port', 3306),
                (string) $config->get('database.username', 'root'),
                (string) $config->get('database.password', ''),
                (string) $config->get('database.charset', 'utf8mb4'),
                (string) $config->get('database.system_database', 'dataforge_system'),
                $config->get('database.ssl_ca') === null ? null : (string) $config->get('database.ssl_ca'),
                (bool) $config->get('database.ssl_verify', true),
            );
        });

        $container->singleton(\PDO::class, static function (Container $c): \PDO {
            /** @var ConnectionFactory $connections */
            $connections = $c->get(ConnectionFactory::class);

            return $connections->system();
        });

        $container->singleton(UserRepository::class, static fn(Container $c): UserRepository => new UserRepository($c->get(\PDO::class)));
        $container->singleton(ManagedDatabaseRepository::class, static fn(Container $c): ManagedDatabaseRepository => new ManagedDatabaseRepository($c->get(\PDO::class)));
        $container->singleton(ActivityRepository::class, static fn(Container $c): ActivityRepository => new ActivityRepository($c->get(\PDO::class)));
        $container->singleton(LoginAttemptRepository::class, static fn(Container $c): LoginAttemptRepository => new LoginAttemptRepository($c->get(\PDO::class)));
        $container->singleton(QueryHistoryRepository::class, static fn(Container $c): QueryHistoryRepository => new QueryHistoryRepository($c->get(\PDO::class)));

        $container->singleton(PasswordHasher::class, static fn(): PasswordHasher => new PasswordHasher());

        $container->singleton(SessionManager::class, static function (Container $c): SessionManager {
            /** @var Config $config */
            $config = $c->get(Config::class);

            return new SessionManager(
                (int) $config->get('security.session.idle_timeout', 1800),
                (int) $config->get('security.session.absolute_timeout', 28800),
            );
        });

        $container->singleton(Csrf::class, static fn(Container $c): Csrf => new Csrf($c->get(SessionManager::class)));
        $container->singleton(Flash::class, static fn(Container $c): Flash => new Flash($c->get(SessionManager::class)));

        $container->singleton(ClientIp::class, static function (Container $c): ClientIp {
            /** @var Config $config */
            $config = $c->get(Config::class);

            /** @var list<string> $proxies */
            $proxies = $config->get('security.trusted_proxies', []);

            return new ClientIp($proxies);
        });

        $container->singleton(RateLimitStore::class, static function (Container $c): RateLimitStore {
            /** @var Config $config */
            $config = $c->get(Config::class);

            if ($config->get('security.rate_limit.storage') === 'redis') {
                return new RedisRateLimitStore(
                    (string) $config->get('security.redis.host', '127.0.0.1'),
                    (int) $config->get('security.redis.port', 6379),
                    1.0,
                    $config->get('security.redis.password') === null ? null : (string) $config->get('security.redis.password'),
                );
            }

            return new FileRateLimitStore((string) $config->get('app.paths.cache', '').'/rate-limit');
        });

        $container->singleton(SecurityHeaders::class, static function (Container $c): SecurityHeaders {
            /** @var Config $config */
            $config = $c->get(Config::class);

            return new SecurityHeaders((string) $config->get('app.env', 'development') === 'production');
        });

        $container->singleton(AvatarService::class, static function (Container $c): AvatarService {
            /** @var Config $config */
            $config = $c->get(Config::class);

            return new AvatarService(
                (string) $config->get('app.paths.uploads', ''),
                (int) $config->get('security.avatar.max_bytes', 2_097_152),
            );
        });

        $container->singleton(View::class, static function (Container $c): View {
            /** @var Config $config */
            $config = $c->get(Config::class);

            $templates = (string) $config->get('app.paths.templates', '');

            $view = new View(
                $templates.'/pages',
                $templates.'/layouts',
                $templates.'/partials',
            );

            $view->share('appName', (string) $config->get('app.name', 'DataForge'));
            $view->share('appVersion', (string) $config->get('app.version', '4.0.0'));

            return $view;
        });

        $container->singleton(ErrorHandlerMiddleware::class, static function (Container $c): ErrorHandlerMiddleware {
            /** @var Config $config */
            $config = $c->get(Config::class);

            return new ErrorHandlerMiddleware(
                $c->get(Logger::class),
                (bool) $config->get('app.debug', false),
            );
        });

        $container->singleton(RateLimitMiddleware::class, static function (Container $c): RateLimitMiddleware {
            /** @var Config $config */
            $config = $c->get(Config::class);

            return new RateLimitMiddleware(
                $c->get(RateLimiter::class),
                $c->get(ClientIp::class),
                $c->get(Logger::class),
                (int) $config->get('security.rate_limit.global.max', 120),
                (int) $config->get('security.rate_limit.global.window', 60),
                (int) $config->get('security.rate_limit.login.max', 10),
                (int) $config->get('security.rate_limit.login.window', 300),
            );
        });

        $container->singleton(RateLimiter::class, static fn(Container $c): RateLimiter => new RateLimiter($c->get(RateLimitStore::class)));

        $container->singleton(QueryService::class, static function (Container $c): QueryService {
            /** @var Config $config */
            $config = $c->get(Config::class);

            return new QueryService(
                $c->get(ConnectionFactory::class),
                $c->get(Logger::class),
                new \DataForge\Service\SqlGuard(),
                (int) $config->get('security.query.max_rows', 500),
                (int) $config->get('security.query.timeout', 10),
            );
        });
    }
}
