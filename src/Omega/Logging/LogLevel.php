<?php

/**
 * Part of Omega - Logging Package.
 * php version 8.3
 *
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Omega\Logging;

/**
 * LogLevel class.
 *
 * The LogLevel class defines a set of constants representing different log levels used in the logging system of the
 * Omega. These levels help categorize log messages according to their severity or importance. The class includes
 * the following constants:
 *
 * * **EMERGENCY**: Indicates system-wide issues requiring immediate action (e.g., the system is unusable).
 * * **ALERT**: Requires immediate attention but is less critical than an emergency (e.g., database connection lost).
 * * **CRITICAL**: Serious problems that need urgent intervention (e.g., application errors).
 * * **ERROR**: Errors that prevent the application from functioning correctly (e.g., exceptions).
 * * **WARNING**: Non-critical issues that might lead to errors if not addressed (e.g., deprecated functions).
 * * **NOTICE**: Normal but significant events that don’t indicate problems (e.g., configuration changes).
 * * **INFO**: Informational messages that highlight progress or state of the application.
 * * **DEBUG**: Detailed information intended for debugging purposes.
 *
 * This class allows the consistent use of these predefined log levels across the application, making it easier to
 * manage and filter logs.
 *
 * @category   Omega
 * @package    Logging
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
final readonly class LogLevel
{
    /**
     * Emergency log level.
     *
     * @var string Holds the emergency log level.
     */
    public const EMERGENCY = 'emergency';

    /**
     * Alert log level.
     *
     * @var string Holds the alert log level.
     */
    public const ALERT = 'alert';

    /**
     * Critical log level.
     *
     * @var string Holds the critical log level.
     */
    public const CRITICAL = 'critical';

    /**
     * Error log level.
     *
     * @var string Holds the error log level.
     */
    public const ERROR = 'error';

    /**
     * Warning log level.
     *
     * @var string Holds the warning log level.
     */
    public const WARNING = 'warning';

    /**
     * Notice log level.
     *
     * @var string Holds the notice log level.
     */
    public const NOTICE = 'notice';

    /**
     * Info log level.
     *
     * @var string Holds the info log level.
     */
    public const INFO = 'info';

    /**
     * Debug log level.
     *
     * @var string Holds the debug log level.
     */
    public const DEBUG = 'debug';
}
