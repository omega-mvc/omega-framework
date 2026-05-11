<?php

/**
 * Part of Omega - Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Http;

use Closure;
use Exception;
use InvalidArgumentException;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ExceptionHandler;
use Omega\Middleware\MaintenanceMiddleware;
use Omega\Router\Router;
use Omega\Application\Bootstrapper\BootProviders;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\Exceptions\Bootstrapper\HandleExceptions;
use Omega\Facade\Bootstrapper\FacadeBootstrapper;
use Omega\Application\Bootstrapper\RegisterProviders;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Throwable;

use function array_merge;
use function array_reduce;
use function is_array;
use function is_string;
use function method_exists;

/**
 * Core HTTP kernel of the application.
 *
 * This class is responsible for handling the full HTTP request
 * lifecycle: bootstrapping the application, dispatching the
 * request, executing middleware, handling exceptions, and
 * returning an HTTP response.
 *
 * It acts as the central coordinator between the application
 * container, routing system, middleware pipeline, and exception
 * handling layer.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Http
{
    /**
     * Application service container instance.
     *
     * Used to resolve dependencies, call handlers and middleware,
     * and manage the application lifecycle.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Global HTTP middleware stack.
     *
     * These middleware are executed for every incoming request.
     *
     * @var array<int, class-string|string>
     */
    protected array $middleware = [
        MaintenanceMiddleware::class,
    ];

    /** @var array<int, class-string|string> List of middleware already registered or executed. */
    protected array $middlewareUsed = [];

    /**
     * Application bootstrap classes.
     *
     * These classes are executed in order during application
     * bootstrapping to prepare the runtime environment.
     *
     * @var array<int, class-string|string>
     */
    protected array $bootstrappers = [
        ConfigBootstrapper::class,
        HandleExceptions::class,
        FacadeBootstrapper::class,
        RegisterProviders::class,
        BootProviders::class,
    ];

    /**
     * Create a new HTTP kernel instance.
     *
     * @param Application $app Application container instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming HTTP request.
     *
     * This method bootstraps the application, resolves the request
     * dispatcher, executes the middleware pipeline, and returns
     * the generated HTTP response. Any thrown exception is caught
     * and delegated to the configured exception handler.
     *
     * @param Request $request Incoming HTTP request.
     * @return Response HTTP response produced by the application.
     * @throws Throwable Re-thrown only if the exception handler fails.
     */
    public function handle(Request $request): Response
    {
        $this->app->set('request', $request);

        try {
            $this->bootstrap();

            $dispatcher = $this->dispatcher($request);
            $request->with($dispatcher['parameters']);

            $middleware = array_merge($this->middleware, $dispatcher['middleware']);
            $pipeline   = $this->middlewarePipeline($middleware, $dispatcher);
            $response   = $pipeline($request);
        } catch (Throwable $th) {
            $handler = $this->app->get(ExceptionHandler::class);

            $handler->report($th);
            $response = $handler->render($request, $th);
        }

        return $response;
    }

    /**
     * Bootstrap the application.
     *
     * Executes all registered bootstrap classes to prepare
     * configuration, providers, facades, and services.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function bootstrap(): void
    {
        $this->app->bootstrapWith($this->bootstrappers);
    }

    /**
     * Terminate the HTTP request/response lifecycle.
     *
     * Executes the `terminate` method on all applicable middleware
     * and performs application shutdown logic.
     *
     * @param Request  $request  The handled HTTP request.
     * @param Response $response The generated HTTP response.
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function terminate(Request $request, Response $response): void
    {
        $middleware = $this->dispatcherMiddleware($request) ?? [];
        foreach (array_merge($this->middleware, $middleware) as $middleware) {
            if (method_exists($middleware, 'terminate')) {
                $this->app->call([$middleware, 'terminate'], ['request' => $request, 'response' => $response]);
            }
        }

        $this->app->terminate();
    }

    /**
     * Normalize a callable result into an HTTP response.
     *
     * The callable result may return a Response instance, a string,
     * or an array. Any other return type is considered invalid.
     *
     * @param callable|array|string $callable   Callable or handler to execute.
     * @param array                 $parameters Parameters passed to the callable.
     * @return Response Normalized HTTP response.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception If the returned content type is invalid.
     */
    private function responseType(callable|array|string $callable, array $parameters): Response
    {
        $content = $this->app->call($callable, $parameters);
        if ($content instanceof Response) {
            return $content;
        }

        if (is_string($content)) {
            return new Response($content);
        }

        if (is_array($content)) {
            return new Response($content);
        }

        throw new Exception('Content must return as response|string|array');
    }

    /**
     * Resolve the dispatcher for the given request.
     *
     * The dispatcher defines the final callable, parameters,
     * and middleware stack for the request.
     *
     * @param Request $request Incoming HTTP request.
     * @return array<string, mixed> Dispatcher configuration.
     */
    protected function dispatcher(Request $request): array
    {
        return ['callable' => new Response(), 'parameters' => [], 'middleware' => []];
    }

    /**
     * Resolve route-specific middleware for the request.
     *
     * @param Request $request Incoming HTTP request.
     * @return array<int, class-string|string>|null List of middleware or null.
     * @noinspection PhpUnusedParameterInspection
     */
    protected function dispatcherMiddleware(Request $request): ?array
    {
        return Router::getCurrent()['middleware'] ?? [];
    }

    /**
     * Build the middleware execution pipeline.
     *
     * Middleware are executed in reverse order, wrapping the final
     * request handler into a single callable pipeline.
     *
     * @param array<int, class-string|string|object> $middleware Middleware stack.
     * @param array{callable: callable, parameters: array<string, mixed>} $dispatcher Dispatcher configuration.
     * @return Closure(Request): Response Executable middleware pipeline.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception If middleware execution fails.
     */
    protected function middlewarePipeline(array $middleware, array $dispatcher): Closure
    {
        return array_reduce(
            array_reverse($middleware),
            fn ($next, $middleware): Closure => fn (Request $request): Response => $this->executeMiddleware(
                $middleware,
                $request,
                $next
            ),
            fn (): Response                  => $this->responseType($dispatcher['callable'], $dispatcher['parameters'])
        );
    }

    /**
     * Execute a single middleware.
     *
     * The middleware must expose a `handle` method accepting
     * the request and a next callback.
     *
     * @param class-string|string $middleware Middleware class or identifier.
     * @param Request             $request    Incoming HTTP request.
     * @param callable            $next       Next middleware callback.
     * @return Response HTTP response.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function executeMiddleware(string $middleware, Request $request, callable $next): Response
    {
        if (false === method_exists($middleware, 'handle')) {
            throw new InvalidArgumentException('Middleware must be a class with handle method');
        }

        return $this->app->call(
            [$middleware, 'handle'],
            ['request' => $request, 'next' => $next]
        );
    }
}
