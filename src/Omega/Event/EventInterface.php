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

/**
 * Defines the contract for an event instance in the system.
 *
 * An event represents a named message that can carry arbitrary data (arguments)
 * and can be dispatched through the event dispatcher system.
 *
 * Events support propagation control, allowing listeners to stop further execution.
 *
 * @category  Omega
 * @package   Event
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version   2.0.0
 */
interface EventInterface
{
    /**
     * Retrieves an argument value from the event.
     *
     * @param string $name The argument name.
     * @param mixed $default Default value returned if the argument does not exist.
     * @return mixed The argument value or the default value.
     */
    public function getArgument(string $name, mixed $default = null): mixed;

    /**
     * Returns the event name.
     *
     * The name uniquely identifies the event within the dispatcher system.
     *
     * @return string The event name.
     */
    public function getName(): string;

    /**
     * Determines whether event propagation has been stopped.
     *
     * If true, no further listeners should be executed.
     *
     * @return bool True if propagation is stopped, false otherwise.
     */
    public function isStopped(): bool;

    /**
     * Stops the propagation of the event.
     *
     * Once propagation is stopped, no further listeners will be executed
     * for this event instance.
     *
     * @return void
     */
    public function stopPropagation(): void;
}
