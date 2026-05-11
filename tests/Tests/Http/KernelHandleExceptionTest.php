<?php

/**
 * Part of Omega - Tests\Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Http;

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ExceptionHandler;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Application\ApplicationManifest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;
use Throwable;

/**
 * KernelHandleExceptionTest class.
 *
 * Tests the kernel behavior when handling exceptions, including HTTP request dispatching
 * and rendering exceptions via a custom exception handler.
 *
 * @category  Tests
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(ExceptionHandler::class)]
#[CoversClass(HttpException::class)]
#[CoversClass(Http::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(ApplicationManifest::class)]
final class KernelHandleExceptionTest extends TestCase
{
    use FixturesPathTrait;

    /** @var Application The application instance used for kernel testing. */
    private Application $app;

    /** @var Http The HTTP service instance used for testing kernel request handling. */
    private Http $http;

    /** @var ExceptionHandler The custom exception handler used during tests. */
    private ExceptionHandler $exceptionHandler;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws Exception Throw if a generic error occurred.
     */
    protected function setUp(): void
    {
        $this->app = new Application($this->setFixturePath('/fixtures/application-read/'));

        $this->app->set(ApplicationManifest::class, fn () => new ApplicationManifest(
            basePath: $this->app->get('path.base'),
            applicationCachePath: $this->app->getApplicationCachePath(),
            vendorPath: '/package/'
        ));

        $this->app->set(
            Http::class,
            fn () => new $this->http($this->app)
        );

        $this->app->set(
            ExceptionHandler::class,
            fn () => $this->exceptionHandler
        );

        /**
         * Anonymous HTTP class.
         *
         * Overrides the dispatcher to always throw a HttpException for testing kernel
         * exception handling. Returns a standard callable and parameters array structure.
         */
        $this->http = new class($this->app) extends Http {
            /**
             * Dispatches a request and triggers an exception.
             *
             * @param Request $request The HTTP request object to dispatch.
             * @return array The dispatcher structure with callable, parameters, and middleware.
             * @throws HttpException Always throws a test HttpException with status 500.
             */
            protected function dispatcher(Request $request): array
            {
                throw new HttpException(500, 'Test Exception');
            }
        };

        /**
         * Anonymous ExceptionHandler class.
         *
         * Overrides the render method to return a response containing the exception message
         * and status 500, allowing testing of exception handling in the kernel.
         */
        $this->exceptionHandler = new class($this->app) extends ExceptionHandler {
            /**
             * Renders a response for the given exception.
             *
             * @param Request $request The HTTP request that triggered the exception.
             * @param Throwable $th The exception to render.
             * @return Response The HTTP response containing the exception message and status 500.
             */
            public function render(Request $request, Throwable $th): Response
            {
                return new Response($th->getMessage(), 500);
            }
        };
    }

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->app->flush();
    }

    /**
     * Test it can render exception.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRenderException(): void
    {
        $http     = $this->app->make(Http::class);
        $response = $http->handle(new Request('/test'));

        $this->assertEquals('Test Exception', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }
}
