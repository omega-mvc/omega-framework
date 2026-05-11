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
 * Defines the execution priority assigned to event listeners.
 *
 * Higher priority listeners are executed before lower priority listeners
 * during event dispatching.
 *
 * The NORMAL priority is used as the default level when no explicit
 * priority is provided.
 *
 * @category  Omega
 * @package   Event
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version   2.0.0
 */
enum Priority: int
{
    /** Execute the listener with the lowest possible priority. */
    case MIN = -3;

    /** Execute the listener with a low priority. */
    case LOW = -2;

    /** Execute the listener with a below normal priority. */
    case BELOW_NORMAL = -1;

    /** Execute the listener with the default priority. */
    case NORMAL = 0;

    /** Execute the listener with an above normal priority. */
    case ABOVE_NORMAL = 1;

    /** Execute the listener with a high priority. */
    case HIGH = 2;

    /** Execute the listener with the maximum possible priority. */
    case MAX = 3;
}