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
use Omega\Http\Http;
use Omega\Application\ApplicationManifest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

/**
 * KernelTest class.
 *
 * Tests the bootstrapping and core behavior of the application kernel, including HTTP services.
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
#[CoversClass(Http::class)]
#[CoversClass(ApplicationManifest::class)]
final class KernelTest extends TestCase
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

        $this->app->set(
            Http::class,
            fn () => new $this->http($this->app)
        );

        $this->http = new Http($this->app);
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
     * Test it can bootstrap.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanBootstrap(): void
    {
        $this->assertFalse($this->app->bootstrapped);
        $this->app->make(Http::class)->bootstrap();
        $this->assertTrue($this->app->bootstrapped);
    }
}

