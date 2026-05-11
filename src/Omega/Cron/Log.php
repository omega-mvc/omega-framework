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

namespace Omega\Cron;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Omega\Database\Facades\DB;

use function json_encode;
use function Omega\Time\now;

/**
 * Class Log
 *
 * Implements the `InterpolateInterface` to handle logging for scheduled cron tasks.
 * This implementation persists log messages and their contextual data into a database table (`cron`).
 *
 * Each call to `interpolate()` inserts a new record containing:
 * - The message string
 * - The serialized context array (as JSON)
 * - The timestamp of when the log entry was created
 *
 * This class may throw:
 * - `DateMalformedStringException` if the timestamp generation fails
 * - `DateInvalidTimeZoneException` if the system cannot determine the correct timezone
 *
 * Usage example:
 * ```php
 * $logger = new Log();
 * $logger->interpolate('Task executed', ['task_id' => 123]);
 * ```
 *
 * @category  Omega
 * @package   Cron
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Log implements InterpolateInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     */
    public function interpolate(string $message, array $context = []): void
    {
        DB::table('cron')
            ->insert()
            ->values([
                'message'     => $message,
                'context'     => json_encode($context),
                'date_create' => now()->getTimestamp(),
            ])
            ->execute();
    }
}
