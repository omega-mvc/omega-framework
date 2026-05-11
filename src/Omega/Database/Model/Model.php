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

namespace Omega\Database\Model;

use ArrayAccess;
use ArrayIterator;
use Exception;
use IteratorAggregate;
use Omega\Database\Connectioninterface;
use Omega\Database\Query\AbstractQuery;
use Omega\Database\Query\Bind;
use Omega\Database\Query\Join\InnerJoin;
use Omega\Database\Query\Query;
use Omega\Database\Query\Select;
use Omega\Database\Query\Where;
use ReturnTypeWillChange;
use Traversable;

use function array_filter;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function class_exists;
use function in_array;
use function is_a;
use function key_exists;
use function max;
use function method_exists;
use function sprintf;

use const ARRAY_FILTER_USE_KEY;

/**
 * Class Model
 *
 * A base ORM model representing a database table row(s).
 * Provides CRUD operations, change tracking, and relational access.
 * Supports ArrayAccess and iteration over the first record's columns.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Model
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @implements ArrayAccess<array-key, mixed>
 * @implements IteratorAggregate<array-key, mixed>
 */
class Model implements ArrayAccess, IteratorAggregate
{
    /** @var ConnectionInterface PDO connection instance for executing queries */
    protected ConnectionInterface $pdo;

    /** @var string Table name associated with this model */
    protected string $tableName;

    /** @var string Primary key column name (default: 'id') */
    protected string $primaryKey = 'id';

    /** @var array<array<array-key, mixed>> Current column values */
    protected array $columns;

    /** @var string[] Columns hidden from output */
    protected array $stash = [];

    /** @var string[] Columns that cannot be modified */
    protected array $resistant = [];

    /** @var array<array<array-key, mixed>> Original data fetched from database */
    protected array $fresh;

    /** @var Where|null Custom where condition instance */
    protected ?Where $where = null;

    /** @var Bind[] Array of binders for prepared statements */
    protected array $binds = [];

    /** @var int Start limit for query results */
    protected int $limitStart = 0;

    /** @var int End limit for query results */
    protected int $limitEnd = 0;

    /** @var int Offset for query results */
    protected int $offset = 0;

    /** @var array<string, string> Sorting order, key => column, value => ASC|DESC */
    protected array $sortOrder  = [];

    /**
     * Model class constructor.
     *
     * Initializes the model with a PDO connection and column data.
     * Sets the table name automatically to the lowercase class name if not already defined.
     * Also initializes the `Where` instance for query conditions.
     *
     * @param ConnectionInterface          $pdo    PDO connection interface for database operations.
     * @param array<array-key, mixed>      $column Initial column data for the model.
     * @return void
     */
    public function __construct(ConnectionInterface $pdo, array $column)
    {
        $this->pdo        = $pdo;
        $this->columns    = $this->fresh = $column;
        $this->tableName ??= strtolower(__CLASS__);
        $this->where = new Where($this->tableName);
    }

    /**
     * Debug information for var_dump and print_r.
     *
     * Excludes columns listed in `$stash` from output.
     *
     * @return array<array<array-key, mixed>> Filtered columns without stash.
     */
    public function __debugInfo()
    {
        return $this->getColumns();
    }

    /**
     * Set up model properties manually.
     *
     * Can be used to override table, columns, PDO instance, where, primary key,
     * stash, and resistant columns after object creation.
     *
     * @param string                        $table       Table name.
     * @param array<array<array-key, mixed>> $column     Column data.
     * @param ConnectionInterface           $pdo         PDO connection instance.
     * @param Where                         $where       Custom Where instance.
     * @param string                        $primaryKey Primary key column name.
     * @param string[]                      $stash      Columns to hide from output.
     * @param string[]                      $resistant  Columns that cannot be modified.
     * @return static
     */
    public function setUp(
        string $table,
        array $column,
        ConnectionInterface $pdo,
        Where $where,
        string $primaryKey,
        array $stash,
        array $resistant,
    ): self {
        $this->tableName  = $table;
        $this->columns    = $this->fresh = $column;
        $this->pdo        = $pdo;
        $this->where      = $where;
        $this->primaryKey = $primaryKey;
        $this->stash      = $stash;
        $this->resistant  = $resistant;

        return $this;
    }

