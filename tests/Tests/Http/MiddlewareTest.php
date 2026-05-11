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
use InvalidArgumentException;
use Omega\Application\Application;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Application\ApplicationManifest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;
use Tests\Http\Support\ClassA;
use Tests\Http\Support\ClassB;
use Tests\Http\Support\ClassC;
use Tests\Http\Support\ClassD;

use function ob_get_clean;
use function ob_start;

/**
 * Test suite for HTTP middleware handling within the application.
 *
 * This class verifies that middleware can be executed in a reversible pipeline,
 * ensuring proper ordering of before/after calls, and that invalid middleware
 * definitions are correctly rejected with exceptions.
 *
 * It uses a combination of fixture-based setup and actual Application and Http
 * instances to simulate realistic request handling scenarios.
 *
 * @category  Tests
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(Application::class)]
#[CoversClass(Http::class)]
#[CoversClass(ApplicationManifest::class)]
final class MiddlewareTest extends TestCase
{
    use FixturesPathTrait;

    /** @var Application The application instance used for kernel testing. */
    private Application $app;

    /** @var Http The HTTP service instance used for testing kernel request handling. */
    private Http $http;

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

        $this->http = new Http($this->app);

        $this->app->set(Http::class, fn () => $this->http);
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
     * Test it can handle middleware reversible using class string.
     *
     * @return void
     */
    public function testItCanHandleMiddlewareReversibleUsingClassString(): void
    {
        $middleware = [
            ClassA::class,
            ClassB::class,
            ClassC::class,
        ];

        $dispatcher = [
            'callable' => function ($param) {
                echo $param;

                return new Response('');
            },
            'parameters' => [
                'param' => 'final response/',
            ],
        ];

        ob_start();
        $pipe = (fn () => $this->{'middlewarePipeline'}($middleware, $dispatcher))->call($this->app[Http::class]);
        $pipe(new Request('/'));
        $out = ob_get_clean();

        $this->assertEquals(
            'middleware.A.before/middleware.B.before/middleware.C.before/final response/middleware.C.after/middleware.A.after/',
            $out,
            'middleware must be called in order and reversible using function'
        );
    }

    /**
     * Test it throw invalid argument method not found.
     *
     * @return void
     */
    public function testItThrowInvalidArgumentMethodNotFound(): void
    {
        $middleware = [ClassD::class];
        $dispatcher = [
            'callable' => function ($param) {
                echo $param;

                return new Response($param);
            },
            'parameters' => [
                'param' => 'final response/',
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware must be a class with handle method');

        $pipe = (fn () => $this->{'middlewarePipeline'}($middleware, $dispatcher))->call($this->app[Http::class]);
        $pipe(new Request('/'));
    }
}
