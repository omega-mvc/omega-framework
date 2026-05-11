<?php

/**
 * Part of Omega - Cache Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Cache\Storage;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use FilesystemIterator;
use Omega\Cache\AbstractCache;
use Omega\Cache\Exceptions\CacheConfigurationException;
use Omega\Cache\Exceptions\CachePathException;
use Omega\Cache\Exceptions\InvalidValueIncrementException;
use Omega\Cache\Traits\CacheTimeTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function array_slice;
use function basename;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_dir;
use function is_int;
use function is_null;
use function mkdir;
use function Omega\Application\slash;
use function serialize;
use function sha1;
use function str_split;
use function time;
use function unlink;
use function unserialize;

use const LOCK_EX;

/**
 * File-based cache storage implementation.
 *
 * This class provides a persistent caching mechanism that stores serialized cache
 * entries as files within a structured directory tree. Each cache key is hashed
 * into a multi-level path to prevent filesystem overload in a single directory.
 *
 * The cache entries include metadata such as value, expiration timestamp, and
 * modification time. Expired items are automatically invalidated upon retrieval.
 *
 * @category   Omega
 * @package    Cache
 * @subpackage Storage
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class File extends AbstractCache
{
    use CacheTimeTrait;

    /** @var string The directory path where cache files are stored. */
    protected string $path;

    /**
     * File constructor.
     *
     * Initializes a new File instance with the given options.
     *
     * Required keys in $options:
     * - 'ttl'  : int|DateInterval  The default time-to-live for cache items.
     * - 'path' : string            The directory path where cache files are stored.
     *
     * @param array<string, mixed> $options Configuration options for the storage.
     * @return void
     * @throws CacheConfigurationException If the 'path' option is missing.
     * @throws CachePathException if the cache directory cannot be created or is not writable.
     */
    public function __construct(array $options)
    {
        parent::__construct($options);

        if (empty($options['path'])) {
            throw new CacheConfigurationException('The "path" option is required for File.');
        }

        $this->path = slash($options['path']);

        if (!is_dir($this->path) && !mkdir($this->path, 0777, true)) {
            throw new CachePathException($this->path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $filePath = $this->makePath($key);

        if (false === file_exists($filePath)) {
            return $default;
        }

        $data = file_get_contents($filePath);

        if ($data === false) {
            return $default;
        }

        $cacheData = unserialize($data);

        if (time() >= $cacheData['timestamp']) {
            $this->delete($key);

            return $default;
        }

        return $cacheData['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $filePath  = $this->makePath($key);
        $directory = dirname($filePath);

        if (false === is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $cacheData = [
            'value'     => $value,
            'timestamp' => $this->calculateExpirationTimestamp($ttl),
            'mtime'     => $this->createMtime(),
        ];

        $serializedData = serialize($cacheData);

        return file_put_contents($filePath, $serializedData, LOCK_EX) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $filePath = $this->makePath($key);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileInfo) {
            $filePath = $fileInfo->getRealPath();

            if (basename($filePath) === '.gitignore') {
                continue;
            }

            $action = $fileInfo->isDir() ? 'rmdir' : 'unlink';
            $action($filePath);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        $state = null;

        foreach ($values as $key => $value) {
            $result = $this->set($key, $value, $ttl);
            $state  = is_null($state) ? $result : $result && $state;
        }

        return $state ?: false;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return file_exists($this->makePath($key));
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidValueIncrementException if the current cache value is not an integer and cannot be incremented.
     */
    public function increment(string $key, int $value): int
    {
        if (false === $this->has($key)) {
            $this->set($key, $value, 0);

            return $value;
        }

        $info = $this->getInfo($key);

        $ori = $info['value'] ?? 0;
        $ttl = $info['timestamp'] ?? 0;

        if (false === is_int($ori)) {
            throw new InvalidValueIncrementException('Value increment must be an integer.');
        }

        $result = (int) ($ori + $value);

        $this->set($key, $result, $ttl);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo(string $key): array
    {
        $filePath = $this->makePath($key);

        if (false === file_exists($filePath)) {
            return [];
        }

        $data = file_get_contents($filePath);

        if (false === $data) {
            return [];
        }

        return unserialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateExpirationTimestamp(int|DateInterval|DateTimeInterface|null $ttl): int
    {
        if ($ttl instanceof DateInterval) {
            return new DateTimeImmutable()->add($ttl)->getTimestamp();
        }

        if ($ttl instanceof DateTimeInterface) {
            return $ttl->getTimestamp();
        }

        $ttl ??= $this->defaultTTL;

        return time() + $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public static function isSupported(): bool
    {
        return true;
    }

    /**
     * Generate the full file path for a given cache key.
     *
     * Converts the cache key into a deterministic hashed path to ensure
     * even file distribution and avoid filesystem performance degradation.
     *
     * Example:
     * ```php
     * $path = $this->makePath('user_123');
     * // "/var/cache/12/ab/12ab34cd...etc."
     * ```
     *
     * @param string $key The cache key for which to generate the file path.
     * @return string The fully qualified path to the cache file.
     */
    protected function makePath(string $key): string
    {
        $hash  = sha1($key);
        $parts = array_slice(str_split($hash, 2), 0, 2);

        return $this->path . '/' . implode('/', $parts) . '/' . $hash;
    }
}
