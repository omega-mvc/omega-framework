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

namespace Omega\Database\Query;

use Omega\Database\Connectioninterface;

/**
 * Represents a database table and provides a fluent interface
 * to perform queries such as SELECT, INSERT, UPDATE, DELETE, and REPLACE.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Query
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class Table
{
    /** @var ConnectionInterface Connection instance used to execute queries. */
    protected ConnectionInterface $pdo;

    /** @var string|InnerQuery Name of the table or a subquery instance. */
    protected string|InnerQuery $tableName;

    /**
     * Initialize a Table instance.
     *
     * @param string|InnerQuery   $tableName Name of the table or subquery.
     * @param ConnectionInterface $pdo       Connection instance for query execution.
     */
    public function __construct(string|InnerQuery $tableName, ConnectionInterface $pdo)
    {
        $this->tableName = $tableName;
        $this->pdo       = $pdo;
    }

    /**
     * Create an Insert query builder for this table.
     *
     * @return Insert Returns an Insert query builder instance.
     */
    public function insert(): Insert
    {
        return new Insert($this->tableName, $this->pdo);
    }

    /**
     * Create a Replace query builder for this table.
     *
     * @return Replace Returns a Replace query builder instance.
     */
    public function replace(): Replace
    {
        return new Replace($this->tableName, $this->pdo);
    }

    /**
     * Create a Select query builder for this table.
     *
     * @param string[] $selectColumns Optional array of column names to select. Defaults to ['*'].
     * @return Select Returns a Select query builder instance.
     */
    public function select(array $selectColumns = ['*']): Select
    {
        return new Select($this->tableName, $selectColumns, $this->pdo);
    }

    /**
     * Create an Update query builder for this table.
     *
     * @return Update Returns an Update query builder instance.
     */
    public function update(): Update
    {
        return new Update($this->tableName, $this->pdo);
    }

    /**
     * Create a Delete query builder for this table.
     *
     * @return Delete Returns a Delete query builder instance.
     */
    public function delete(): Delete
    {
        return new Delete($this->tableName, $this->pdo);
    }

    /**
     * Retrieve metadata information about the table columns.
     *
     * Queries INFORMATION_SCHEMA to return column details such as name,
     * type, charset, collation, nullability, ordinal position, and key type.
     *
     * @return array<string, mixed>
     *         Returns an array of column metadata. Returns an empty array if the table has no columns.
     */
    public function info(): array
    {
        $this->pdo->query(
            'SELECT
                COLUMN_NAME,
                COLUMN_TYPE,
                CHARACTER_SET_NAME,
                COLLATION_NAME,
                IS_NULLABLE,
                ORDINAL_POSITION,
                COLUMN_KEY
            FROM
                INFORMATION_SCHEMA.COLUMNS
            WHERE
                TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
        );
        $this->pdo->bind(':table', $this->tableName);

        $result = $this->pdo->resultset();

        return $result === false ? [] : $result;
    }
}
