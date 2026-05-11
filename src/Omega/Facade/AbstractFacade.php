<?php

/**
 * Part of Omega - Facade Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Facade;

use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Facade\Exception\FacadeObjectNotSetException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function array_key_exists;

/**
 * Base class for implementing facades.
 *
 * Facades provide a static interface to underlying objects managed by the
 * application container. This class handles resolving and caching the actual
 * instance behind the facade, as well as forwarding static calls to that instance.
 *
 * @category  Omega
 * @package   Facade
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
abstract class AbstractFacade implements FacadeInterface
{
    /** @var Application|null The Application container instance. */
    protected static ?Application $app = null;

    /** @var array<string, mixed> Array mapping accessor names to resolved instances */
    protected static array $instance = [];

    /**
     * Create a new facade instance and register the application container.
     *
     * @param Application $app The application container instance
     * @return void
     */
    public function __construct(Application $app)
    {
        static::$app = $app;
    }

    /**
     * Set the application container to be used by all facades.
     *
     * @param Application|null $app The application container, or null to unset
     * @return void
     */
    public static function setFacadeBase(?Application $app = null): void
    {
        static::$app = $app;
    }

    /**
     * Get the container binding key for the underlying instance.
     *
     * @return string The service identifier used to resolve the instance
     */
    abstract public static function getFacadeAccessor(): string;

    /**
     * Resolve and retrieve the underlying facade instance.
     *
     * @return mixed The resolved instance
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected static function getFacade(): mixed
    {
        return static::getFacadeBase(static::getFacadeAccessor());
    }

    /**
     * Resolve a facade instance by name or class.
     *
     * @param string|class-string $name Entry name or class name to resolve
     * @return mixed The resolved instance
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected static function getFacadeBase(string $name): mixed
    {
        if (array_key_exists($name, static::$instance)) {
            return static::$instance[$name];
        }

        return static::$instance[$name] = static::$app->make($name);
    }

    /**
     * Clear all cached resolved facade instances.
     *
     * @return void
     */
    public static function flushInstance(): void
    {
        static::$instance = [];
    }

    /**
     * Forward static method calls to the underlying instance.
     *
     * @param string $name The method name being called
     * @param array<int, mixed> $arguments The method arguments
     * @return mixed The method return value
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws FacadeObjectNotSetException
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        if (static::$app === null) {
            throw new FacadeObjectNotSetException(static::class);
        }

        $instance = static::getFacade();

        return $instance->$name(...$arguments);
    }
}
