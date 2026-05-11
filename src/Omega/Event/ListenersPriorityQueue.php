<?php

/**
 * Part of Omega - Event Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Event;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use ReturnTypeWillChange;

use function array_search;
use function call_user_func_array;
use function count;
use function krsort;

/**
 * Priority-based listener queue.
 *
 * This class stores event listeners grouped by priority level and ensures
 * they are executed in descending priority order when retrieved.
 *
 * Internally, listeners are bucketed by priority (integer keys), allowing
 * efficient insertion and ordered retrieval without repeated sorting on insert.
 *
 * Higher priority values are executed before lower ones.
 *
 * @category  Omega
 * @package   Event
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version   2.0.0
 */
final class ListenersPriorityQueue implements IteratorAggregate, Countable
{
    /**
     * Internal storage of listeners grouped by priority level.
     *
     * The array structure is:
     * [
     *   priority => [callable, callable, ...],
     * ]
     *
     * Higher numeric priority values have precedence.
     */
    private array $listeners = [];

    /**
     * Adds a listener to the queue under the given priority.
     *
     * Multiple listeners can share the same priority level.
     * Listeners are appended in insertion order within the same priority bucket.
     *
     * @param callable $callback The event listener to register.
     * @param int $priority Execution priority (higher values run first).
     * @return ListenersPriorityQueue
     */
    public function add(callable $callback, int $priority): ListenersPriorityQueue
    {
        $this->listeners[$priority][] = $callback;

        return $this;
    }

    /**
     * Removes a listener from the queue if present.
     *
     * The listener is searched across all priority levels.
     *
     * @param callable $callback The listener to remove.
     * @return ListenersPriorityQueue
     */
    public function remove(callable $callback): ListenersPriorityQueue
    {
        foreach ($this->listeners as $priority => $listeners) {
            if (($key = array_search($callback, $listeners, true)) !== false) {
                unset($this->listeners[$priority][$key]);
            }
        }

        return $this;
    }

    /**
     * Checks whether a listener is registered in the queue.
     *
     * The search is performed across all priority levels.
     *
     * @param callable $callback The listener to check.
     * @return bool True if the listener exists, false otherwise.
     */
    public function has(callable $callback): bool
    {
        foreach ($this->listeners as $listeners) {
            if (array_search($callback, $listeners, true) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the priority assigned to a given listener.
     *
     * If the listener exists in multiple buckets (should not normally happen),
     * the first matching priority encountered is returned.
     *
     * @param callable $callback The listener to inspect.
     * @param mixed $default Value returned if the listener is not found.
     * @return mixed The priority level or the default value.
     */
    public function getPriority(callable $callback, mixed $default = null): mixed
    {
        foreach ($this->listeners as $priority => $listeners) {
            if (array_search($callback, $listeners, true) !== false) {
                return $priority;
            }
        }

        return $default;
    }

    /**
     * Returns all listeners sorted by priority (descending).
     *
     * Listeners are flattened into a single list ordered by:
     * 1. Priority (higher first)
     * 2. Insertion order within the same priority
     *
     * @return callable[] Ordered list of listeners.
     */
    public function getAll(): array
    {
        if (empty($this->listeners)) {
            return [];
        }

        krsort($this->listeners);

        return call_user_func_array('array_merge', $this->listeners);
    }

    /**
     * Returns an iterator over all listeners in execution order.
     *
     * @return ArrayIterator Iterator of ordered listeners.
     */
    #[ReturnTypeWillChange]
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getAll());
    }

    /**
     * Counts all registered listeners across all priority levels.
     *
     * @return int Total number of listeners in the queue.
     */
    #[ReturnTypeWillChange]
    public function count(): int
    {
        $count = 0;

        foreach ($this->listeners as $priority) {
            $count += count($priority);
        }

        return $count;
    }
}