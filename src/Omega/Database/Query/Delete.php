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

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Database\Query;

use Omega\Database\Connectioninterface;
use Omega\Database\Query\Join\AbstractJoin;
use Omega\Database\Query\Traits\ConditionTrait;
use Omega\Database\Query\Traits\SubQueryTrait;

use function array_merge;
use function count;
use function implode;

/**
 * Builds and executes a DELETE SQL query.
 *
 * This class provides a fluent interface to delete records from a table,
 * optionally using table aliases, JOIN clauses, and WHERE conditions.
 * Query execution is delegated to AbstractExecute.
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
class Delete extends AbstractExecute
{
    use ConditionTrait;
    use SubQueryTrait;

    /**
     * Optional alias for the target table.
     *
     * When an alias is defined, the DELETE statement will target the alias
     * instead of the base table name.
     *
     * @var string|null
     */
    protected ?string $alias = null;

    /**
     * Create a new DELETE query builder.
     *
     * @param string              $tableName Table name.
     * @param ConnectionInterface $pdo       Database connection instance.
     */
    public function __construct(string $tableName, ConnectionInterface $pdo)
    {
        $this->table = $tableName;
        $this->pdo   = $pdo;
    }

    /**
     * Cast the builder to its SQL representation.
     *
     * @return string The compiled DELETE SQL query.
     */
    public function __toString(): string
    {
        return $this->builder();
    }

    /**
     * Set an alias for the target table.
     *
     * When an alias is used, DELETE conditions based on bound values are ignored,
     * except when subqueries are involved. JOIN clauses will also reference
     * the alias instead of the base table.
     *
     * @param string $alias Table alias.
     * @return $this
     */
    public function alias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Add a JOIN clause to the DELETE query.
     *
     * Supported join types include:
     *  - INNER JOIN
     *  - LEFT JOIN
     *  - RIGHT JOIN
     *  - FULL JOIN
     *
     * @param AbstractJoin $refTable Join definition.
     * @return $this
     */
    public function join(AbstractJoin $refTable): self
    {
        $table = $this->alias ?? $this->table;
        $refTable->table($table);

        $this->join[] = $refTable->stringJoin();
        $binds        = (fn () => $this->{'subQuery'})->call($refTable);

        if (null !== $binds) {
            $this->binds = array_merge($this->binds, $binds->getBind());
        }

        return $this;
    }

    /**
     * Compile all JOIN clauses.
     *
     * @return string The JOIN portion of the SQL query.
     */
    private function getJoin(): string
    {
        return 0 === count($this->join)
            ? ''
            : implode(' ', $this->join);
    }

    /**
     * Compile the DELETE SQL query.
     *
     * @return string The generated SQL statement.
     */
    protected function builder(): string
    {
        $build = [];

        $build['join']  = $this->getJoin();
        $build['where'] = $this->getWhere();

        $queryParts = implode(
            ' ',
            array_filter($build, fn ($item) => $item !== '')
        );

        return $this->query = null === $this->alias
            ? "DELETE FROM {$this->table} {$queryParts}"
            : "DELETE {$this->alias} FROM {$this->table} AS {$this->alias} {$queryParts}";
    }
}
