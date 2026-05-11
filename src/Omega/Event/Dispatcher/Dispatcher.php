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
use Omega\Event\ListenersPriorityQueue;
use Omega\Event\Priority;
use Omega\Event\SubscriberInterface;

use function count;
use function is_array;

/**
 * Central event dispatcher responsible for managing and executing event listeners.
 *
 * The Dispatcher acts as the core runtime component of the event system.
 * It allows registration of listeners and subscribers, and ensures execution
 * in priority order for each dispatched event.
 *
 * Listeners are stored per event name and executed in a deterministic order
 * based on their assigned priority.
 *
 * The dispatcher also supports:
 * - Subscriber-based registration (multiple listeners per class)
 * - Listener priority queues
 * - Event propagation stopping
 *
 * It does not manage event creation logic; it only orchestrates execution.
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
class Dispatcher implements DispatcherInterface
{
    /**
     * Internal registry of event listeners grouped by event name.
     *
     * Each event name maps to a priority queue that stores callable listeners
     * sorted by execution priority.
     *
     * @var array<string, ListenersPriorityQueue>
     */
    protected array $listeners = [];

    /**
     * {@inheritdoc}
     */
    public function addListener(string $eventName, callable $callback, int $priority = 0): bool
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = new ListenersPriorityQueue();
        }

        $this->listeners[$eventName]->add($callback, $priority);

        return true;
    }

    /**
     * Retrieves the priority of a registered listener for a specific event.
     *
     * If the listener is not registered, null is returned.
     *
     * @param string $eventName The event name.
     * @param callable $callback The listener callback.
     * @return int|null The priority of the listener or null if not found.
     */
    public function getListenerPriority(string $eventName, callable $callback): ?int
    {
        if (!isset($this->listeners[$eventName])) {
            return null;
        }

        return $this->listeners[$eventName]->getPriority($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners(?string $event = null): array
    {
        if ($event !== null) {
            if (isset($this->listeners[$event])) {
                return $this->listeners[$event]->getAll();
            }

            return [];
        }

        $dispatcherListeners = [];

        /** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
        foreach ($this->listeners as $registeredEvent => $listeners) {
            $dispatcherListeners[$registeredEvent] = $listeners->getAll();
        }

        return $dispatcherListeners;
    }

    /**
     * {@inheritdoc}
     */
    public function hasListener(callable $callback, ?string $eventName = null): bool
    {
        if ($eventName) {
            if (isset($this->listeners[$eventName])) {
                return $this->listeners[$eventName]->has($callback);
            }
        } else {
            /** @noinspection PhpLoopCanBeConvertedToArrayAnyInspection */
            foreach ($this->listeners as $queue) {
                if ($queue->has($callback)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener(string $eventName, callable $listener): void
    {
        if (isset($this->listeners[$eventName])) {
            $this->listeners[$eventName]->remove($listener);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearListeners(?string $event = null): static
    {
        if ($event) {
            if (isset($this->listeners[$event])) {
                unset($this->listeners[$event]);
            }
        } else {
            $this->listeners = [];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function countListeners(string $event): int
    {
        return isset($this->listeners[$event]) ? count($this->listeners[$event]) : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber(SubscriberInterface $subscriber): void
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_array($params)) {
                $priority = $params[1] ?? Priority::NORMAL;

                $this->addListener(
                    $eventName,
                    [$subscriber, $params[0]],
                    $priority instanceof Priority
                        ? $priority->value
                        : $priority
                );

                continue;
            }

            $this->addListener($eventName, [$subscriber, $params]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeSubscriber(SubscriberInterface $subscriber): void
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_array($params)) {
                $this->removeListener($eventName, [$subscriber, $params[0]]);
            } else {
                $this->removeListener($eventName, [$subscriber, $params]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(EventInterface $event): EventInterface
    {
        if (isset($this->listeners[$event->getName()])) {
            foreach ($this->listeners[$event->getName()] as $listener) {
                if ($event->isStopped()) {
                    return $event;
                }

                $listener($event);
            }
        }

        return $event;
    }
}
