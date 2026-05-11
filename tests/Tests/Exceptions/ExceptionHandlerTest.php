<?php

/**
 * Part of Omega - Tests\Exceptions Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Exceptions;

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
use Omega\Text\Str;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use ReflectionMethod;
use Tests\FixturesPathTrait;
use Throwable;

use function file;
use function Omega\Support\view;
use function str_contains;

/**
 * Unit tests for Omega exception handling and HTTP components.
 *
 * Verifies ExceptionHandler, HttpException, Http, Request, Response,
 * ApplicationManifest, Str, Templator, and TemplatorFinder behavior.
 * Ensures exceptions are reported, rendered, and JSON responses handled.
 *
 * @category  Tests
 * @package   Exceptions
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
#[CoversClass(Str::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class ExceptionHandlerTest extends TestCase
{
    use FixturesPathTrait;

    /** @var Application Application instance used in the tests. */
    private Application $app;

    /** @var Http Http instance used to simulate requests. */
    private Http $http;

    /** @var ExceptionHandler Custom exception handler for testing. */
    private ExceptionHandler $exceptionHandler;

    /** @var string[] Mock logger to capture reported exception messages during tests. */
    public static array $logs = [];

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws Exception Trow when a generic error occurred.
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

        $this->http = new class($this->app) extends Http {
            protected function dispatcher(Request $request): array
            {
                throw new HttpException(429, 'Too Many Request');
            }
        };

        $this->exceptionHandler = new class($this->app) extends ExceptionHandler {
            public function render(Request $request, Throwable $th): Response
            {
                // try to bypass test for json format
                if ($request->isJson()) {
                    return $this->handleJsonResponse($th);
                }

                if ($th instanceof HttpException) {
                    return new Response($th->getMessage(), $th->getStatusCode(), $th->getHeaders());
                }

                return parent::render($request, $th);
            }

            public function report(Throwable $th): void
            {
                ExceptionHandlerTest::$logs[] = $th->getMessage();
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

        ExceptionHandlerTest::$logs = [];
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
        $response =   $http->handle(new Request('/test'));

        $this->assertEquals('Too Many Request', $response->getContent());
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * Test it can report exception.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanReportException(): void
    {
        $http     = $this->app->make(Http::class);
          $http->handle(new Request('/test'));

        $this->assertEquals(['Too Many Request'], ExceptionHandlerTest::$logs);
    }

    /**
     * Test it can render json.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRenderJson(): void
    {
        $this->app->bootedCallback(function () {
            $this->app->set('app.debug', false);
        });

        $http     = $this->app->make(Http::class);
        $response    =   $http->handle(new Request('/test', [], [], [], [], [], [
            'content-type' => 'application/json',
        ]));

        $this->assertEquals([
            'code'     => 500,
            'messages' => [
                'message'   => 'Internal Server Error',
            ],
        ], $response->getContent());
        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * Test it can render son for debug.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRenderJsonForDebug(): void
    {
        $this->app->bootedCallback(function () {
            $this->app->set('app.debug', true);
        });

        $http = $this->app->make(Http::class);

        $response = $http->handle(new Request(
            '/test',
            [],
            [],
            [],
            [],
            [],
            ['content-type' => 'application/json']
        ));

        $content = $response->getContent();

        // Verifiche principali
        $this->assertEquals('Too Many Request', $content['messages']['message']);
        $this->assertEquals(
            'Omega\Http\Exceptions\HttpException',
            $content['messages']['exception']
        );

        // 🔎 Calcolo dinamico della riga del throw
        $reflection = new ReflectionMethod($this->http, 'dispatcher');
        $source     = file($reflection->getFileName());

        $expectedLine = null;

        foreach ($source as $number => $line) {
            if (str_contains($line, 'throw new HttpException')) {
                $expectedLine = $number + 1; // file() è 0-indexed
                break;
            }
        }

        $this->assertNotNull(
            $expectedLine,
            'Unable to detect HttpException throw line dynamically.'
        );

        $this->assertEquals($expectedLine, $content['messages']['line']);

        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * Test it can render http exception.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRenderHttpException(): void
    {
        $this->app->set('path.view', $this->setFixturePath('/fixtures/exceptions/'));
        $this->app->set('paths.view', [
            $this->setFixturePath('/fixtures/exceptions/'),
            $this->setFixturePath('/fixtures/exceptions/pages/'),
        ]);
        $this->app->set(
            TemplatorFinder::class,
            fn () => new TemplatorFinder($this->app->get('paths.view'), ['.php', '.template.php'])
        );

        $this->app->set(
            'view.instance',
            fn (TemplatorFinder $finder) => new Templator($finder, $this->setFixturePath('/fixtures/exceptions'))
        );

        $this->app->set(
            'view.response',
            fn () => fn (string $viewPath, array $portal = []): Response => new Response(
                $this->app->make(Templator::class)->render($viewPath, $portal)
            )
        );

        $handler = $this->app->make(ExceptionHandler::class);

        $exception = new HttpException(429, 'Internal Error', null, []);
        $render    = (fn () => $this->{'handleHttpException'}($exception))->call($handler);

        $this->assertTrue(Str::contains($render->getContent(), '<h1>Too Many Request</h1>'));
    }
}
