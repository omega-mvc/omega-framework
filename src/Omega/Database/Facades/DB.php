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

declare(strict_types=1);

namespace Omega\Database\Facades;

use Omega\Database\Connectioninterface;
use Omega\Database\DatabaseManager;
use Omega\Database\Query\InnerQuery;
use Omega\Database\Query\Table;
use Omega\Facade\AbstractFacade;

/**
 * Facade for the Database service.
 *
 * This facade provides a static interface to the underlying `Database` instance
 * resolved from the application container. It allows convenient static-style
 * calls while still relying on dependency injection and the container under the hood.
 *
 * Usage of this facade does not create a global state; the underlying instance
 * is still managed by the container and may be swapped, mocked, or replaced
 * for testing or customization purposes.
 *
 * @category   Omega
 * @package    Database
 * @subpackges Facades
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @method static void                clearConnections()
 * @method static ConnectionInterface connection(string $name)
 * @method static DatabaseManager     setDefaultConnection(ConnectionInterface $connection)
 * @method static DatabaseManager     query(string $query)
 * @method static DatabaseManager     bind(string|int|bool|null $param, mixed $value, string|int|bool|null $type = null)
 * @method static bool                execute()
 * @method static mixed[]|false       resultset()
 * @method static mixed               single()
 * @method static int                 rowCount()
 * @method static bool                transaction(callable $callable)
 * @method static bool                beginTransaction()
 * @method static bool                endTransaction()
 * @method static bool                cancelTransaction()
 * @method static string|false        lastInsertId()
 * @method static void                flushLogs()
 * @method static array               getLogs()
 *
 * @see DatabaseManager
 */
final class DB extends AbstractFacade
{
    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor(): string
    {
        return DatabaseManager::class;
    }

    /**
     * Start a new query builder for the given table.
     *
     * This is a convenience method that automatically uses the default PDO
     * connection instance managed by the database container. It initializes
     * a fresh query builder, optionally using an InnerQuery as a subquery
     * source when provided.
     *
     * @param string|InnerQuery $tableName The name of the table or an InnerQuery instance.
     * @return Table A new Table query builder instance.
     */
    public static function table(string|InnerQuery $tableName): Table
    {
        return new Table($tableName, PDO::getInstance());
    }

    /**
     * Start a new query builder with a custom database connection.
     *
     * This method behaves like `table()` but allows specifying a custom or
     * alternative database connection. Useful in scenarios involving multiple
     * connections or dynamically selected data sources.
     *
     * @param string|InnerQuery $tableName The target table name or InnerQuery instance.
     * @param ConnectionInterface        $pdo       The database connection to associate with the query.
     * @return Table A new Table query builder bound to the provided connection.
     */
    public static function from(string|InnerQuery $tableName, ConnectionInterface $pdo): Table
    {
        return new Table($tableName, $pdo);
    }
}
