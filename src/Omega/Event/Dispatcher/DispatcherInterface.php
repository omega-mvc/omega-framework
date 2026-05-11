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

use Omega\Event\EventInterface;
use Omega\Event\SubscriberInterface;

/**
 * Defines the contract for an event dispatcher system.
 *
 * A dispatcher is responsible for:
 * - Registering event listeners and subscribers
 * - Dispatching events to all relevant listeners
 * - Managing listener priorities and execution order
 * - Allowing inspection and manipulation of registered listeners
 *
 * Listeners are executed in priority order, from highest to lowest priority.
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
interface DispatcherInterface
{
    /**
     * Dispatches an event to all registered listeners.
     *
     * The dispatcher iterates through all listeners registered for the event's
     * name and executes them in priority order.
     *
     * Listeners may stop propagation, preventing further execution.
     *
     * @param EventInterface $event The event instance to dispatch.
     * @return EventInterface The same event instance after being processed by listeners.
     */
    public function dispatch(EventInterface $event): EventInterface;

    /**
     * Registers a listener for a specific event.
     *
     * Listeners are executed when the corresponding event is dispatched.
     * Execution order is determined by priority (higher values run first).
     *
     * @param string $eventName The name of the event to listen to.
     * @param callable $callback The listener callback to execute when the event is dispatched.
     * @param int $priority Execution priority of the listener (higher = earlier execution).
     * @return bool True if the listener was successfully registered.
     */
    public function addListener(string $eventName, callable $callback, int $priority = 0): bool;

    /**
     * Removes all listeners or listeners for a specific event.
     *
     * If an event name is provided, only listeners for that event are removed.
     * Otherwise, all registered listeners are cleared.
     *
     * @param string|null $event The event name, or null to clear all listeners.
     * @return static Returns the dispatcher instance for chaining.
     */
    public function clearListeners(?string $event = null): static;

    /**
     * Returns the number of registered listeners for a given event.
     *
     * @param string $event The event name.
     * @return int Number of listeners registered for the event.
     */
    public function countListeners(string $event): int;

    /**
     * Retrieves all registered listeners.
     *
     * If an event name is provided, only listeners for that event are returned.
     * Otherwise, all listeners for all events are returned.
     *
     * Listeners are returned in execution order (priority-sorted).
     *
     * @param string|null $event The event name or null for all events.
     * @return callable[] A list of listeners grouped or filtered by event name.
     */
    public function getListeners(?string $event = null): array;

    /**
     * Checks whether a listener is registered.
     *
     * If an event name is provided, the check is limited to that event only.
     * Otherwise, all events are searched.
     *
     * @param callable $callback The listener callback to check.
     * @param string|null $eventName The event name to restrict the search, or null for global search.
     * @return bool True if the listener is registered, false otherwise.
     */
    public function hasListener(callable $callback, ?string $eventName = null): bool;

    /**
     * Removes a specific listener from an event.
     *
     * If the listener is not registered, this operation has no effect.
     *
     * @param string $eventName The name of the event.
     * @param callable $listener The listener to remove.
     * @return void
     */
    public function removeListener(string $eventName, callable $listener): void;

    /**
     * Registers an event subscriber.
     *
     * A subscriber is a class that declares multiple event listeners at once.
     *
     * @param SubscriberInterface $subscriber The subscriber instance.
     * @return void
     */
    public function addSubscriber(SubscriberInterface $subscriber): void;

    /**
     * Unregisters an event subscriber.
     *
     * Removes all listeners associated with the given subscriber.
     *
     * @param SubscriberInterface $subscriber The subscriber instance.
     * @return void
     */
    public function removeSubscriber(SubscriberInterface $subscriber): void;
}
