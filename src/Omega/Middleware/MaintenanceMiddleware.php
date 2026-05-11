<?php

/**
 * Part of Omega - Middleware Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Middleware;

use Closure;
use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Request;
use Omega\Http\Response;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function Omega\Http\redirect;

/**
 * Middleware to handle application maintenance mode.
 *
 * This middleware intercepts incoming HTTP requests and checks if the
 * application is in maintenance mode. If so, it either redirects the
 * request, returns a maintenance template response, or throws an HTTP
 * exception. If maintenance mode is not active, the request is passed
 * to the next middleware/controller.
 *
 * @category  Omega
 * @package   Middleware
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class MaintenanceMiddleware
{
    /**
     * Create a new MaintenanceMiddleware instance.
     *
     * @param Application $app The application instance used to check maintenance status and retrieve data.
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Handle an incoming request.
     *
     * Checks if the application is in maintenance mode and responds accordingly:
     * - Redirects if a redirect URL is specified.
     * - Returns a maintenance template with optional headers.
     * - Throws HttpException if no redirect or template is defined.
     *
     * @param Request $request The incoming HTTP request.
     * @param Closure $next The next middleware or request handler.
     * @return Response The HTTP response for the request.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown for general unexpected errors during request handling.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->app->isDownMaintenanceMode()) {
            $data = $this->app->getDownData();

            if (isset($data['redirect'])) {
                return redirect($data['redirect']);
            }

            if (isset($data['template'])) {
                $header = isset($data['retry']) ? ['Retry-After' => $data['retry']] : [];

                return new Response($data['template'], $data['status'] ?? 503, $header);
            }

            throw new HttpException($data['status'] ?? 503, 'Service Unavailable');
        }

        return $next($request);
    }
}
