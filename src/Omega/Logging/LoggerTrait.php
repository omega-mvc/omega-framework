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

use Stringable;
use Omega\Logging\Exception\LogArgumentException;

/**
 * Logger trait.
 *
 * The LoggerTrait provides implementations for logging messages across various log levels, simplifying the logging
 * process by delegating to a single log() method. It defines several methods corresponding to different severity
 * levels, as outlined by the PSR-3 specification for logging:
 *
 * * emergency(): Logs when the system is unusable.
 * * alert(): Logs when action must be taken immediately.
 * * critical(): Logs critical conditions
 * * error(): Logs runtime errors that do not require immediate action but should be monitored.
 * * warning(): Logs exceptional occurrences that are not errors.
 * * notice(): Logs normal but significant events.
 * * info(): Logs informational messages for general operational information.
 * * debug(): Logs detailed debugging information for developers.
 *
 * Each method accepts a string or Stringable message and an optional context array, then calls the log() method with
 * the corresponding log level.
 *
 * The log() method is abstract and must be implemented by the using class. It ensures flexibility, allowing the
 * actual logging implementation to define how to handle different log levels. Additionally, it throws a
 * LogArgumentException if invalid arguments are passed.
 *
 * This trait standardizes logging behavior and promotes code reuse, enabling consistent logging practices throughout
 * the application.
 *
 * @category   Omega
 * @package    Logging
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
trait LoggerTrait
{
    /**
     * System is unusable.
     *
     * @param string|Stringable    $message Holds the message for system is unusable.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * @param string|Stringable    $message Holds the message for action must be taken immediately.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical condition.
     *
     * @param string|Stringable    $message Holds the message for critical condition.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable    $message Holds the message for runtime errors.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string|Stringable    $message Holds the message for exceptional errors.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|Stringable    $message Holds the message for normal but significant events.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * @param string|Stringable    $message Holds the message for interesting events.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string|Stringable    $message Holds the message for detailed debug information.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed                $level   Holds the log level.
     * @param string|Stringable    $message Holds the log message.
     * @param array<string, mixed> $context Holds the context of message.
     * @return void
     * @throws LogArgumentException
     */
    abstract public function log(mixed $level, string|Stringable $message, array $context = []): void;
}
