<?php

/**
 * Part of Omega - Tests\Database Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Database;

use Omega\Database\ConnectionInterface;
use Omega\Database\DatabaseManager;
use Omega\Database\Exceptions\InvalidConfigurationException;
use Omega\Database\Query\Insert;
use Omega\Database\Schema\Schema;
use Omega\Database\Schema\SchemaConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractTestDatabase
 *
 * Provides a base test class for database-related tests. It handles
 * creating and dropping connections, initializing schemas, and
 * managing a DatabaseManager instance. It supports multiple database
 * drivers (MySQL, MariaDB, SQLite) and ensures isolated test
 * environments for each test case.
 *
 * @category  Tests
 * @package   Database
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(ConnectionInterface::class)]
#[CoversClass(SchemaConnection::class)]
#[CoversClass(DatabaseManager::class)]
#[CoversClass(Insert::class)]
#[CoversClass(Schema::class)]
abstract class AbstractTestDatabase extends TestCase
{
    /** @var array<string, string|int> Database connection environment variables */
    protected array $env;

    /** @var Connectioninterface Main PDO connection instance for tests */
    protected ConnectionInterface $pdo;

    /** @var SchemaConnection Schema-level connection instance */
    protected SchemaConnection $pdoSchema;

    /** @var Schema Schema instance for database creation and teardown */
    protected Schema $schema;

    /** @var DatabaseManager Database manager instance for executing queries */
    protected DatabaseManager $db;

    /**
     * Create the database connection and initialize schema and DatabaseManager.
     *
     * @return void
     */
    protected function createConnection(): void
    {
        $this->setupEnv($_ENV['DB_CONNECTION'] ?? 'mysql');

        $this->pdoSchema = new SchemaConnection($this->env);
        $this->schema    = new Schema($this->pdoSchema, $this->env['database']);

        $this->schema->create()
            ->database($this->env['database'])
            ->ifNotExists()
            ->execute();

        $class = $this->resolveConnectionClass($this->env['driver']);

        $this->pdo = new $class($this->env);

        $this->db = new DatabaseManager($this->getConfiguration());
        $this->db->setDefaultConnection($this->pdo);
    }

    /**
     * Drop the test database.
     *
     * @return void
     */
    protected function dropConnection(): void
    {
        $this->schema->drop()->database($this->env['database'])->ifExists()->execute();
    }

    /**
     * Create the "users" table in the test database.
     *
     * @return bool True on success, false on failure
     */
    protected function createUserSchema(): bool
    {
        return $this
            ->pdo
            ->query('CREATE TABLE users (
                user      varchar(32)  NOT NULL,
                password  varchar(500) NOT NULL,
                stat      int(2)       NOT NULL,
                PRIMARY KEY (user)
            )')
            ->execute();
    }

    /**
     * Get configuration for supported database connections.
     *
     * @return array<string, array<string, string|int>> Configuration array
     */
    protected function getConfiguration(): array
    {
        return [
            'mysql' => [
                'driver'   => 'mysql',
                'host'     => '127.0.0.1',
                'username' => 'root',
                'password' => 'vb65ty4',
                'database' => 'testing_db',
                'port'     => 3306,
                'charset'  => 'utf8mb4',
            ],
            'sqlite' => [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ],
        ];
    }

    /**
     * Set up environment variables for the requested connection type.
     *
     * @param string $useConnection Connection type ('mysql', 'mariadb', 'sqlite')
     * @return void
     */
    protected function setupEnv(string $useConnection = 'mysql'): void
    {
        $configuration = $this->getConfiguration();

        $this->env = match ($useConnection) {
            'mysql', 'mariadb' => $configuration['mysql'],
            'sqlite' => $configuration['sqlite'],
        };
    }

    /**
     * Insert new users into the "users" table.
     *
     * @param array<int, array<string, string|int|bool|null>> $users Array of user data [{user, password, stat}]
     * @return bool True on successful insert, false otherwise
     */
    protected function createUser(array $users): bool
    {
        return (new Insert('users', $this->pdo))
            ->rows($users)
            ->execute();
    }

    protected function resolveConnectionClass(string $driver): string
    {
        return match ($driver) {
            'mysql'   => \Omega\Database\MysqlConnection::class,
            'mariadb' => \Omega\Database\MariaDbConnection::class,
            'pgsql'   => \Omega\Database\PgsqlConnection::class,
            'sqlite'  => \Omega\Database\SqliteConnection::class,
            default   => throw new InvalidConfigurationException(
                "Unsupported database driver [$driver]."
            ),
        };
    }
}