    /**
     * Magic getter for dynamic property access.
     *
     * If the property corresponds to a method that returns a Model or ModelCollection,
     * it automatically returns the first record or an array of arrays.
     * Otherwise, fetches the value from the current column using `getter`.
     *
     * @param string $name Property name to retrieve.
     * @return mixed Value of the property.
     * @throws Exception If accessing a protected column in stash or other getter error.
     */
    public function __get(string $name)
    {
        if (method_exists($this, $name)) {
            $highOrder = $this->{$name}();
            if (is_a($highOrder, Model::class)) {
                return $highOrder->first();
            }

            if (is_a($highOrder, ModelCollection::class)) {
                return $highOrder->toArrayArray();
            }
        }

        return $this->getter($name);
    }

    /**
     * Magic setter for dynamic property access.
     *
     * Delegates to `setter` method for updating the current column.
     *
     * @param string $name  Property name to set.
     * @param mixed  $value Value to assign.
     * @return void
     * @throws Exception If trying to set a resistant column or other setter error.
     */
    public function __set(string $name, mixed $value): void
    {
        $this->setter($name, $value);
    }

    /**
     * Magic isset for dynamic property checks.
     *
     * Delegates to `has` method to verify if the first record contains the given key.
     *
     * @param string $name Property name to check.
     * @return bool True if key exists, false otherwise.
     * @throws Exception If first record is empty.
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * Check if the first column contains a given key.
     *
     * @param string $name Column key to check.
     * @return bool True if key exists in first column, false otherwise.
     * @throws Exception If no records exist.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->first());
    }

    /**
     * Set the value of a column in the first record.
     *
     * Ignores columns listed in `$resistant`.
     *
     * @param string $key   Column name.
     * @param mixed  $value Value to assign.
     * @return static
     * @throws Exception If first column cannot be determined.
     */
    public function setter(string $key, mixed $value): self
    {
        $this->firstColumn($current);
        if (key_exists($key, $this->columns[$current]) && !in_array($key, $this->resistant)) {
            $this->columns[$current][$key] = $value;

            return $this;
        }

        return $this;
    }

