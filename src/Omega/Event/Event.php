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

use Omega\Event\Exceptions\InvalidEventArgumentNameException;
use ReturnTypeWillChange;

/**
 * Mutable event implementation.
 *
 * Extends AbstractEvent by adding convenience methods for managing event
 * arguments at runtime. Unlike EventImmutable, this class allows full
 * modification of the event payload after creation, including adding,
 * updating, removing, and clearing arguments.
 *
 * @category  Omega
 * @package   Event
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version   2.0.0
 */
class Event extends AbstractEvent
{
    /**
     * Adds an argument only if it does not already exist.
     *
     * This method preserves existing values and will not overwrite them
     * if the argument name is already defined.
     *
     * @param string $name Argument name.
     * @param mixed $value Argument value.
     * @return static
     */
    public function addArgument(string $name, mixed $value): static
    {
        if (!isset($this->arguments[$name])) {
            $this->arguments[$name] = $value;
        }

        return $this;
    }

    /**
     * Sets or overrides an event argument.
     *
     * If the argument already exists, its value will be replaced.
     *
     * @param string $name Argument name.
     * @param mixed $value Argument value.
     * @return static
     */
    public function setArgument(string $name, mixed $value): static
    {
        $this->arguments[$name] = $value;

        return $this;
    }

    /**
     * Removes an argument from the event.
     *
     * If the argument exists, it is removed and its previous value is returned.
     * If it does not exist, null is returned.
     *
     * @param string $name Argument name.
     * @return mixed The removed value, or null if not found.
     */
    public function removeArgument(string $name): mixed
    {
        $return = null;

        if (isset($this->arguments[$name])) {
            $return = $this->arguments[$name];
            unset($this->arguments[$name]);
        }

        return $return;
    }

    /**
     * Removes all arguments from the event.
     *
     * Returns the previous argument set before clearing.
     *
     * @return array The previous arguments.
     */
    public function clearArguments(): array
    {
        $arguments = $this->arguments;
        $this->arguments = [];

        return $arguments;
    }

    /**
     * Sets an argument via ArrayAccess.
     *
     * Throws an exception if the offset is null, as argument names
     * must always be valid strings.
     *
     * @param mixed $offset Argument name.
     * @param mixed $value Argument value.
     * @return void
     * @throws InvalidEventArgumentNameException If the argument name is null.
     */
    #[ReturnTypeWillChange]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset == null) {
            throw new InvalidEventArgumentNameException(
                'The argument name cannot be null.'
            );
        }

        $this->setArgument($offset, $value);
    }

    /**
     * Removes an argument via ArrayAccess.
     *
     * @param mixed $offset Argument name.
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetUnset(mixed $offset): void
    {
        $this->removeArgument($offset);
    }
}
