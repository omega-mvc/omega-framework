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

/** @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection */
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Database\Query;

use Omega\Database\Connectioninterface;

use function array_filter;
use function array_map;
use function implode;
use function in_array;
use function is_bool;
use function is_string;
use function str_contains;
use function str_replace;

/**
 * Abstract base class for query builders.
 *
 * Provides common functionality for constructing SQL queries,
 * managing table names, columns, bindings, filters, joins,
 * grouping, and sort orders.
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
abstract class AbstractQuery
{
    /** @var int Ascending order flag */
    public const int ORDER_ASC  = 0;

    /** @var int Descending order flag */
    public const int ORDER_DESC = 1;

    /** @var ConnectionInterface PDO connection instance */
    protected ConnectionInterface $pdo;

    /** @var string Main SQL query */
    protected string $query;

    /** @var string Table name used in the query */
    protected string $table = '';

    /** @var InnerQuery|null Optional subquery for nested queries */
    protected ?InnerQuery $subQuery = null;

    /** @var string[] Columns to select */
    protected array $column = ['*'];

    /** @var Bind[] Binder array for PDO prepared statements. */
    protected array $binds = [];

    /** @var int LIMIT start value */
    protected int $limitStart = 0;

    /** @var int LIMIT end value */
    protected int $limitEnd = 0;

    /** @var int OFFSET value */
    protected int $offset = 0;

    /** @var array<string, string> Column sorting order ASC|DESC */
    protected array $sortOrder = [];

    /** @var string[] WHERE clause statements */
    protected array $where = [];

    /** @var string[] GROUP BY columns */
    protected array $groupBy = [];

    /** @var array<int, array<string, array<string, array<string, string>>>> Multi-filter groups with strict mode. */
    protected array $groupFilters = [];

    /** Single filters with key => value pairs */
    protected array $filters = [];

    /** @var bool Whether filters are combined with AND (true) or OR (false) */
    protected bool $strictMode = true;

    /** @var string[] JOIN clauses */
    protected array $join = [];

    /**
     * Reset all query builder properties to default values.
     *
     * @return self Returns the current instance for chaining.
     */
    public function reset(): self
    {
        $this->table        = '';
        $this->subQuery     = null;
        $this->column       = ['*'];
        $this->binds        = [];
        $this->limitStart   = 0;
        $this->limitEnd     = 0;
        $this->where        = [];
        $this->groupFilters = [];
        $this->filters      = [];
        $this->strictMode   = true;

        return $this;
    }

    /**
    /**
     * Build WHERE clause based on current filters and bindings.
     *
     * @return string Returns the generated WHERE SQL string.
     */
    protected function getWhere(): string
    {
        $merging      = $this->mergeFilters();
        $where        = $this->splitGroupsFilters($merging);
        $glue         = $this->strictMode ? ' AND ' : ' OR ';
        $whereCustom  = implode($glue, $this->where);

        if ($where !== '' && $whereCustom !== '') {
            $whereString = $this->strictMode ? "AND $whereCustom" : "OR $whereCustom";

            return "WHERE $where $whereString";
        } elseif ($where === '' && $whereCustom !== '') {
            $whereString = $whereCustom;

            return "WHERE $whereString";
        } elseif ($where !== '') {
            return "WHERE $where";
        }

        // return condition where statement
        return $where;
    }

    /**
     * Merge main filters and group filters into a single array.
     *
     * @return array<int, array<string, array<string, array<string, string>>>> Returns merged filters.
     */
    protected function mergeFilters(): array
    {
        $newGroupFilters = $this->groupFilters;
        if (!empty($this->filters)) {
            // merge group filter and main filter (condition)
            $newGroupFilters[] = [
                'filters' => $this->filters,
                'strict'  => $this->strictMode,
            ];
        }

        return $newGroupFilters;
    }

    /**
     * Split group filters into a SQL string.
     *
     * @param array<int, array<string, array<string, array<string, string>>>> $groupFilters Filter groups.
     * @return string Returns the SQL string for the groups.
     */
    protected function splitGroupsFilters(array $groupFilters): string
    {
        $whereStatement = [];
        foreach ($groupFilters as $filters) {
            $single           = $this->splitFilters($filters);
            $whereStatement[] = "( $single )";
        }

        return implode(' AND ', $whereStatement);
    }

    /**
     * Split individual filters into SQL conditions.
     *
     * @param array<string, array<string, array<string, string>>> $filters Single filter group.
     * @return string Returns the SQL condition string.
     */
    protected function splitFilters(array $filters): string
    {
        $query     = [];
        $tableName = null === $this->subQuery ? $this->table : $this->subQuery->getAlias();
        foreach ($filters['filters'] as $fieldName => $fieldValue) {
            $value      = $fieldValue['value'];
            $comparison = $fieldValue['comparison'];
            $column     = str_contains($fieldName, '.') ? $fieldName : "{$tableName}.{$fieldName}";
            $bind       = $fieldValue['bind'];

            if ($value !== '') {
                $query[] = "({$column} {$comparison} :{$bind})";
            }
        }

        $clearQuery = array_filter($query);

        return $filters['strict']
            ? implode(' AND ', $clearQuery)
            : implode(' OR ', $clearQuery);
    }

    /**
     * Bind values into query placeholders and return a full SQL string.
     *
     * @return string Returns the final SQL query with bound values.
     */
    public function queryBind(): string
    {
        [$binds, $values] = $this->bindsDestructor();

        $quoteValues = array_map(function ($value) {
            if (is_string($value)) {
                return "'" . $value . "'";
            }

            if (is_bool($value)) {
                if ($value === true) {
                    return 'true';
                }

                return 'false';
            }

            /* @phpstan-ignore-next-line */
            return $value;
        }, $values);

        return str_replace($binds, $quoteValues, $this->builder());
    }

    /**
     * Build the SQL query.
     *
     * @return string Returns the SQL query string (empty by default in abstract class).
     */
    protected function builder(): string
    {
        return '';
    }

    /**
     * Extract bind names, values, and columns from the bind array.
     *
     * @return array{0: array<int, string>, 1: array<int, mixed>, 2: array<int, string>}
     *         Returns [bind names, values, columns].
     */
    public function bindsDestructor(): array
    {
        $bindName = [];
        $value    = [];
        $columns  = [];

        foreach ($this->binds as $bind) {
            $bindName[] = $bind->getBind();
            $value[]    = $bind->getValue();
            if (!in_array($bind->getColumnName(), $columns)) {
                $columns[] = $bind->getColumnName();
            }
        }

        return [$bindName, $value, $columns];
    }

    /**
     * Retrieve the current array of Bind objects.
     *
     * @return Bind[] Returns all bind objects used in the query.
     */
    public function getBinds(): array
    {
        return $this->binds;
    }

    /**
     * Apply a Where reference object to this query.
     *
     * @param Where|null $ref Reference object containing conditions and binds.
     * @return static Returns the current instance for chaining.
     */
    public function whereRef(?Where $ref): static
    {
        if ($ref->isEmpty()) {
            return $this;
        }
        $condition = $ref->get();
        foreach ($condition['binds'] as $bind) {
            $this->binds[] = $bind;
        }
        foreach ($condition['where'] as $where) {
            $this->where[] = $where;
        }
        foreach ($condition['filters'] as $name => $filter) {
            $this->filters[$name] = $filter;
        }
        $this->strictMode = $condition['isStrict'];

        return $this;
    }
}
