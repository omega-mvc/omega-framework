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

/** @noinspection PhpSameParameterValueInspection */
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\View;

use Exception;

use function array_combine;
use function array_diff_key;
use function array_fill;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_unique;
use function array_values;
use function array_walk;
use function count;
use function file_exists;
use function file_get_contents;
use function htmlspecialchars;
use function implode;
use function is_bool;
use function is_int;
use function is_null;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function Omega\Application\slash;
use function preg_match;
use function rtrim;
use function str_ends_with;

use const ARRAY_FILTER_USE_BOTH;
use const ENT_QUOTES;
use const JSON_ERROR_NONE;

/**
 * Vite class handles asset management and HMR (Hot Module Replacement) integration.
 *
 * This class reads Vite manifest files, generates HTML tags for JS/CSS assets,
 * supports hot module replacement, and caches asset information for performance.
 *
 * @category  Omega
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Vite
{
    /** @var string Name of the manifest file. */
    private string $manifestName;

    /** @var int Timestamp of the cached manifest file. */
    private int $cacheTime = 0;

    /** @var array<string, array<string, array<string, string>>> Cached manifest data. */
    public static array $cache = [];

    /** @var string|null HMR (Hot Module Replacement) URL if running HMR server. */
    public static ?string $hot = null;

    /** @var string Public path of the application. */
    private readonly string $publicPath;

    /** @var string Path where Vite build assets are stored. */
    private readonly string $buildPath;

    /**
     * Constructor.
     *
     * @param string $publicPath Public path of the application.
     * @param string $buildPath Path where Vite build assets are stored.
     */
    public function __construct(string $publicPath, string $buildPath)
    {
        $this->publicPath   = slash($publicPath);
        $this->buildPath    = slash($buildPath);

        $this->manifestName = 'manifest.json';
    }

    /**
     * Render HTML tags for the given Vite entry point(s).
     *
     * This method returns the appropriate `<script>` and `<link>` tags for JS and CSS assets.
     * If HMR (Hot Module Replacement) is active, it includes the HMR client script and uses HMR URLs.
     *
     * @param string ...$entryPoints Entry point filenames defined in Vite manifest.
     * @return string HTML tags for JS/CSS assets.
     * @throws Exception If a manifest file cannot be read or a resource is missing.
     */
    public function __invoke(string ...$entryPoints): string
    {
        if (empty($entryPoints)) {
            return '';
        }

        return $this->isRunningHRM()
            ? $this->renderHmrTags($entryPoints)
            : $this->renderBuildTags($entryPoints);
    }

    /**
     * Render HTML tags for entry points using the HMR (Hot Module Replacement) server.
     *
     * This method generates the HMR client script tag and resolves each entry point
     * to its corresponding HMR URL, producing the appropriate script or style tags.
     *
     * @param string[] $entryPoints List of entry point filenames.
     * @return string HTML string containing HMR script and resource tags.
     * @throws Exception If the HMR URL cannot be determined.
     */
    private function renderHmrTags(array $entryPoints): string
    {
        $hmrUrl = $this->getHmrUrl();

        $tags = array_merge(
            [$this->getHmrScript()],
            array_map(
                fn ($entry) => $this->createTag($hmrUrl . $entry, $entry),
                $entryPoints
            )
        );

        return implode("\n", $tags);
    }

    /**
     * Render HTML tags for entry points using the HMR (Hot Module Replacement) server.
     *
     * This method generates the HMR client script tag and resolves each entry point
     * to its corresponding HMR URL, producing the appropriate script or style tags.
     *
     * @param string[] $entryPoints List of entry point filenames.
     * @return string HTML string containing HMR script and resource tags.
     * @throws Exception If the HMR URL cannot be determined.
     */
    private function renderBuildTags(array $entryPoints): string
    {
        $imports = $this->getManifestImports($entryPoints);

        $tags = array_merge(
            $this->buildPreloadTags($imports),
            $this->buildAssetTags($entryPoints)
        );

        return implode("\n", $tags);
    }

    /**
     * Generate preload and style tags for imported resources.
     *
     * This method creates <link rel="modulepreload"> tags for imported JavaScript
     * dependencies and <link rel="stylesheet"> tags for associated CSS files.
     *
     * @param array{imports: string[], css: string[]} $imports Import and CSS dependencies.
     * @return string[] List of HTML preload and style tags.
     * @throws Exception If manifest resources cannot be resolved.
     */
    private function buildPreloadTags(array $imports): array
    {
        $importTags = array_map(
            fn ($entry) => $this->createPreloadTag($this->getManifest($entry)),
            $imports['imports']
        );

        $cssTags = array_map(
            fn ($entry) => $this->createStyleTag($this->buildPath . $entry),
            $imports['css']
        );

        return array_merge($importTags, $cssTags);
    }

    /**
     * Generate script and style tags for the given entry points.
     *
     * This method resolves asset URLs from the manifest, separates CSS and JS files,
     * and generates the corresponding HTML tags for each resource.
     *
     * @param string[] $entryPoints List of entry point filenames.
     * @return string[] List of HTML script and style tags.
     * @throws Exception If manifest resources cannot be resolved.
     */
    private function buildAssetTags(array $entryPoints): array
    {
        $assets = $this->gets($entryPoints);

        $cssAssets = array_filter(
            $assets,
            fn ($file) => $this->isCssFile($file)
        );

        $jsAssets = array_diff_key($assets, $cssAssets);

        return array_merge(
            array_map(fn ($url) => $this->createStyleTag($url), $cssAssets),
            array_map(fn ($url) => $this->createScriptTag($url), $jsAssets)
        );
    }

    /**
     * Set a custom manifest filename.
     *
     * @param string $manifestName The manifest file name to use instead of the default.
     * @return $this Fluent interface, returns the current instance.
     */
    public function manifestName(string $manifestName): self
    {
        $this->manifestName = $manifestName;

        return $this;
    }

    /**
     * Flush cached manifest data and HMR URL.
     *
     * This clears the internal cache and resets the HMR URL, forcing reloading of manifest on next access.
     *
     * @return void
     */
    public static function flush(): void
    {
        static::$cache = [];
        static::$hot   = null;
    }

    /**
     * Get the full path to the Vite manifest file.
     *
     * @return string Full path to the manifest file.
     * @throws Exception If the manifest file does not exist.
     */
    public function manifest(): string
    {
        if (file_exists($fileName = "{$this->publicPath}/{$this->buildPath}/{$this->manifestName}")) {
            return $fileName;
        }

        throw new Exception("Manifest file not found {$fileName}");
    }

    /**
     * Load and decode the Vite manifest JSON file.
     *
     * Caches the decoded manifest and reuses it if the manifest file has not changed since last load.
     *
     * @return array<string, array<string, string|string[]>> Decoded manifest data.
     * @throws Exception If the manifest file cannot be read or JSON decoding fails.
     */
    public function loader(): array
    {
        $fileName = $this->manifest();

        if ($this->isCacheValid($fileName)) {
            return static::$cache[$fileName];
        }

        $content = $this->readManifestFile($fileName);
        $data = $this->parseManifestJson($content);

        return $this->updateCache($fileName, $data);
    }

    /**
     * Check if the cached manifest is still valid.
     *
     * Compares the cached timestamp with the current manifest file modification time
     * to determine if the cached data can be reused.
     *
     * @param string $fileName Full path to the manifest file.
     * @return bool True if the cache is valid, false otherwise.
     * @throws Exception Throw when a generic error occurred.
     */
    private function isCacheValid(string $fileName): bool
    {
        return array_key_exists($fileName, static::$cache)
            && $this->cacheTime === $this->manifestTime();
    }

    /**
     * Read the contents of the manifest file from disk.
     *
     * Suppresses warnings from `file_get_contents` and throws an exception if
     * the file cannot be read.
     *
     * @param string $fileName Full path to the manifest file.
     * @return string File contents as a string.
     * @throws Exception If the file cannot be read.
     */
    private function readManifestFile(string $fileName): string
    {
        $content = @file_get_contents($fileName);

        if ($content === false) {
            throw new Exception("Failed to read manifest file: {$fileName}");
        }

        return $content;
    }

    /**
     * Decode the JSON contents of the manifest file.
     *
     * Parses the JSON string into an associative array and validates the result.
     * Throws an exception if decoding fails.
     *
     * @param string $content JSON string read from the manifest file.
     * @return array<string, array<string, string|string[]>> Decoded manifest data.
     * @throws Exception If JSON decoding fails.
     */
    private function parseManifestJson(string $content): array
    {
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Manifest JSON decode error: ' . json_last_error_msg());
        }

        return $json ?? [];
    }

    /**
     * Update the static cache with the newly loaded manifest data.
     *
     * Also updates the internal cache timestamp to the current manifest file modification time.
     *
     * @param string $fileName Full path to the manifest file.
     * @param array<string, array<string, string|string[]>> $data Decoded manifest data to cache.
     * @return array<string, array<string, string|string[]>> The cached manifest data.
     * @throws Exception Throw when a generic error occurred.
     */
    private function updateCache(string $fileName, array $data): array
    {
        $this->cacheTime = $this->manifestTime();
        return static::$cache[$fileName] = $data;
    }

    /**
     * Get the built path for a single resource from the Vite manifest.
     *
     * @param string $resourceName The resource name as defined in the manifest.
     * @return string Full URL/path to the resource file.
     * @throws Exception If the resource is not found in the manifest.
     */
    public function getManifest(string $resourceName): string
    {
        $asset = $this->loader();

        if (!array_key_exists($resourceName, $asset)) {
            throw new Exception("Resource file not found {$resourceName}");
        }

        return $this->buildPath . $asset[$resourceName]['file'];
    }

    /**
     * Collect imports and CSS files for the given resources.
     *
     * This builds an array with 'imports' and 'css' for the requested resources,
     * resolving nested imports recursively.
     *
     * @param string[] $resources List of resource names.
     * @return array{imports: string[], css: string[]} Arrays of import and CSS file paths.
     * @throws Exception If the manifest cannot be loaded.
     */
    public function getManifestImports(array $resources): array
    {
        $assets = $this->loader();

        $initialAssets = array_intersect_key($assets, array_flip($resources));

        $preload = array_reduce(
            $initialAssets,
            function (array $carry, array $asset) use ($assets) {
                $this->collectImports($assets, $asset, $carry);
                return $carry;
            },
            ['imports' => [], 'css' => []]
        );

        return [
            'imports' => array_values(array_unique($preload['imports'])),
            'css'     => array_values(array_unique($preload['css'])),
        ];
    }

    /**
     * Recursively collect CSS and JS import dependencies for a single asset.
     *
     * @param array<string, array<string, string|string[]>> $assets Full manifest assets array.
     * @param array<string, string|string[]> $asset Asset entry to collect dependencies from.
     * @param array{imports: string[], css: string[]} $preload Reference to the preload array to populate.
     * @return void
     */
    private function collectImports(array $assets, array $asset, array &$preload): void
    {
        $preload['css'] = array_merge($preload['css'], (array) ($asset['css'] ?? []));

        $imports = (array) ($asset['imports'] ?? []);

        $preload['imports'] = array_merge($preload['imports'], $imports);

        array_walk($imports, function ($import) use ($assets, &$preload) {
            if (isset($assets[$import])) {
                $this->collectImports($assets, $assets[$import], $preload);
            }
        });
    }

    /**
     * Get the URL for a single resource, using HMR if running.
     *
     * @param string $resourceName Resource name to retrieve.
     * @return string URL to the resource file.
     * @throws Exception If manifest or hot file cannot be loaded.
     */
    public function get(string $resourceName): string
    {
        if (!$this->isRunningHRM()) {
            return $this->getManifest($resourceName);
        }

        $hot = $this->getHmrUrl();

        return $hot . $resourceName;
    }

    /**
     * Get the URLs for multiple resources, using HMR if running.
     *
     * @param string[] $resourceNames List of resource names.
     * @return array<string, string> Mapping of resource name => URL.
     * @throws Exception If manifest or hot file cannot be loaded.
     */
    public function gets(array $resourceNames): array
    {
        if ($this->isRunningHRM()) {
            $hot = $this->getHmrUrl();

            return array_combine(
                $resourceNames,
                array_map(fn ($asset) => $hot . $asset, $resourceNames)
            );
        }

        $asset = $this->loader();

        return array_reduce($resourceNames, function ($carry, $name) use ($asset) {
            if (isset($asset[$name])) {
                $carry[$name] = $this->buildPath . $asset[$name]['file'];
            }
            return $carry;
        }, []);
    }

    /**
     * Determine if the HMR (Hot Module Replacement) server is currently running.
     *
     * @return bool True if HMR is running, false otherwise.
     */
    public function isRunningHRM(): bool
    {
        return is_file("{$this->publicPath}/hot");
    }

    /**
     * Get the base URL of the HMR server.
     *
     * @return string HMR server URL with trailing slash.
     * @throws Exception If the hot file cannot be read.
     */
    public function getHmrUrl(): string
    {
        if (!is_null(static::$hot)) {
            return static::$hot;
        }

        $hotFile = "{$this->publicPath}/hot";
        $hot     = @file_get_contents($hotFile);

        if ($hot === false) {
            throw new Exception("Failed to read hot file: {$hotFile}");
        }

        $hot  = rtrim($hot);
        $dash = str_ends_with($hot, '/') ? '' : '/';

        return static::$hot = $hot . $dash;
    }

    /**
     * Get the HMR client script tag.
     *
     * @return string Script tag for HMR client.
     * @throws Exception If HMR URL cannot be determined.
     */
    public function getHmrScript(): string
    {
        return '<script type="module" src="' . $this->getHmrUrl() . '@vite/client"></script>';
    }

    /**
     * Get the last cached manifest timestamp.
     *
     * @return int Timestamp of the last loaded manifest.
     */
    public function cacheTime(): int
    {
        return $this->cacheTime;
    }

    /**
     * Get the last modification time of the manifest file.
     *
     * @return int Timestamp of the manifest file.
     * @throws Exception If the manifest file cannot be found.
     */
    public function manifestTime(): int
    {
        return filemtime($this->manifest());
    }

    /**
     * Generate preload link tags for given entry points.
     *
     * @param string[] $entryPoints List of entry point resource names.
     * @return string HTML string containing preload tags.
     * @throws Exception If manifest files cannot be read.
     */
    public function getPreloadTags(array $entryPoints): string
    {
        if ($this->isRunningHRM()) {
            return '';
        }

        $imports = $this->getManifestImports($entryPoints);

        $importTags = array_map(
            fn($entry) => $this->createPreloadTag($this->getManifest($entry)),
            $imports['imports']
        );

        $cssTags = array_map(
            fn($entry) => $this->createStyleTag($this->buildPath . $entry),
            $imports['css']
        );

        $tags = array_merge($importTags, $cssTags);

        return implode("\n", $tags);
    }

    /**
     * Generate script and style tags for the given entry points with optional attributes.
     *
     * @param string[] $entryPoints List of entry point resource names.
     * @param array<string|int, string|bool|int|null>|null $attributes Optional HTML attributes.
     * @return string HTML string containing script and style tags.
     * @throws Exception If manifest files cannot be read.
     */
    public function getTags(array $entryPoints, ?array $attributes = null): string
    {
        return $this->getCustomTags(
            array_combine($entryPoints, array_fill(0, count($entryPoints), $attributes ?? []))
        );
    }

    /**
     * Generate custom script and style tags for multiple entry points with per-entry attributes.
     *
     * @param array<string, array<string|int, string|bool|int|null>> $entryPoints Entry points and attributes.
     * @param array<string|int, string|bool|int|null> $defaultAttributes Default attributes applied if not
     *                      provided per entry.
     * @return string HTML string containing custom tags.
     * @throws Exception If manifest files cannot be read.
     */
    public function getCustomTags(array $entryPoints, array $defaultAttributes = []): string
    {
        $tags = [];

        if ($this->isRunningHRM()) {
            $tags[] = $this->getHmrScript();
        }

        $assets    = $this->gets(array_keys($entryPoints));
        $cssAssets = array_filter(
            $assets,
            fn ($file, $url) => $this->isCssFile($file),
            ARRAY_FILTER_USE_BOTH
        );

        $jsAssets = array_diff_key($assets, $cssAssets);
        $tags = array_merge(
            $tags,
            array_map(
                fn ($url, $file) => $this->createStyleTag($url, $entryPoints[$file] ?? $defaultAttributes),
                array_values($cssAssets),
                array_keys($cssAssets)
            ),
            array_map(
                fn ($url, $file) => $this->createScriptTag($url, $entryPoints[$file] ?? $defaultAttributes),
                array_values($jsAssets),
                array_keys($jsAssets)
            )
        );

        return implode("\n", $tags);
    }

    /**
     * Create a single tag for a resource, choosing script or style based on file type.
     *
     * @param string $url Resource URL.
     * @param string $entryPoint Resource entry name.
     * @param array<string|int, string|bool|int|null>|null $attributes Optional HTML attributes.
     * @return string HTML tag string.
     */
    private function createTag(string $url, string $entryPoint, ?array $attributes = null): string
    {
        if ($this->isCssFile($entryPoint)) {
            return $this->createStyleTag($url);
        }

        return $this->createScriptTag($url, $attributes);
    }

    /**
     * Create a script tag with optional attributes.
     *
     * @param string $url Script URL.
     * @param array<string|int, string|bool|int|null>|null $attributes Optional HTML attributes.
     * @return string HTML script tag.
     */
    private function createScriptTag(string $url, ?array $attributes = null): string
    {
        $attributes ??= [];

        if (false === isset($attributes['type'])) {
            $attributes = array_merge(['type' => 'module'], $attributes);
        }

        $attributes['src'] = $this->escapeUrl($url);
        $attributes        = $this->buildAttributeString($attributes);

        return "<script {$attributes}></script>";
    }

    /**
     * Create a style (link) tag with optional attributes.
     *
     * @param string $url CSS file URL.
     * @param array<string|int, string|bool|int|null>|null $attributes Optional HTML attributes.
     * @return string HTML link tag.
     */
    private function createStyleTag(string $url, ?array $attributes = null): string
    {
        if ($this->isRunningHRM()) {
            return $this->createScriptTag($url, $attributes);
        }

        $attributes ??= [];
        $attributes['rel']  = 'stylesheet';
        $attributes['href'] = $this->escapeUrl($url);
        $attributes         = $this->buildAttributeString($attributes);

        return "<link {$attributes}>";
    }

    /**
     * Create a preload link tag for a given resource URL.
     *
     * @param string $url Resource URL to preload.
     * @return string HTML preload link tag.
     */
    private function createPreloadTag(string $url): string
    {
        $attributes = $this->buildAttributeString([
            'rel'  => 'modulepreload',
            'href' => $this->escapeUrl($url),
        ]);

        return "<link {$attributes}>";
    }

    // helper functions

    /**
     * Determine if a filename is a CSS-related file.
     *
     * @param string $filename File name to check.
     * @return bool True if CSS or preprocessor file, false otherwise.
     */
    private function isCssFile(string $filename): bool
    {
        return preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)$/', $filename) === 1;
    }

    /**
     * Escape a URL for safe inclusion in HTML attributes.
     *
     * @param string $url URL to escape.
     * @return string Escaped URL.
     */
    private function escapeUrl(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Build an HTML attribute string from an associative array.
     *
     * @param array<string|int, string|bool|int|null> $attributes Attributes to convert.
     * @return string HTML-ready attribute string.
     */
    private function buildAttributeString(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        $parts = array_filter(array_map(
            function ($key, $value) {
                if (is_int($key)) {
                    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                }

                $key = htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8');

                return match (true) {
                    is_bool($value) => $value ? $key : null,
                    $value === null => null,
                    default => $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"',
                };
            },
            array_keys($attributes),
            $attributes
        ));

        return implode(' ', $parts);
    }
}