    /**
     * Get the value of a column in the first record.
     *
     * Throws exception if the column is hidden in `$stash`.
     *
     * @param string $key     Column name.
     * @param mixed  $default Default value if key does not exist.
     * @return mixed Value of the column or default.
     * @throws Exception If column is in stash.
     */
    public function getter(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->stash)) {
            throw new Exception("Can't read this column `$key`.");
        }

        return $this->first()[$key] ?? $default;
    }

    /**
     * Get the value of the primary key from the first record.
     *
     * @return mixed Value of the primary key.
     * @throws Exception If first record does not exist or primary key is missing.
     */
    public function getPrimaryKey(): mixed
    {
        $first = $this->first();
        if (false === array_key_exists($this->primaryKey, $first)) {
            throw new Exception(
                'This ' . __CLASS__ . ' model does not contain a correct record, please check your query.'
            );
        }

        return $first[$this->primaryKey];
    }

    /**
     * Get a custom Where instance for building queries.
     *
     * Allows overwriting the existing where condition for the model.
     *
     * @return Where A new Where instance scoped to the current table.
     */
    public function identifier(): Where
    {
        return $this->where = new Where($this->tableName);
    }

    /**
     * Get the first column of the model, excluding columns in stash.
     *
     * @param int|string|null $key Optional reference to the key of the first column.
     * @return array<array-key, mixed> The first column data.
     * @throws Exception If there are no columns available.
     */
    public function first(int|string|null &$key = null): array
    {
        $columns = $this->getColumns();
        if (null === ($key = array_key_first($columns))) {
            throw new Exception('Empty columns, try to assign using read.');
        }

        return $columns[$key];
    }

    /**
     * Convert current model columns into a ModelCollection.
     *
     * Each column becomes a separate Model instance within the collection.
     *
     * @return ModelCollection<array-key, static> Collection of model instances.
     */
    public function get(): ModelCollection
    {
        /** @var ModelCollection<array-key, static> $collection */
        $collection = new ModelCollection([], $this);

        foreach ($this->columns as $column) {
            $where = new Where($this->tableName);
            if (array_key_exists($this->primaryKey, $column)) {
                $where->equal($this->primaryKey, $column[$this->primaryKey]);
            }

            $collection->push(new static($this->pdo, [])->setUp(
                $this->tableName,
                [$column],
                $this->pdo,
                $where,
                $this->primaryKey,
                $this->stash,
                $this->resistant
            ));
        }

        return $collection;
    }

    /**
     * Insert all columns into the database.
     *
     * @return bool True if all inserts succeeded, false if any insert failed.
     */
    public function insert(): bool
    {
        $insert = Query::from($this->tableName, $this->pdo);
        foreach ($this->columns as $column) {
            $success = $insert->insert()
                ->values($column)
                ->execute();

            if (!$success) {
                return false;
            }
        }

        return true;
    }

    /**
     * Read records from the database using current where condition.
     *
     * Populates `$columns` and `$fresh` with the fetched data.
     *
     * @return bool True if any records were found, false otherwise.
     */
    public function read(): bool
    {
        $query = new Select($this->tableName, ['*'], $this->pdo);
        $query->sortOrderRef($this->limitStart, $this->limitEnd, $this->offset, $this->sortOrder);
        $all = $this->fetch($query);

        if ([] === $all) {
            return false;
        }

        $this->columns = $this->fresh = $all;

        return true;
    }

    /**
     * Update modified columns in the database.
     *
     * Only updates columns that have changed since the last fetch.
     *
     * @return bool True if the update affected any rows, false otherwise.
     * @throws Exception If there is a problem computing changes.
     */
    public function update(): bool
    {
        if ($this->isClean()) {
            return false;
        }

        $update = Query::from($this->tableName, $this->pdo)
            ->update()
            ->values($this->changes());

        return $this->changing($this->execute($update));
    }

    /**
     * Delete records from the database based on current where condition.
     *
     * @return bool True if any rows were deleted, false otherwise.
     */
    public function delete(): bool
    {
        $delete = Query::from($this->tableName, $this->pdo)->delete();

        return $this->changing($this->execute($delete));
    }

    /**
     * Check whether any record exists for the current where condition.
     *
     * @return bool True if a record exists, false otherwise.
     */
    public function isExist(): bool
    {
        $query = new Select($this->tableName, [$this->primaryKey], $this->pdo);
        $query->whereRef($this->where);

        return $this->execute($query);
    }

    /**
     * Internal relation handler used by hasOne and hasMany.
     *
     * @param string      $model    The related model class or table name.
     * @param string|null $ref      Optional column name for the join reference.
     * @param bool        $multiple True for multiple results (hasMany), false for single result (hasOne).
     * @return Model|ModelCollection The related model instance(s).
     */
    protected function buildRelation(string $model, ?string $ref, bool $multiple): Model|ModelCollection
    {
        if (class_exists($model)) {
            $model     = new $model($this->pdo, []);
            $tableName = $model->tableName;
            $joinRef   = $ref ?? $model->primaryKey;
        } else {
            $tableName = $model;
            $joinRef   = $ref ?? $this->primaryKey;
            $model     = new static($this->pdo, []);
        }

        $query = Query::from($this->tableName, $this->pdo)
            ->select([$tableName . '.*'])
            ->join(InnerJoin::ref($tableName, $this->primaryKey, $joinRef))
            ->whereRef($this->where);

        if ($multiple) {
            $result = $query->get();
            $model->columns = $model->fresh = $result->toArray();

            return $model->get();
        }

        $result = $query->single();
        $model->columns = $model->fresh = [$result];

        return $model;
    }

    /**
     * Get a single related model (hasOne relation).
     *
     * @param string      $model Related model class or table name.
     * @param string|null $ref   Optional join column.
     * @return Model Single related model instance.
     */
    public function hasOne(string $model, ?string $ref = null): Model
    {
        return $this->buildRelation($model, $ref, false);
    }

    /**
     * Get multiple related models (hasMany relation).
     *
     * @param string      $model Related model class or table name.
     * @param string|null $ref   Optional join column reference.
     * @return ModelCollection<array-key, Model> Collection of related models.
     */
    public function hasMany(string $model, ?string $ref = null): ModelCollection
    {
        return $this->buildRelation($model, $ref, true);
    }

    /**
     * Check if current columns or a specific column have been modified.
     *
     * @param string|null $column Optional column name to check. If null, checks all columns.
     * @return bool True if the column(s) are unchanged, false if modified.
     * @throws Exception If the specified column does not exist in the table.
     */
    public function isClean(?string $column = null): bool
    {
        if ($column === null) {
            return $this->columns === $this->fresh;
        }

        if (false === (array_keys($this->columns) === array_keys($this->fresh))) {
            return false;
        }

        foreach (array_keys($this->columns) as $key) {
            if (
                !array_key_exists($column, $this->columns[$key])
                || !array_key_exists($column, $this->fresh[$key])
            ) {
                throw new Exception(
                    sprintf(
                        "Column {%s} is not in table `{%s}`.",
                        $column,
                        $this->tableName
                    )
                );
            }

            if (false === ($this->columns[$key][$column] === $this->fresh[$key][$column])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if current columns or a specific column have been modified.
     *
     * @param string|null $column Optional column name to check. If null, checks all columns.
     * @return bool True if the column(s) have been modified, false if unchanged.
     * @throws Exception If the specified column does not exist in the table.
     */
    public function isDirty(?string $column = null): bool
    {
        return !$this->isClean($column);
    }

    /**
     * Get the differences between current column values and the original fresh values.
     *
     * @return array<array-key, mixed> Key-value pairs of modified columns.
     * @throws Exception If there is an issue accessing the first column.
     */
    public function changes(): array
    {
        $change = [];
        $column = $this->firstColumn($current);

        if (false === array_key_exists($current, $this->fresh)) {
            return $column;
        }

        foreach ($column as $key => $value) {
            if (
                array_key_exists($key, $this->fresh[$current])
                && $this->fresh[$current][$key] !== $value
            ) {
                $change[$key] = $value;
            }
        }

        return $change;
    }

    /**
     * Convert the current model data into an array.
     *
     * Excludes columns defined in the stash.
     *
     * @return array<array<array-key, mixed>> Array representation of model columns.
     */
    public function toArray(): array
    {
        return $this->getColumns();
    }

    /**
     * Set both limit start and limit end for fetching records.
     *
     * @param int $limitStart Starting index for records.
     * @param int $limitEnd   Maximum number of records to fetch.
     * @return static Fluent instance of the model.
     */
    public function limit(int $limitStart, int $limitEnd): self
    {
        $this->limitStart($limitStart);
        $this->limitEnd($limitEnd);

        return $this;
    }

    /**
     * Set starting offset for fetching records.
     *
     * @param int $value Limit start, default is 0.
     * @return static Fluent instance of the model.
     */
    public function limitStart(int $value): self
    {
        $this->limitStart = max($value, 0);

        return $this;
    }

    /**
     * Set ending limit for fetching records.
     *
     * @param int $value Limit end, zero means no records fetched.
     * @return static Fluent instance of the model.
     */
    public function limitEnd(int $value): self
    {
        $this->limitEnd = max($value, 0);

        return $this;
    }

    /**
     * Set the offset for fetching records.
     *
     * @param int $value Offset value.
     * @return static Fluent instance of the model.
     */
    public function offset(int $value): self
    {
        $this->offset = max($value, 0);

        return $this;
    }

    /**
     * Set limit and offset for fetching records simultaneously.
     *
     * @param int $limit  Number of records to fetch.
     * @param int $offset Offset from the start of the result set.
     * @return static Fluent instance of the model.
     */
    public function limitOffset(int $limit, int $offset): self
    {
        return $this
            ->limitStart($limit)
            ->limitEnd(0)
            ->offset($offset);
    }

    /**
     * Set the sort order for a specific column.
     *
     * Column must exist in the model. Supports ASC/DESC order.
     *
     * @param string      $columnName Column to sort by.
     * @param int         $orderUsing Order direction, use Query::ORDER_ASC or Query::ORDER_DESC.
     * @param string|null $belongTo   Optional table name for column, defaults to current table.
     * @return $this Fluent instance of the model.
     */
    public function order(string $columnName, int $orderUsing = Query::ORDER_ASC, ?string $belongTo = null): self
    {
        $order = 0 === $orderUsing ? 'ASC' : 'DESC';
        $belongTo ??= $this->tableName;
        $res = sprintf('%s.%s', $belongTo, $columnName);

        $this->sortOrder[$res] = $order;

        return $this;
    }

    /**
     * Check if a given offset exists in the model (ArrayAccess interface).
     *
     * @param array-key $offset Column name to check.
     * @return bool True if the column exists.
     * @throws Exception If column access fails.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Retrieve the value at a given offset (ArrayAccess interface).
     *
     * @param array-key $offset Column name to fetch.
     * @return mixed|null Value of the column or null if not set.
     * @throws Exception If the column cannot be accessed.
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getter($offset);
    }

    /**
     * Set a value at a given offset (ArrayAccess interface).
     *
     * @param mixed $offset Column name to set.
     * @param mixed $value  Value to assign.
     * @return void
     * @throws Exception If the column cannot be modified.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setter($offset, $value);
    }

    /**
     * Unset a value at a given offset (ArrayAccess interface).
     *
     * @param mixed $offset Column name to unset.
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        // intentionally left blank; unsetting not supported
    }

    /**
     * Retrieve an iterator for traversing the first record's columns.
     *
     * @return Traversable<array-key, mixed> Iterator for the first column.
     * @throws Exception If no records exist.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->first());
    }

    /**
     * Find a model record by its primary key.
     *
     * @param int|string          $id  Primary key value.
     * @param ConnectionInterface $pdo PDO connection instance.
     * @return Model The found model instance.
     */
    public static function find(int|string $id, ConnectionInterface $pdo): static
    {
        $model          = new static($pdo, []);
        $model->where   = new Where($model->tableName)
            ->equal($model->primaryKey, $id);

        $model->read();

        return $model;
    }

    /**
     * Find a model by primary key or create a new record if it does not exist.
     *
     * @param mixed                   $id     Primary key value.
     * @param array<array-key, mixed> $column Initial column values if creating.
     * @param ConnectionInterface     $pdo    PDO connection instance.
     * @return static Found or newly created model instance.
     * @throws Exception If insertion fails.
     */
    public static function findOrCreate(mixed $id, array $column, ConnectionInterface $pdo): static
    {
        $model          = new static($pdo, [$column]);
        $model->where   = new Where($model->tableName)
            ->equal($model->primaryKey, $id);

        if ($model->isExist()) {
            $model->read();
            return $model;
        }

        if ($model->insert()) {
            return $model;
        }

        throw new Exception('Cannot insert data.');
    }

    /**
     * Find a model using a custom WHERE clause.
     *
     * @param string              $whereCondition SQL WHERE condition as a string.
     * @param array<string|int>   $binder         Values to bind to placeholders.
     * @param ConnectionInterface $pdo            PDO connection instance.
     * @return static Model instance matching the condition.
     */
    public static function where(string $whereCondition, array $binder, ConnectionInterface $pdo): static
    {
        $model = new static($pdo, []);
        $map   = [];
        foreach ($binder as $bind => $value) {
            $map[] = [$bind, $value];
        }

        $model->where = new Where($model->tableName)
            ->where($whereCondition, $map);
        $model->read();

        return $model;
    }

    /**
     * Find a model where a column equals a specific value.
     *
     * @param array-key           $columnName Column name to filter by.
     * @param mixed               $value      Value to match.
     * @param ConnectionInterface $pdo        PDO connection instance.
     * @return static Model instance matching the equality condition.
     */
    public static function equal(int|string $columnName, mixed $value, ConnectionInterface $pdo): static
    {
        $model = new static($pdo, []);
        $model->identifier()->equal($columnName, $value);
        $model->read();

        return $model;
    }

    /**
     * Fetch all records from the table and return as a collection.
     *
     * @param ConnectionInterface $pdo PDO connection instance.
     * @return ModelCollection<array-key, static> Collection of all model instances.
     */
    public static function all(ConnectionInterface $pdo): ModelCollection
    {
        $model = new static($pdo, []);
        $model->read();

        return $model->get();
    }

    /**
     * Get all columns excluding those in the stash.
     *
     * @return array<array<array-key, mixed>> Array of columns filtered by stash.
     */
    protected function getColumns(): array
    {
        return array_map(
            fn ($column) => array_filter(
                $column,
                fn ($k) => !in_array($k, $this->stash, true),
                ARRAY_FILTER_USE_KEY
            ),
            $this->columns
        );
    }

    /**
     * Get the first column from the model.
     *
     * @param int|string|null $key By-reference key of the first column.
     * @return array<array-key, mixed> First column's data.
     * @throws Exception If there are no columns loaded.
     */
    protected function firstColumn(int|string|null &$key = null): array
    {
        if (null === ($key = array_key_first($this->columns))) {
            throw new Exception('Empty columns, try to assign using read.');
        }

        return $this->columns[$key];
    }

    /**
     * Update fresh column snapshot if changes were applied.
     *
     * @param bool $change Whether the changes were successfully applied.
     * @return bool Returns the same value of $change.
     */
    private function changing(bool $change): bool
    {
        if ($change) {
            $this->fresh = $this->columns;
        }

        return $change;
    }

    /**
     * Build query and bind parameters for execution.
     *
     * @param AbstractQuery $query The query object to extract SQL and binds from.
     * @return array{0: string, 1: Bind[]} Array containing SQL string and list of bind objects.
     */
    private function builder(AbstractQuery $query): array
    {
        return [
            (fn () => $this->{'builder'}())->call($query),
            (fn () => $this->{'binds'})->call($query),
        ];
    }

    /**
     * Fetch the result set from a query.
     *
     * @param AbstractQuery $baseQuery Query object to execute.
     * @return array|false Array of results, or false if no results found.
     */
    private function fetch(AbstractQuery $baseQuery): array|false
    {
        $baseQuery->whereRef($this->where);

        [$query, $binds] = $this->builder($baseQuery);

        $this->pdo->query($query);
        foreach ($binds as $bind) {
            if (!$bind->hasBind()) {
                $this->pdo->bind($bind->getBind(), $bind->getValue());
            }
        }

        return $this->pdo->resultset();
    }

    /**
     * Execute a query with the current WHERE conditions applied.
     *
     * @param AbstractQuery $baseQuery Query object to execute.
     * @return bool True if the execution affected at least one row, false otherwise.
     */
    private function execute(AbstractQuery $baseQuery): bool
    {
        $baseQuery->whereRef($this->where);

        [$query, $binds] = $this->builder($baseQuery);

        if ($query != null) {
            $this->pdo->query($query);
            foreach ($binds as $bind) {
                if (!$bind->hasBind()) {
                    $this->pdo->bind($bind->getBind(), $bind->getValue());
                }
            }

            $this->pdo->execute();

            return $this->pdo->rowCount() > 0;
        }

        return false;
    }
}
