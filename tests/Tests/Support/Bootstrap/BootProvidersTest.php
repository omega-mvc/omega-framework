<?php

/**
 * Part of Omega - Tests\Support\Bootstrap Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpPossiblePolymorphicInvocationInspection */

declare(strict_types=1);

namespace Tests\Support\Bootstrap;

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\AbstractServiceProvider;
use Omega\Application\Bootstrapper\BootProviders;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

use function get_class;

/**
 * Class BootProvidersTest
 *
 * This test ensures that the application can successfully bootstrap its
 * service providers using the BootProviders bootstrapper. The test confirms
 * that before the bootstrap process the application is not yet initialized,
 * and that after invoking `bootstrapWith()` the application transitions to
 * a booted state.
 *
 * This verifies the correct integration between the application's service
 * provider registration system and the bootstrap loading mechanism.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Bootstrap
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(BootProviders::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
class BootProvidersTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test bootstrap
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBootstrap(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/application-read/'));

        $this->assertFalse($app->isBooted);
        $app->bootstrapWith([BootProviders::class]);
        $this->assertTrue($app->isBooted);
    }

    /**
     * Test register boot provider when application already booted.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testRegisterBootsProviderWhenApplicationAlreadyBooted(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/application-read/'));

        $app->isBooted = true;

        $provider = new class($app) extends AbstractServiceProvider {

            public bool $bootCalled = false;

            public function register(): void {}

            public function boot(): void
            {
                $this->bootCalled = true;
            }
        };

        $class = get_class($provider);

        $registered = $app->register($class);

        $this->assertTrue($registered->bootCalled);
    }

    /**
     * Test register does not boot provider when application not booted.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testRegisterDoesNotBootProviderWhenApplicationNotBooted(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/application-read/'));

        $provider = new class($app) extends AbstractServiceProvider {

            public bool $bootCalled = false;

            public function register(): void {}

            public function boot(): void
            {
                $this->bootCalled = true;
            }
        };

        $class = get_class($provider);

        $registered = $app->register($class);

        $this->assertFalse($registered->bootCalled);
    }
}
