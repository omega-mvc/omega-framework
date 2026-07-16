<?php

/**
 * Part of Omega - Application Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Application;

use Exception;
use Omega\Cache\CacheServiceProvider;
use Omega\Container\AbstractServiceProvider;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Cron\CronServiceProvider;
use Omega\Database\DatabaseServiceProvider;
use Omega\Exceptions\WhoopsServiceProvider;
use Omega\Http\MacroServiceProvider;
use Omega\RateLimiter\RateLimiterServiceProvider;
use Omega\Router\RouteServiceProvider;
use Omega\Security\HashServiceProvider;
use Omega\View\ViewServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function file_exists;

/**
 * Default Omega application implementation.
 *
 * This class represents the concrete, framework-provided Application runtime.
 * It defines the default container bindings, filesystem paths, environment
 * resolution, and version handling used by a standard Omega installation.
 *
 * The Application class acts as the primary entry point for bootstrapping,
 * service provider registration, and runtime configuration.
 *
 * Custom applications may extend AbstractApplication to override or replace
 * this implementation when different behavior is required.
 *
 * @category  Omega
 * @package   Application
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Application extends AbstractApplication implements ApplicationInterface
{
    /** @var array<int, class-string<AbstractServiceProvider>> Registered service provider class names. */
    protected array $providers = [
        WhoopsServiceProvider::class,
        CronServiceProvider::class,
        HashServiceProvider::class,
        RouteServiceProvider::class,
        DatabaseServiceProvider::class,
        ViewServiceProvider::class,
        CacheServiceProvider::class,
        RateLimiterServiceProvider::class,
        MacroServiceProvider::class,
    ];

    /**
     * Create a new Application instance.
     *
     * The base path is used to resolve all application directories, configuration
     * files, cache paths, and framework resources.
     *
     * If null is provided, path resolution must be handled externally before
     * accessing path-dependent services.
     *
     * @param string|null $basePath Absolute path to the application root directory.
     * @throws Exception
     */
    public function __construct(?string $basePath = null)
    {
        parent::__construct($basePath);
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function isDownMaintenanceMode(): bool
    {
        return file_exists(get_path('path.storage') . 'app/maintenance.php');
    }

    /**
     * {@inheritdoc}
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function getDownData(): array
    {
        $default = [
            'redirect' => null,
            'retry'    => null,
            'status'   => 503,
            'template' => null,
        ];

        $down = get_path('path.storage') . 'app/down';
        if (!file_exists($down)) {
            return $default;
        }

        /** @var array<string, string|int|null> $config */
        $config = include $down;

        return array_replace($default, $config);
    }
}
