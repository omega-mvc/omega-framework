<?php

/**
 * Part of Omega - Logging Package.
 * php verson 8.2
 *
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Omega\Logging;

use Strimgable;
use Omega\Logging\Exception\LogArgumentException;

/**
 * Logger interface.
 *
 * The LoggerInterface defines a standard contract for logging functionality in the Omega Logging Package.
 * It includes methods for logging messages at various severity levels, based on the PSR-3 logging specification:
 *
 * * emergency(): Logs messages when the system is unusable.
 * * alert(): Logs messages requiring immediate attention.
 * * critical(): Logs messages for critical conditions.
 * * error(): Logs runtime errors that need monitoring but not immediate action.
 * * warning(): Logs messages for exceptional occurrences that are not errors.
 * * notice(): Logs significant but normal events.
 * * info(): Logs general operational information.
 * * debug(): Logs detailed debugging information for developers.
 *
 * Each method accepts a string or Stringable message, along with an optional context array for additional details.
 *
 * The log() method is abstract and must be implemented by concrete classes, allowing them to handle logging at any
 * arbitrary level. It accepts a level, a message, and an optional context array. The method throws a
 * LogArgumentException if invalid arguments are provided.
 *
 * This interface ensures that any logger class implementing it will conform to a common structure, allowing consistent
 * logging behavior across the application.
 *
 * @category   Omega
 * @package    Logging
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
interface LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param string|Stringable    $message Holds the message for system is unusable.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function emergency(string|Stringable $message, array $context = []): void;

    /**
     * Action must be taken immediately.
     *
     * @param string|Stringable    $message Holds the message for action must be taken immediately.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function alert(string|Stringable $message, array $context = []): void;

    /**
     * Critical condition.
     *
     * @param string|Stringable    $message Holds the message for critical condition.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function critical(string|Stringable $message, array $context = []): void;

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable    $message Holds the message for runtime errors.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function error(string|Stringable $message, array $context = []): void;

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string|Stringable    $message Holds the message for exceptional errors.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function warning(string|Stringable $message, array $context = []): void;

    /**
     * Normal but significant events.
     *
     * @param string|Stringable    $message Holds the message for normal but significant events.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function notice(string|Stringable $message, array $context = []): void;

    /**
     * Interesting events.
     *
     * @param string|Stringable    $message Holds the message for interesting events.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function info(string|Stringable $message, array $context = []): void;

    /**
     * Detailed debug information.
     *
     * @param string|Stringable    $message Holds the message for detailed debug information.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function debug(string|Stringable $message, array $context = []): void;

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed                $level   Holds the log level.
     * @param string|Stringable    $message Holds the log message.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     * @throws LogArgumentException
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void;
}
