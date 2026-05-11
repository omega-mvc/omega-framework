<?php

declare(strict_types=1);

namespace Omega\Router;

use App\Middlewares\AppMiddleware;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Router\Router;
use Omega\Container\AbstractServiceProvider;
use ReflectionException;

use function file_exists;
use function is_string;
use function Omega\Application\get_path;
use function str_contains;
use function unserialize;

class RouteServiceProvider extends AbstractServiceProvider
{
    /**
     * @return void
     * @throws BindingResolutionException
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function boot(): void
    {
        if (file_exists($cache = $this->app->getApplicationCachePath() . 'route.php')) {
            $routes = require $cache;

            foreach ($routes as $route) {
                if (is_string($route['function']) && str_contains($route['function'], 'SerializableClosure')) {
                    $route['function'] = unserialize($route['function'])->getClosure();
                }
                Router::addRoutes($route);
            }
        } else {
            Router::middleware([
                // middleware
                AppMiddleware::class,
            ])->group(
                fn () => [
                    require_once get_path('path.base', 'routes/web.php'),
                ]
            );
        }

        require_once get_path('path.base', 'routes/schedule.php');
    }
}
