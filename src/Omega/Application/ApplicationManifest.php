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

use function array_column;
use function array_filter;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_values;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function var_export;
use function Omega\Application\slash;

use const PHP_EOL;

/**
 * ApplicationManifest handles caching and retrieval of package information.
 *
 * This class reads installed Composer packages, extracts relevant configuration
 * data, and caches it to a PHP file for faster access. It provides methods to
 * retrieve service providers and other package-related metadata.
 *
 * @category  Omega
 * @package   Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final class ApplicationManifest
{
    /** @var string $basePath The base path of the application. */
    private readonly string $basePath;

    /** @var string Path where cached package manifest is stored. */
    private readonly string $applicationCachePath;

    /** @var array<string, array<string, array<int, string>>>|null Cached package manifest. */
    public ?array $applicationManifest = null;

    /**
     * Constructor for ApplicationManifest.
     *
     * @param string      $basePath             The base path of the application.
     * @param string      $applicationCachePath Path where cached package manifest is stored.
     * @param string|null $vendorPath           Optional vendor path; defaults to '/vendor/composer/'.
     */
    public function __construct(string $basePath, string $applicationCachePath, private ?string $vendorPath = null)
    {
        $this->basePath             = slash($basePath);
        $this->applicationCachePath = slash($applicationCachePath);

        $this->vendorPath = $vendorPath !== null
            ? slash($vendorPath)
            : slash('/vendor/composer/');
    }

    /**
     * Get all registered providers from the cached package manifest.
     *
     * @return string[] List of provider class names.
     */
    public function providers(): array
    {
        return $this->config('providers');
    }

    /**
     * Retrieve an array of values for a given key from the package manifest.
     *
     * @param string $key The key to retrieve from each package configuration.
     * @return string[] Array of non-empty values for the given key.
     */
    private function config(string $key): array
    {
        $manifest = $this->getApplicationManifest();

        $values = array_column($manifest, $key);

        $values = array_map(
            static fn ($value): array => (array) $value,
            array_filter(
                $values,
                static fn ($value): bool => $value !== null
            )
        );

        return array_values(
            array_filter(
                array_merge(...$values),
                static fn ($value): bool => false === empty($value)
            )
        );
    }

    /**
     * Get the cached package manifest, building it if it does not exist.
     *
     * @return array<string, array<string, array<int, string>>> Cached package manifest.
     */
    private function getApplicationManifest(): array
    {
        if ($this->applicationManifest) {
            return $this->applicationManifest;
        }

        if (false === file_exists($this->applicationCachePath . 'packages.php')) {
            $this->build();
        }

        return $this->applicationManifest = require $this->applicationCachePath . 'packages.php';
    }

    /**
     * Build the package manifest cache from installed Composer packages.
     *
     * Scans the composer installed.json file, extracts 'omega-mvc' extra data,
     * and writes a cached PHP file for future access.
     *
     * @return void
     */
    public function build(): void
    {
        $file = $this->basePath . $this->vendorPath . 'installed.json';

        $packages = file_exists($file)
            ? json_decode(file_get_contents($file), true)['packages'] ?? []
            : [];

        $provider = array_reduce(
            $packages,
            static function (array $carry, array $package): array {
                if (isset($package['extra']['omega-mvc'])) {
                    $carry[$package['name']] = $package['extra']['omega-mvc'];
                }
                return $carry;
            },
            []
        );

        file_put_contents(
            $this->applicationCachePath . 'packages.php',
            '<?php return ' . var_export($provider, true) . ';' . PHP_EOL
        );
    }
}
