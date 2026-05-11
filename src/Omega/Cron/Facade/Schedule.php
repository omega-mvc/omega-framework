<?php

/**
 * Part of Omega - Cron Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Cron\Facade;

use Closure;
use Omega\Cron\InterpolateInterface;
use Omega\Cron\ScheduleTime;
use Omega\Facade\AbstractFacade;

/**
 * Facade for the Schedule service.
 *
 * This facade provides a static interface to the underlying `Schedule` instance
 * resolved from the application container. It allows convenient static-style
 * calls while still relying on dependency injection and the container under the hood.
 *
 * Usage of this facade does not create a global state; the underlying instance
 * is still managed by the container and may be swapped, mocked, or replaced
 * for testing or customization purposes.
 *
 * @category   Omega
 * @package    Cron
 * @subpackges Facades
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @method static ScheduleTime[]        getPools()
 * @method static ScheduleTime          call(Closure $call_back, mixed[] $params = [])
 * @method static void                  execute()
 * @method static void                  setLogger(InterpolateInterface $logger)
 * @method static void                  setTime(int $time)
 * @method static \Omega\Cron\Schedule  add(\Omega\Cron\Schedule $schedule)
 * @method static void                  flush()
 *
 * @see Schedule
 */
final class Schedule extends AbstractFacade
{
    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor(): string
    {
        return 'schedule';
    }
}
