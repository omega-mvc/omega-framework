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
use Omega\Cache\AbstractCache;
use Omega\Cache\Traits\CacheTimeTrait;

use function array_key_exists;
use function time;

/**
 * In-memory array-based cache storage implementation.
 *
 * This class provides a fast, ephemeral caching mechanism where all entries
 * are stored in memory using an internal array. It is ideal for testing
 * or short-lived caching scenarios and does not persist data between requests.
 *
 * Cache entries include metadata such as value, expiration timestamp, and
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
class Memory extends AbstractCache
{
    use CacheTimeTrait;

    /**
     * Internal array holding cached items and their metadata.
     *
     * Format:
     * ```php
     * [
     *   'key_name' => [
     *       'value'     => mixed,
     *       'timestamp' => int,    // expiration time
     *       'mtime'     => float,  // creation/modification time
     *   ],
     * ]
     * ```
     *
     * @var array<string, array{value: mixed, timestamp?: int, mtime?: float}>
     */
    protected array $storage = [];

    /**
     * Memory constructor.
     *
     * Initializes a new Memory instance with the given options.
     *
     * Required keys in $options:
     * - 'ttl' : int|DateInterval  The default time-to-live for cache items.
     *
     * @param array<string, mixed> $options Configuration options for the storage.
     * @return void
     */
    public function __construct(array $options)
    {
        parent::__construct($options['ttl']);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (false === array_key_exists($key, $this->storage)) {
            return $default;
        }

        $item = $this->storage[$key];

        $expiresAt = $item['timestamp'] ?? 0;

        if ($this->isExpired($expiresAt)) {
            $this->delete($key);

            return $default;
        }

        return $item['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $this->storage[$key] = [
            'value'     => $value,
            'timestamp' => $this->calculateExpirationTimestamp($ttl),
            'mtime'     => $this->createMtime(),
        ];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        if ($this->has($key)) {
            unset($this->storage[$key]);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->storage = [];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->storage);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value): int
    {
        if (false === $this->has($key)) {
            $this->set($key, $value, 0);

            return $this->storage[$key]['value'];
        }

        $this->storage[$key]['value'] = ((int) $this->storage[$key]['value']) + $value;

        return $this->storage[$key]['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo(string $key): array
    {
        return $this->storage[$key] ?? [];
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

        return new DateTimeImmutable()->add(new DateInterval("PT{$ttl}S"))->getTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    public static function isSupported(): bool
    {
        return true;
    }

    /**
     * Determines if a given timestamp indicates an expired cache item.
     *
     * @param int $timestamp The expiration timestamp of a cache item.
     * @return bool Returns true if the cache item has expired, false otherwise.
     */
    private function isExpired(int $timestamp): bool
    {
        return $timestamp !== 0 && time() >= $timestamp;
    }
}
