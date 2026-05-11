<?php

declare(strict_types=1);

namespace Omega\Database;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

abstract class AbstractConnection implements ConnectionInterface
{
    /** @var PDO Active PDO instance */
    protected PDO $pdo;

    /** @var PDOStatement Prepared PDO statement */
    private PDOStatement $statement;

    /** @var array<int, string|int|bool> Default PDO options. */
    protected array $defaultOptions = [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
    ];

    /**
     * Normalized database connection configuration.
     *
     * @var array{
     *     driver: string,
     *     host: ?string,
     *     database: ?string,
     *     port: ?int,
     *     charset: ?string,
     *     username: ?string,
     *     password: ?string,
     *     options: array<int, string|int|bool>
     * }
     */
    protected array $configs;

    /** @var string Currently prepared SQL query. */
    protected string $query;

    /** @var array<int, array<string, mixed>> Logs of executed queries with query, start, end, and duration. */
    protected array $logs = [];

    public function __construct(array $configs)
    {
        $this->configs = $this->normalizeConfigs($configs);

        $dsn = $this->buildDsn();

        $this->pdo = $this->createPdo(
            $dsn,
            $this->configs['username'],
            $this->configs['password'],
            $this->mergeOptions($this->configs['options'] ?? [])
        );
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Normalize configuration once for all drivers.
     */
    protected function normalizeConfigs(array $configs): array
    {
        return [
            'driver' => $configs['driver'] ?? null,
            'host' => $configs['host'] ?? null,
            'database' => $configs['database_name'] ?? $configs['database'] ?? null,
            'port' => $configs['port'] ?? null,
            'charset' => $configs['charset'] ?? null,
            'username' => $configs['user'] ?? $configs['username'] ?? null,
            'password' => $configs['password'] ?? null,
            'path' => $configs['path'] ?? null,
            'options' => $configs['options'] ?? [],
        ];
    }

    /**
     * Merge driver options with defaults.
     */
    protected function mergeOptions(array $options): array
    {
        return $options + $this->defaultOptions;
    }

    /**
     * Create PDO with retry on lost connection.
     */
    protected function createPdo(
        string  $dsn,
        ?string $username,
        ?string $password,
        array   $options
    ): PDO
    {
        try {
            return new PDO($dsn, $username ?? '', $password ?? '', $options);
        } catch (PDOException $e) {
            if ($this->isLostConnection($e)) {
                return new PDO($dsn, $username ?? '', $password ?? '', $options);
            }

            throw $e;
        }
    }

    /**
     * Centralized lost connection detection.
     */
    protected function causedByLostConnection(Throwable $e): bool
    {
        $errors = [
            // MySQL/MariaDB
            'child connection forced to terminate due to client_idle_limit',
            'SQLSTATE[HY000] [2002] Operation in progress',
            'Error writing data to the connection',
            'running with the --read-only option',
            'Server is in script upgrade mode',
            'Packets out of order. Expected',
            'Resource deadlock avoided',
            'is dead or not enabled',
            'server has gone away',
            'Error while sending',
            'query_wait_timeout',
            'Lost connection',
            // PostgresSQL
            'could not connect to server: Connection refused',
            'server closed the connection unexpectedly',
            'connection is no longer usable',
            'no connection to the server',
            // SQLite
            'No such file or directory',
            'Transaction() on null',
            // SSL
            'SQLSTATE[HY000]: General error: 7 SSL SYSCALL error',
            'SSL connection has been closed unexpectedly',
            'decryption failed or bad record mac',
            'SSL: Connection timed out',
            'SSL: Operation timed out',
            'SSL: Broken pipe',
            // Network error
            'The connection is broken and recovery is not possible',
            'Physical connection is not usable',
            'Communication link failure',
            'No route to host',
            'reset by peer',
            // Network timeout
            'Connection timed out',
            'Login timeout expired',
            // General error
            'SQLSTATE[HY000] [2002] Connection refused',
            'SQLSTATE[08S01]: Communication link failure',
            'php_network_getaddresses: getaddrinfo failed',
            'The client was disconnected by the server because of inactivity',
            'Temporary failure in name resolution',
            'could not translate host name',
        ];

        $message = $e->getMessage();

        return array_any($errors, fn($error) => false !== stripos($message, $error));
    }

    /**
     * Return the current connection instance.
     *
     * This method exists for backward compatibility and does not
     * implement a real singleton pattern.
     *
     * @return self
     */
    public function getInstance(): self
    {
        return $this;
    }

    /**
     * Each driver must provide its own DSN.
     */
    abstract protected function buildDsn(): string;

    /**
     * Add a query execution log entry.
     *
     * @param string $query The executed SQL query.
     * @param float $startTime Query start timestamp in seconds.
     * @param float $endTime Query end timestamp in seconds.
     * @return void
     */
    protected function addLog(string $query, float $startTime, float $endTime): void
    {
        $this->logs[] = [
            'query'    => $query,
            'started'  => $startTime,
            'ended'    => $endTime,
            'duration' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query): self
    {
        $this->statement = $this->pdo->prepare($this->query = $query);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bind(string|int|bool|null $param, mixed $value, string|int|bool|null $type = null): self
    {
        if (is_null($type)) {
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
        }
        $this->statement->bindValue($param, $value, $type);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): bool
    {
        $start    = microtime(true);
        $execute  = $this->statement->execute();

        $this->addLog($this->query, $start, microtime(true));

        return $execute;
    }

    /**
     * {@inheritdoc}
     */
    public function resultset(): array|false
    {
        $this->execute();

        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function single(): mixed
    {
        $this->execute();

        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callable): bool
    {
        try {
            if (false === $this->beginTransaction()) {
                return false;
            }

            $return_call =  call_user_func($callable, $this);
            if (true !== $return_call) {
                $this->cancelTransaction();

                return false;
            }

            return $this->endTransaction();
        } catch (Throwable) {
            $this->cancelTransaction();

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function endTransaction(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function cancelTransaction(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * {@inheritdoc}
     */
    public function flushLogs(): void
    {
        $this->logs = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs(): array
    {
        foreach ($this->logs as &$log) {
            $log['duration'] ??= round(($log['ended'] - $log['started']) * 1000, 2);
        }

        unset($log);

        return $this->logs;
    }
}
