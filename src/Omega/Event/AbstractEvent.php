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

use ArrayAccess;
use Countable;
use ReturnTypeWillChange;
use Serializable;

use function count;
use function serialize;
use function unserialize;

/**
 * Base implementation of an event object.
 *
 * This class represents a named event carrying an optional set of arguments,
 * supports propagation control (stop mechanism), array-like access to arguments,
 * counting of arguments, and legacy serialization support.
 *
 * It is designed to be extended for domain-specific events while providing
 * a consistent core behavior for event dispatching systems.
 *
 * @category  Omega
 * @package   Event
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version   2.0.0
 */
abstract class AbstractEvent implements EventInterface, ArrayAccess, Serializable, Countable
{
    /**
     * Indicates whether event propagation has been stopped.
     *
     * When set to true, no further listeners should be executed for this event.
     */
    protected bool $stopped = false;

    /**
     * Creates a new event instance.
     *
     * @param string $name The unique name of the event.
     * @param array $arguments Optional associative array of event arguments.
     */
    public function __construct(protected string $name, protected array $arguments = [])
    {
    }

    /**
     * Returns the event name.
     *
     * @return string The event identifier.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieves a specific event argument by name.
     *
     * @param string $name The argument key.
     * @param mixed $default Default value returned if the argument does not exist.
     * @return mixed The argument value or the provided default.
     */
    public function getArgument(string $name, mixed $default = null): mixed
    {
        if (isset($this->arguments[$name])) {
            return $this->arguments[$name];
        }

        return $default;
    }

    /**
     * Checks whether an argument exists in the event.
     *
     * @param string $name The argument key.
     * @return bool True if the argument exists, false otherwise.
     */
    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    /**
     * Returns all event arguments.
     *
     * @return array Associative array of all arguments.
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Indicates whether propagation has been stopped.
     *
     * @return bool True if propagation is stopped, false otherwise.
     */
    public function isStopped(): bool
    {
        return $this->stopped === true;
    }

    /**
     * Stops further propagation of the event to additional listeners.
     *
     * @return void
     */
    public function stopPropagation(): void
    {
        $this->stopped = true;
    }

    /**
     * Returns the number of event arguments.
     *
     * @return int Total number of arguments stored in the event.
     */
    #[ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->arguments);
    }

    /**
     * Serializes the event using legacy Serializable support.
     *
     * @return string Serialized representation of the event.
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * Prepares the event data for serialization.
     *
     * @return array Structured data representing the event state.
     */
    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'arguments' => $this->arguments,
            'stopped' => $this->stopped,
        ];
    }

    /**
     * Unserializes the event from a serialized string representation.
     *
     * @param string $data Serialized event data.
     * @return void
     */
    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    /**
     * Restores the event state from an array structure.
     *
     * @param array $data Event data including name, arguments, and stopped flag.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->name = $data['name'];
        $this->arguments = $data['arguments'];
        $this->stopped = $data['stopped'];
    }

    /**
     * Checks whether an argument exists using array access.
     *
     * @param mixed $offset Argument key.
     * @return bool True if the argument exists, false otherwise.
     */
    #[ReturnTypeWillChange]
    public function offsetExists(mixed $offset): bool
    {
        return $this->hasArgument($offset);
    }

    /**
     * Retrieves an argument using array access.
     *
     * @param mixed $offset Argument key.
     * @return mixed Argument value or null if not set.
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getArgument($offset);
    }
}
