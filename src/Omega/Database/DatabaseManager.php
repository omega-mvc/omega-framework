<?php

/**
 * Part of Omega - Database Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

namespace Omega\Database;

use Omega\Database\ConnectionInterface;
use Omega\Database\Exceptions\InvalidConfigurationException;

use function sprintf;

/**
 * Manages multiple database connections and delegates operations
 * to the currently active connection.
 *
 * The DatabaseManager acts as a proxy between the application and the
 * concrete database connection implementation. It is responsible for:
 *
 * - Resolving connections from configuration
 * - Caching instantiated connections
 * - Delegating query, transaction, and logging operations
 *
 * This class does not implement database logic itself, but forwards
 * all calls to the selected ConnectionInterface instance.
 *
 * @category  Omega
 * @package   Database
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class DatabaseManager implements ConnectionInterface
{
    /** @var ConnectionInterface The currently active default connection */
    private ConnectionInterface $connection;

    /** @var array<string, ConnectionInterface> Cached connection instances */
    private array $connections = [];

    /**
     * Create a new DatabaseManager instance.
     *
     * The configuration array contains named connection definitions that
     * will be used to lazily instantiate Connection instances on demand.
     *
     * @param array<string, array<string, string|int|array<int, string|int|bool>|null>> $configs
     *        Database connection configurations indexed by connection name.
     */
    public function __construct(private readonly array $configs)
    {
    }

    /**
     * Clear all cached database connections.
     *
     * This forces new connection instances to be created on the next
     * request for a connection.
     *
     * @return void
     */
    public function clearConnections(): void
    {
        $this->connections = [];
    }

    /**
     * Retrieve a database connection by name.
     *
     * If the connection has not been created yet, it will be instantiated
     * using the corresponding configuration and cached for reuse.
     *
     * @param string $name The name of the configured database connection.
     * @return ConnectionInterface The resolved database connection.
     * @throws InvalidConfigurationException If the connection is not configured.
     */
    public function connection(string $name): ConnectionInterface
    {
        if (!isset($this->connections[$name])) {
            if (!isset($this->configs[$name])) {
                throw new InvalidConfigurationException(
                    sprintf('Database connection [%s] not configured.', $name)
                );
            }

            $config = $this->configs[$name];
            $driver = ucfirst($config['driver']);

            $this->connections[$name] = ConnectionFactory::make($config);
        }

        return $this->connections[$name];
    }

    /**
     * Set the default database connection.
     *
     * All subsequent database operations executed through the
     * DatabaseManager will be delegated to this connection.
     *
     * @param ConnectionInterface $connection The connection to set as default.
     * @return $this Returns the current DatabaseManager instance.
     */
    public function setDefaultConnection(ConnectionInterface $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query): self
    {
        $this->connection->query($query);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bind(int|string|bool|null $param, mixed $value, int|string|bool|null $type = null): self
    {
        $this->connection->bind($param, $value, $type);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): bool
    {
        return $this->connection->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function resultset(): array|false
    {
        return $this->connection->resultset();
    }

    /**
     * {@inheritdoc}
     */
    public function single(): mixed
    {
        return $this->connection->single();
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount(): int
    {
        return $this->connection->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callable): bool
    {
        return $this->connection->transaction($callable);
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function endTransaction(): bool
    {
        return $this->connection->endTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function cancelTransaction(): bool
    {
        return $this->connection->cancelTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId(): string|false
    {
        return $this->connection->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function flushLogs(): void
    {
        $this->connection->flushLogs();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs(): array
    {
        return $this->connection->getLogs();
    }
}
