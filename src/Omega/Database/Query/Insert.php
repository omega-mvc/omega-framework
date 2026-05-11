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

use function array_chunk;
use function array_filter;
use function count;
use function implode;

/**
 * Builds and executes an INSERT SQL query.
 *
 * This class supports single-row and multi-row inserts, value binding,
 * and optional ON DUPLICATE KEY UPDATE clauses. Query execution is handled
 * by AbstractExecute.
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
class Insert extends AbstractExecute
{
    /**
     * Columns and values used for ON DUPLICATE KEY UPDATE.
     *
     * The array key represents the column name, while the value represents
     * the SQL expression assigned to that column.
     *
     * @var array<string, string>|null
     */
    private ?array $duplicateKey = null;

    /**
     * Create a new INSERT query builder.
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
     * @return string The compiled INSERT SQL query.
     */
    public function __toString(): string
    {
        return $this->builder();
    }

    /**
     * Add multiple column-value pairs for insertion.
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
     * Add a single column-value pair for insertion.
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
     * Add multiple rows for a multi-row INSERT statement.
     *
     * Each row is indexed to ensure unique bind placeholders.
     *
     * @param array<int, array<string, string|int|bool|null>> $rows Rows to insert.
     * @return $this
     */
    public function rows(array $rows): self
    {
        foreach ($rows as $index => $values) {
            foreach ($values as $bind => $value) {
                $this->binds[] = Bind::set($bind, $value, $bind)
                    ->prefixBind(':bind_' . $index . '_');
            }
        }

        return $this;
    }

    /**
     * Define a column update for the ON DUPLICATE KEY UPDATE clause.
     *
     * If no value is provided, the column will be updated using
     * VALUES(column).
     *
     * @param string      $column Column name.
     * @param string|null $value  SQL expression or null for default behavior.
     * @return $this
     */
    public function on(string $column, ?string $value = null): self
    {
        $this->duplicateKey[$column] = $value ?? "VALUES({$column})";

        return $this;
    }

    /**
     * Compile the INSERT SQL query.
     *
     * @return string The generated SQL statement.
     */
    protected function builder(): string
    {
        [$binds, , $columns] = $this->bindsDestructor();

        $stringsBinds = [];
        /** @var array<int, array<int, string>> $chunk */
        $chunk = array_chunk($binds, count($columns), true);

        foreach ($chunk as $group) {
            $stringsBinds[] = '(' . implode(', ', $group) . ')';
        }

        $builds = [];
        $builds['column']    = '(' . implode(', ', $columns) . ')';
        $builds['values']    = 'VALUES';
        $builds['binds']     = implode(', ', $stringsBinds);
        $builds['keyUpdate'] = $this->getDuplicateKeyUpdate();

        $stringBuild = implode(
            ' ',
            array_filter($builds, fn ($item) => $item !== '')
        );

        $this->query = "INSERT INTO {$this->table} {$stringBuild}";

        return $this->query;
    }

    /**
     * Build the ON DUPLICATE KEY UPDATE clause.
     *
     * @return string The compiled clause or an empty string if not defined.
     */
    private function getDuplicateKeyUpdate(): string
    {
        if (null === $this->duplicateKey) {
            return '';
        }

        $keys = [];
        foreach ($this->duplicateKey as $key => $value) {
            $keys[] = "{$key} = {$value}";
        }

        return 'ON DUPLICATE KEY UPDATE ' . implode(', ', $keys);
    }
}
