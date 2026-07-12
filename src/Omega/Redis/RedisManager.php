<?php

declare(strict_types=1);

namespace Omega\Redis;

use Closure;
use Exception;

use function is_callable;

class RedisManager implements RedisInterface
{
    /** @var array<string, RedisInterface|Closure(): RedisInterface> */
    private array $driver = [];

    private RedisInterface $defaultDriver;

    public function __construct()
    {
    }

    public function setDefaultDriver(RedisInterface $driver): self
    {
        $this->defaultDriver = $driver;

        return $this;
    }

    /**
     * @param Closure(): RedisInterface|RedisInterface $driver
     */
    public function setDriver(string $driverName, RedisInterface|Closure $driver): self
    {
        $this->driver[$driverName] = $driver;

        return $this;
    }

    /**
     * @throws Exception
     */
    private function resolve(string $driverName): RedisInterface
    {
        $driver = $this->driver[$driverName];

        if (is_callable($driver)) {
            $driver = $driver();
        }

        if (null === $driver) {
            throw new Exception("Can not use driver $driverName.");
        }

        return $this->driver[$driverName] = $driver;
    }

    /**
     * @throws Exception
     */
    public function driver(?string $driverName = null): RedisInterface
    {
        if ($driverName === null) {
            return $this->defaultDriver;
        }

        if (isset($this->driver[$driverName])) {
            return $this->resolve($driverName);
        }

        return $this->defaultDriver;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function get(string $key): mixed
    {
        return $this->driver()->get($key);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function set(string $key, mixed $value, ?int $timeout = null): bool
    {
        return $this->driver()->set($key, $value, $timeout);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function del(string|array $keys): int
    {
        return $this->driver()->del($keys);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function exists(string $key): bool
    {
        return $this->driver()->exists($key);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function incr(string $key): int|false
    {
        return $this->driver()->incr($key);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function decr(string $key): int|false
    {
        return $this->driver()->decr($key);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function keys(string $pattern): array
    {
        return $this->driver()->keys($pattern);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function client(): object
    {
        return $this->driver()->client();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function getName(): ?string
    {
        return $this->driver()->getName();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function disconnect(): void
    {
        $this->driver()->disconnect();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function command(string $command, array $arguments = []): mixed
    {
        return $this->driver()->command($command, $arguments);
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->driver()->{$method}(...($arguments));
    }
}
