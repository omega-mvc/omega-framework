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

use function array_filter;
use function array_merge;
use function count;
use function implode;

/**
 * Builds and executes an UPDATE SQL query.
 *
 * This class provides a fluent interface to update one or more columns,
 * apply JOIN clauses, and define WHERE conditions. Query execution is
 * delegated to AbstractExecute.
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
class Update extends AbstractExecute
{
    use ConditionTrait;
    use SubQueryTrait;

    /**
     * Create a new UPDATE query builder.
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
     * @return string The compiled UPDATE SQL query.
     */
    public function __toString(): string
    {
        return $this->builder();
    }

    /**
     * Set multiple column-value pairs for the UPDATE statement.
     *
     * @param array<string, string|int|bool|null> $values Column-value map.
     * @return $this
     */
    public function values(array $values): self
    {
        foreach ($values as $key => $value) {
            $this->value($key, $value);
        }

        return $this;
    }

    /**
     * Set a single column-value pair for the UPDATE statement.
     *
     * The value is automatically bound using a prefixed placeholder.
     *
     * @param string               $bind  Column name.
     * @param string|int|bool|null $value Column value.
     * @return $this
     */
    public function value(string $bind, string|int|bool|null $value): self
    {
        $this->binds[] = Bind::set($bind, $value, $bind)
            ->prefixBind(':bind_');

        return $this;
    }

    /**
     * Add a JOIN clause to the UPDATE query.
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
        // override master table
        $refTable->table($this->table);

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
     * Compile the UPDATE SQL query.
     *
     * @return string The generated SQL statement.
     */
    protected function builder(): string
    {
        $setter = [];
        foreach ($this->binds as $bind) {
            if ($bind->hasColumName()) {
                $setter[] = $bind->getColumnName() . ' = ' . $bind->getBind();
            }
        }

        $build          = [];
        $build['join']  = $this->getJoin();
        $build[]        = 'SET ' . implode(', ', $setter);
        $build['where'] = $this->getWhere();

        $queryParts = implode(
            ' ',
            array_filter($build, fn ($item) => $item !== '')
        );

        return $this->query = "UPDATE {$this->table} {$queryParts}";
    }
}
