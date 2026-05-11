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
 * Defines a subscriber that can register multiple event listeners.
 *
 * A subscriber is a declarative way to bind multiple event listeners
 * to a single class. It returns a map of event names to listener definitions.
 *
 * Each event definition may specify:
 * - A method name
 * - An optional priority for execution ordering
 *
 * @category  Omega
 * @package   Event
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version   2.0.0
 */
interface SubscriberInterface
{
    /**
     * Returns the list of events this subscriber listens to.
     *
     * The returned array uses event names as keys and listener definitions as values.
     *
     * Supported formats:
     * - 'eventName' => 'methodName'
     * - 'eventName' => ['methodName', priority]
     *
     * Priority defaults to 0 if not specified.
     *
     * @return array<string, string|array{0: string, 1?: int}>
     */
    public static function getSubscribedEvents(): array;
}
