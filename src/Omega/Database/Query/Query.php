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
 * Query Builder.
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
class Query
{
    /** @var int Ascending order flag for ORDER BY clauses. */
    public const int ORDER_ASC   = 0;

    /** @var int Descending order flag for ORDER BY clauses. */
    public const int ORDER_DESC  = 1;

    /**
     * Create a new Query builder instance.
     *
     * @param ConnectionInterface $pdo The database connection to use for queries.
     * @return void
     */
    public function __construct(protected ConnectionInterface $pdo)
    {
    }

    /**
     * Invoke method to start a table query dynamically.
     *
     * This allows the object to be called like a function to
     * create a Table builder for a given table.
     *
     * @param string $tableName The name of the table to query.
     * @return Table Returns a new Table query builder instance.
     */
    public function __invoke(string $tableName): Table
    {
        return $this->table($tableName);
    }

    /**
     * Create a Table builder and associate it with a table.
     *
     * @param string|InnerQuery $tableName The name of the table or an inner query.
     * @return Table Returns a new Table query builder instance.
     */
    public function table(string|InnerQuery $tableName): Table
    {
        return new Table($tableName, $this->pdo);
    }

    /**
     * Static helper to create a Table builder for a given table.
     *
     * @param string|InnerQuery   $tableName The table name or an inner query.
     * @param ConnectionInterface $pdo       The database connection to use.
     * @return Table Returns a Table query builder instance.
     */
    public static function from(string|InnerQuery $tableName, ConnectionInterface $pdo): Table
    {
        $conn = new Query($pdo);

        return $conn->table($tableName);
    }
}
