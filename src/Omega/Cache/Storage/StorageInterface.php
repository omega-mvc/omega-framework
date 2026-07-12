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
use DateTimeInterface;

/**
 * Interface for cache storage drivers.
 *
 * This interface defines the essential methods that any cache storage implementation
 * must provide. It focuses on tracking cache metadata, handling expiration,
 * and providing precise creation/modification timestamps.
 *
 * Implementations can vary from in-memory storage (like arrays), file-based storage,
 * or external systems (like Redis or Memcached). Each implementation must respect
 * the TTL (time-to-live) policy and provide consistent metadata for cache items.
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
interface StorageInterface
{
    /**
     * Retrieves metadata information about a specific cache entry.
     *
     * Implementations should return an associative array that includes at least
     * the stored value and optionally other metadata such as creation time
     * or modification time.
     *
     * Example:
     * ```php
     * [
     *   'key_name' => [
     *       'value'     => 'cached_value',
     *       'timestamp' => 1697123456,
     *       'mtime'     => 1697123456.123
     *   ]
     * ]
     * ```
     *
     * @param string $key The cache item key to retrieve information for.
     * @return array<string, array{value: mixed, timestamp?: int, mtime?: float}>
     *                Returns an array containing metadata for the given key.
     */
    public function getInfo(string $key): array;

    /**
     * Calculates the cache item's expiration timestamp based on the provided TTL.
     *
     * Implementations should convert the TTL (in seconds or as a DateInterval)
     * into a UNIX timestamp representing the moment when the cache item expires.
     * If the TTL is `null`, the cache item should be considered persistent.
     *
     * @param int|DateInterval|DateTimeInterface|null $ttl The time-to-live value.
     *        - `int`: Number of seconds until expiration.
     *        - `DateInterval`: A relative interval added to the current time.
     *        - `DateTimeInterface`: A specific expiration moment.
     *        - `null`: No expiration (persistent cache).
     * @return int Returns the UNIX timestamp representing the expiration time.
     */
    public function calculateExpirationTimestamp(int|DateInterval|DateTimeInterface|null $ttl): int;

    /**
     * Determines whether the current cache backend is supported in
     * the running environment.
     *
     * This method allows conditional usage of cache pools depending
     * on platform capabilities or extension availability.
     *
     * @return bool True if the cache backend is supported, false otherwise.
     */
    public static function isSupported(): bool;
}
