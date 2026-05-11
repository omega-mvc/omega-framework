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
use function max;

/**
 * Builds and executes a SELECT SQL query.
 *
 * This class is responsible for composing SELECT statements, including
 * joins, where conditions, grouping, ordering, limits, offsets, and
 * subqueries. It also provides fetch helpers inherited from AbstractFetch.
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
final class Select extends AbstractFetch
{
    use ConditionTrait;
    use SubQueryTrait;

    /**
     * Create a new SELECT query builder.
     *
     * If the table name is provided as an InnerQuery, its bindings
     * are automatically inherited.
     *
     * @param string|InnerQuery   $tableName   Table name or subquery.
     * @param string[]            $columnsName List of selected columns.
     * @param ConnectionInterface $pdo         Database connection instance.
     * @param string[]|null       $options     Optional custom options (e.g. raw query).
     */
    public function __construct(
        string|InnerQuery $tableName,
        array $columnsName,
        ConnectionInterface $pdo,
        ?array $options = null
    ) {
        $this->subQuery = $tableName instanceof InnerQuery
            ? $tableName
            : new InnerQuery(table: $tableName);

        $this->column = $columnsName;
        $this->pdo    = $pdo;

        // inherit binds from subquery
        if ($tableName instanceof InnerQuery) {
            $this->binds = $tableName->getBind();
        }

        $column      = implode(', ', $columnsName);
        $this->query = $options['query'] ?? "SELECT {$column} FROM { $this->subQuery}";
    }

    /**
     * Cast the query builder to its SQL representation.
     *
     * @return string The compiled SQL query.
     */
    public function __toString(): string
    {
        return $this->builder();
    }

    /**
     * Create a new Select instance using a static factory.
     *
     * @param string              $tableName  Table name.
     * @param string[]            $columnName Selected columns.
     * @param ConnectionInterface $pdo         Database connection.
     * @return Select
     */
    public static function from(string $tableName, array $columnName, ConnectionInterface $pdo): Select
    {
        return new Select($tableName, $columnName, $pdo);
    }

    /**
     * Add a JOIN clause to the query.
     *
     * Supports INNER, LEFT, RIGHT, and FULL joins.
     * Bindings from the joined query are automatically merged.
     *
     * @param AbstractJoin $refTable Join reference.
     * @return $this
     */
    public function join(AbstractJoin $refTable): self
    {
        // override master table
        $refTable->table($this->subQuery->getAlias());

        $this->join[] = $refTable->stringJoin();
        $binds        = (fn () => $this->{'subQuery'})->call($refTable);

        if (null !== $binds) {
            $this->binds = array_merge($this->binds, $binds->getBind());
        }

        return $this;
    }

    /**
     * Build JOIN clauses.
     *
     * @return string Compiled JOIN statement.
     */
    private function joinBuilder(): string
    {
        return 0 === count($this->join)
            ? ''
            : implode(' ', $this->join);
    }

    /**
     * Set the LIMIT range.
     *
     * @param int $limitStart Starting row.
     * @param int $limitEnd   Number of rows.
     * @return $this
     */
    public function limit(int $limitStart, int $limitEnd): self
    {
        $this->limitStart($limitStart);
        $this->limitEnd($limitEnd);

        return $this;
    }

    /**
     * Set the LIMIT start value.
     *
     * @param int $value Starting row, minimum 0.
     * @return $this
     */
    public function limitStart(int $value): self
    {
        $this->limitStart = max($value, 0);

        return $this;
    }

    /**
     * Set the LIMIT end value.
     *
     * A value of zero disables row limiting.
     *
     * @param int $value Number of rows.
     * @return $this
     */
    public function limitEnd(int $value): self
    {
        $this->limitEnd = max($value, 0);

        return $this;
    }

    /**
     * Set the OFFSET value.
     *
     * @param int $value Offset, minimum 0.
     * @return $this
     */
    public function offset(int $value): self
    {
        $this->offset = max($value, 0);

        return $this;
    }

    /**
     * Set LIMIT and OFFSET in a single call.
     *
     * @param int $limit  Number of rows.
     * @param int $offset Offset value.
     * @return $this
     */
    public function limitOffset(int $limit, int $offset): self
    {
        return $this
            ->limitStart($limit)
            ->limitEnd(0)
            ->offset($offset);
    }

    /**
     * Add an ORDER BY clause.
     *
     * @param string      $columnName Column name.
     * @param int         $orderUsing Sort direction (Query::ORDER_ASC|ORDER_DESC).
     * @param string|null $belongTo   Table or alias name.
     * @return $this
     */
    public function order(
        string $columnName,
        int $orderUsing = Query::ORDER_ASC,
        ?string $belongTo = null
    ): self {
        $order    = 0 === $orderUsing ? 'ASC' : 'DESC';
        $belongTo ??= null === $this->subQuery
            ? $this->table
            : $this->subQuery->getAlias();

        $res = "{$belongTo}.{$columnName}";
        $this->sortOrder[$res] = $order;

        return $this;
    }

    /**
     * Order results by column presence (IS NOT NULL).
     *
     * @param string      $columnName Column name.
     * @param int         $orderUsing Sort direction.
     * @param string|null $belongTo   Table or alias name.
     * @return $this
     */
    public function orderIfNotNull(
        string $columnName,
        int $orderUsing = Query::ORDER_ASC,
        ?string $belongTo = null
    ): self {
        return $this->order($columnName . ' IS NOT NULL', $orderUsing, $belongTo);
    }

    /**
     * Order results by column absence (IS NULL).
     *
     * @param string      $columnName Column name.
     * @param int         $orderUsing Sort direction.
     * @param string|null $belongTo   Table or alias name.
     * @return $this
     */
    public function orderIfNull(
        string $columnName,
        int $orderUsing = Query::ORDER_ASC,
        ?string $belongTo = null
    ): self {
        return $this->order($columnName . ' IS NULL', $orderUsing, $belongTo);
    }

    /**
     * Add GROUP BY clauses.
     *
     * @param string ...$groups Column names.
     * @return $this
     */
    public function groupBy(string ...$groups): self
    {
        $this->groupBy = $groups;

        return $this;
    }

    /**
     * Compile the final SELECT SQL query.
     *
     * @return string The generated SQL statement.
     */
    protected function builder(): string
    {
        $column = implode(', ', $this->column);

        $build = [
            'join'       => $this->joinBuilder(),
            'where'      => $this->getWhere(),
            'group_by'   => $this->getGroupBy(),
            'sort_order' => $this->getOrderBy(),
            'limit'      => $this->getLimit(),
        ];

        $condition = implode(' ', array_filter($build, fn ($item) => $item !== ''));

        return $this->query = "SELECT {$column} FROM {$this->subQuery} {$condition}";
    }

    /**
     * Build LIMIT and OFFSET clause.
     *
     * @return string Return LIMIT and OFFSET clause.
     */
    private function getLimit(): string
    {
        $limit = $this->limitEnd > 0 ? "LIMIT $this->limitEnd" : '';

        if ($this->limitStart === 0) {
            return $limit;
        }

        if ($this->limitEnd === 0 && $this->offset > 0) {
            return "LIMIT $this->limitStart OFFSET $this->offset";
        }

        return "LIMIT $this->limitStart, $this->limitEnd";
    }

    /**
     * Build GROUP BY clause.
     *
     * @return string Return GROUP BY clause.
     */
    private function getGroupBy(): string
    {
        if ([] === $this->groupBy) {
            return '';
        }

        $groupBy = implode(', ', $this->groupBy);

        return "GROUP BY {$groupBy}";
    }

    /**
     * Build ORDER BY clause.
     *
     * @return string Return ORDER BY clause.
     */
    private function getOrderBy(): string
    {
        if ([] === $this->sortOrder) {
            return '';
        }

        $orders = [];
        foreach ($this->sortOrder as $column => $order) {
            $orders[] = "{$column} {$order}";
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    /**
     * Restore pagination and sorting state from external reference.
     *
     * @param int                   $limitStart Starting row.
     * @param int                   $limitEnd   Number of rows.
     * @param int                   $offset     Offset value.
     * @param array<string, string> $sortOrder  Sort configuration.
     * @return void
     */
    public function sortOrderRef(
        int $limitStart,
        int $limitEnd,
        int $offset,
        array $sortOrder
    ): void {
        $this->limitStart = $limitStart;
        $this->limitEnd   = $limitEnd;
        $this->offset     = $offset;
        $this->sortOrder  = $sortOrder;
    }
}
