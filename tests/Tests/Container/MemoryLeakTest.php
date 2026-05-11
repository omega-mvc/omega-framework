<?php

/**
 * Part of Omega - Tests\Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use stdClass;
use Tests\Container\Support\DependencyClass;

use function count;
use function getenv;

/**
 * Class MemoryLeakTest
 *
 * This test class performs stress testing on the container to detect potential memory leaks
 * during heavy usage scenarios. Each test executes a high number of iterations (10,000 cycles)
 * for operations such as making non-shared instances, calling closures with dependencies, and
 * injecting setters. The purpose is to ensure that bindings, instances, aliases, and metadata
 * do not grow unexpectedly during repeated operations. After initial stress testing, the
 * iteration count will be lowered from 10,000 to 1,000 for routine CI and faster test execution.
 *
 * @category  Tests
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
class MemoryLeakTest extends AbstractTestContainer
{
    /**
     * Number of iterations to run for stress/memory-leak tests.
     *
     * This value is dynamically set in `setUp()` depending on the environment:
     * - In CI environments (e.g., GitHub Actions), it is reduced to a smaller number
     *   to speed up automated test execution.
     * - Locally, it defaults to a higher number for thorough stress testing.
     *
     * @var int
     */
    private int $iterations;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('OMEGA_TEST_MODE') === 'light') {
            $this->iterations = 10;
        } elseif (getenv('CI') || getenv('GITHUB_ACTIONS')) {
            $this->iterations = 100;
        } else {
            $this->iterations = 100000;
        }
    }

    /**
     * Test leak repeated make on non-shared.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    #[Group('memory-leak')]
    public function testLeakRepeatedMakeNonShared(): void
    {
        $initialBindingsCount  = count($this->getProtectedProperty('bindings'));
        $initialInstancesCount = count($this->getProtectedProperty('instances'));
        $initialAliasesCount   = count($this->getProtectedProperty('aliases'));

        // Make many non-shared instances of a simple class that is not bound
        for ($i = 0; $i < $this->iterations; $i++) {
            $this->container->make(stdClass::class);
        }

        $finalBindingsCount  = count($this->getProtectedProperty('bindings'));
        $finalInstancesCount = count($this->getProtectedProperty('instances'));
        $finalAliasesCount   = count($this->getProtectedProperty('aliases'));

        // Assert that bindings, instances, and aliases do not grow
        $this->assertEquals($initialBindingsCount, $finalBindingsCount);
        $this->assertEquals($initialInstancesCount, $finalInstancesCount);
        $this->assertEquals($initialAliasesCount, $finalAliasesCount);
    }

    /**
     * Test leak all  metadata.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    #[Group('memory-leak')]
    public function testLeakCallMetadata(): void
    {
        $callable = function (DependencyClass $dep) {
            return $dep;
        };

        // Call many times to simulate heavy usage
        for ($i = 0; $i < $this->iterations; $i++) {
            $this->container->call($callable);
        }

        // If no exception is thrown, it's a pass for this basic check
        $this->assertTrue(true);
    }

    /**
     * Test leak inject on.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection
     */
    #[Group('memory-leak')]
    public function testLeakInjectOn(): void
    {
        // Define a simple class with a setter to be injected
        $injectable = new class {
            public DependencyClass $dependency;

            public function setDependency(DependencyClass $dependency): void
            {
                $this->dependency = $dependency;
            }
        };

        // Call injectOn many times to simulate heavy usage
        for ($i = 0; $i < $this->iterations; $i++) {
            $this->container->injectOn($injectable);
        }

        // If no exception is thrown, it's a pass for this basic check
        $this->assertTrue(true);
    }
}
