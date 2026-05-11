<?php

/**
 * Part of Omega - View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\View;

use Omega\View\Exceptions\ViewFileNotFoundException;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unshift;
use function in_array;
use function Omega\Application\slash;
use function realpath;
use function reset;

/**
 * Class TemplatorFinder
 *
 * Responsible for locating template files based on registered paths and file extensions.
 * Caches resolved file paths for faster subsequent lookups.
 *
 * @category  Omega
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class TemplatorFinder
{
    /** @var array<string, string> Cached mapping of view names to resolved file paths. */
    protected array $views = [];

    /** @var string[] Registered paths to search for template files. */
    protected array $paths = [];

    /** @var string[] Registered file extensions for templates.
     * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection
     */
    protected array $extensions;

    /**
     * TemplatorFinder constructor.
     *
     * @param string[]   $paths      Initial paths to search for templates.
     * @param string[]|null $extensions Optional list of file extensions. Defaults to ['.template.php', '.php'].
     */
    public function __construct(array $paths, ?array $extensions = null)
    {
        $this->setPaths(array_map(
            fn($path) => slash(path:  $path),
            $paths
        ));

        $this->extensions = $extensions ?? ['.template.php', '.php'];
    }

    /**
     * Find the full file path of a template by its view name.
     *
     * @param string $viewName Name of the view/template.
     * @return string Full file path to the template.
     * @throws ViewFileNotFoundException If the template cannot be found in any registered path.
     */
    public function find(string $viewName): string
    {
        if (isset($this->views[$viewName])) {
            return $this->views[$viewName];
        }

        return $this->views[$viewName] = $this->findInPath($viewName, $this->paths);
    }

    /**
     * Search for a view in a given set of paths.
     *
     * @param string   $viewName Name of the view/template.
     * @param string[] $paths    Array of paths to search.
     * @return string Full file path to the template.
     * @throws ViewFileNotFoundException If the template cannot be found in any registered path.
     */
    protected function findInPath(string $viewName, array $paths): string
    {
        $found = array_filter(array_map(
            fn($path) => array_filter(
                array_map(
                    fn($ext) => $path . slash(path: '/'  . $viewName) . $ext,
                    $this->extensions
                ),
                'file_exists'
            ),
            $paths
        ));

        $found = array_merge(...$found);

        if (!empty($found)) {
            return reset($found);
        }

        throw new ViewFileNotFoundException($viewName);
    }

    /**
     * Add a new path to search for templates.
     *
     * @param string $path Path to add.
     * @return self
     */
    public function addPath(string $path): self
    {
        if (false === in_array($path, $this->paths)) {
            $this->paths[] = $this->resolvePath($path);
        }

        return $this;
    }

    /**
     * Add a new file extension at the beginning of the extension list.
     *
     * @param string $extension File extension (e.g., '.php').
     * @return self
     */
    public function addExtension(string $extension): self
    {
        array_unshift($this->extensions, $extension);
        return $this;
    }

    /**
     * Clear all cached view paths.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->views = [];
    }

    /**
     * Set the paths for the template finder.
     *
     * @param string[] $paths Array of paths to register.
     * @return self
     */
    public function setPaths(array $paths): self
    {
        $this->paths = array_map(
            fn ($path) => $this->resolvePath($path),
            $paths
        );

        return $this;
    }

    /**
     * Get all registered paths.
     *
     * @return string[] Array of registered paths.
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Get all registered file extensions.
     *
     * @return string[] Array of file extensions.
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Resolve a path to its real path, if possible.
     *
     * @param string $path Path to resolve.
     * @return string Resolved path or original if realpath fails.
     */
    protected function resolvePath(string $path): string
    {
        return realpath($path) ?: $path;
    }
}
