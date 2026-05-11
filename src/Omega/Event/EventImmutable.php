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

use Omega\Event\Exceptions\EventImmutableException;

use function sprintf;

/**
 * Represents an immutable event instance.
 *
 * Once created, an immutable event cannot be modified:
 * - Event arguments cannot be added, changed, or removed
 * - The event structure is locked at construction time
 *
 * This class is useful for guaranteeing event integrity in contexts
 * where event mutation must be prevented (e.g. security-sensitive flows,
 * audit events, or deterministic pipelines).
 *
 * @category  Omega
 * @package   Event
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version   2.0.0
 */
final class EventImmutable extends AbstractEvent
{
    /**
     * Indicates whether the event has already been constructed.
     *
     * This flag is used to prevent re-initialization or reconstruction
     * of the immutable event instance.
     *
     * @var bool
     */
    private bool $constructed = false;

    /**
     * Creates a new immutable event instance.
     *
     * The event name and arguments are set at construction time and cannot
     * be modified afterwards.
     *
     * @param string $name The event name.
     * @param array $arguments Initial event arguments.
     *
     * @throws EventImmutableException If an attempt is made to reconstruct or reinitialize the event.
     */
    public function __construct(string $name, array $arguments = [])
    {
        if ($this->constructed) {
            throw new EventImmutableException(
                sprintf('Cannot reconstruct the EventImmutable %s.', $this->name)
            );
        }

        $this->constructed = true;

        parent::__construct($name, $arguments);
    }

    /**
    /**
     * Prevents modification of event arguments.
     *
     * Immutable events do not allow runtime modification of their state.
     *
     * @param mixed $offset The argument name.
     * @param mixed $value The argument value.
     * @return void
     * @throws EventImmutableException Always thrown, as mutation is not allowed.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new EventImmutableException(
            sprintf(
                'Cannot set the argument %s of the immutable event %s.',
                $offset,
                $this->name
            )
        );
    }

    /**
     * Prevents removal of event arguments.
     *
     * Immutable events cannot be altered after construction.
     *
     * @param mixed $offset The argument name.
     * @return void
     * @throws EventImmutableException Always thrown, as mutation is not allowed.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new EventImmutableException(
            sprintf(
                'Cannot remove the argument %s of the immutable event %s.',
                $offset,
                $this->name
            )
        );
    }
}
