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

namespace Omega\Event\Dispatcher;

use Omega\Event\Exceptions\DispatcherNotSetException;

/**
 * Provides event dispatcher awareness to a class.
 *
 * This trait allows any class to be injected with an instance of
 * DispatcherInterface, enabling it to dispatch and manage events
 * without directly handling dispatcher creation or resolution.
 *
 * If the dispatcher is not set, attempting to access it will result
 * in a DispatcherNotSetException being thrown.
 *
 * @category   Omega
 * @package    Event
 * @subpackage Dispatcher
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait DispatcherAwareTrait
{
    /**
     * The event dispatcher instance associated with this object.
     *
     * This property is optional until explicitly set via setDispatcher().
     * Attempting to access it before initialization will result in an exception.
     */
    private ?DispatcherInterface $dispatcher;

    /**
     * Retrieves the associated event dispatcher.
     *
     * Returns the dispatcher instance previously injected via setDispatcher().
     *
     * @return DispatcherInterface The active event dispatcher instance.
     * @throws DispatcherNotSetException If no dispatcher has been set on this object.
     */
    public function getDispatcher(): DispatcherInterface
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        throw new DispatcherNotSetException('Dispatcher not set in ' . __CLASS__);
    }

    /**
     * Injects the event dispatcher into the current object.
     *
     * This method allows the dispatcher to be provided externally,
     * typically by a service container or framework bootstrap process.
     *
     * @param DispatcherInterface $dispatcher The dispatcher instance to assign.
     * @return static Returns the current instance for fluent method chaining.
     */
    public function setDispatcher(DispatcherInterface $dispatcher): static
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }
}
