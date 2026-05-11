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

/**
 * Defines a contract for objects that are aware of an event dispatcher.
 *
 * Implementing classes can receive and store a Dispatcher instance in order
 * to dispatch or interact with events during their lifecycle.
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
interface DispatcherAwareInterface
{
    /**
     * Injects the event dispatcher instance into the implementing object.
     *
     * This allows the object to dispatch events or interact with the event
     * system without directly managing dispatcher creation.
     *
     * @param DispatcherInterface $dispatcher The event dispatcher instance to inject.
     * @return DispatcherAwareInterface Returns the current instance for method chaining.
     */
    public function setDispatcher(DispatcherInterface $dispatcher): DispatcherAwareInterface;
}
