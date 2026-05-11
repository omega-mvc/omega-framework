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
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function file_exists;
use function rtrim;
use function Omega\Environment\env;
use function Omega\Application\get_path;
use function Omega\Application\set_path;

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
class Application extends AbstractApplication
{
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
     */
    public function setDefinitions(): array
    {
        return [
            'boot.cache'              => $this->basePath . set_path('bootstrap.cache'),
            'path.app'                => $this->basePath . set_path('app'),
            'path.cache'              => $this->basePath . set_path('storage.app.cache'),
            'path.command'            => $this->basePath . set_path('app.Console.Commands'),
            'path.component'          => $this->basePath . set_path('resources.components'),
            'path.controller'         => $this->basePath . set_path('app.Http.Controllers'),
            'path.exception'          => $this->basePath . set_path('app.Exceptions'),
            'path.model'              => $this->basePath . set_path('app.Models'),
            'path.middleware'         => $this->basePath . set_path('app.Middlewares'),
            'path.provider'           => $this->basePath . set_path('app.Providers'),
            'path.view'               => $this->basePath . set_path('resources.views'),
            'path.storage'            => $this->basePath . set_path('storage'),
            'path.logs'               => $this->basePath . set_path('storage.logs'),
            'path.public'             => $this->basePath . set_path('public'),
            'path.migration'          => $this->basePath . set_path('database.migration'),
            'path.seeder'             => $this->basePath . set_path('database.seeders'),
            'path.compiled_view_path' => $this->basePath . set_path('storage.app.view'),
            'path.database'           => $this->basePath . set_path('database'),
            'paths.view'              => array_map(
                fn ($p) => $this->basePath . $p,
                [set_path('resources.views')]
            ),
            'environment'             => env('APP_ENV'),
            'app.debug'               => env('APP_DEBUG'),
            'app.name'                => env('APP_NAME'),
            'app.version'             => env('APP_VERSION')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(?string $name = null): string
    {
        return $name ?? $this->get('app.name');
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(?string $version = null): string
    {
        return $version ?? $this->get('app.version');
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
    public function getApplicationCachePath(): string
    {
        $base = rtrim(get_path('path.base'), "/\\");

        return $base . set_path('bootstrap.cache');
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
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function setConfigPath(): void
    {
        $this->set('path.config', $this->basePath . set_path('config'));
    }
}
