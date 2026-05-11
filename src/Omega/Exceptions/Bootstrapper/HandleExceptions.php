<?php

/**
 * Part of Omega - Exceptions Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpReturnValueOfMethodIsNeverUsedInspection */
/** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace Omega\Exceptions\Bootstrapper;

use ErrorException;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ExceptionHandler;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Throwable;

use function error_get_last;
use function error_reporting;
use function in_array;
use function ini_set;
use function php_sapi_name;
use function register_shutdown_function;
use function set_error_handler;
use function set_exception_handler;
use function str_repeat;

use const E_COMPILE_ERROR;
use const E_CORE_ERROR;
use const E_DEPRECATED;
use const E_ERROR;
use const E_PARSE;
use const E_USER_DEPRECATED;

/**
 * HandleExceptions is responsible for registering and managing the application's error and exception handling.
 *
 * This class sets up global handlers for PHP errors, exceptions, and shutdown events.
 * It reserves memory to allow handling fatal errors gracefully and logs deprecation warnings if a logger is available.
 *
 * Key responsibilities include:
 * - Setting custom error and exception handlers
 * - Capturing fatal errors on shutdown
 * - Logging deprecation notices
 *
 * @category   Omega
 * @package    Exceptions
 * @subpackage Bootstrapper
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class HandleExceptions
{
    /** @var Application The application instance used by this handler. */
    private Application $app;

    /** @var string|null Reserved memory buffer to allow handling fatal errors without running out of memory. */
    public static ?string $reserveMemory = null;

    /**
     * Bootstrap the exception handling for the given application instance.
     *
     * Sets up error reporting, registers custom handlers for errors, exceptions, and shutdown,
     * and disables displaying errors outside the testing environment.
     *
     * @param Application $app The application instance
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function bootstrap(Application $app): void
    {
        self::$reserveMemory = str_repeat('x', 32_768);

        $this->app = $app;

        error_reporting(E_ALL);

        /** @phpstan-ignore-next-line */
        if ('testing' !== $app->getEnvironment()) {
            set_error_handler([$this, 'handleError']);
            set_exception_handler([$this, 'handleException']);
        }

        register_shutdown_function([$this, 'handleShutdown']);

        if ('testing' !== $app->getEnvironment()) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Handle PHP errors by converting them to ErrorException or logging deprecation notices.
     *
     * @param int $level The error level
     * @param string $message The error message
     * @param string $file The file in which the error occurred
     * @param int|null $line The line number of the error
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ErrorException if a non-deprecated error occurs.
     */
    public function handleError(int $level, string $message, string $file = '', ?int $line = 0): void
    {
        if ($this->isDeprecation($level)) {
            $this->handleDeprecationError($message, $file, $line, $level);
        }

        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle deprecation errors by logging them if a logger is available.
     *
     * @param string $message The deprecation message
     * @param string $file The file where the deprecation occurred
     * @param int $line The line number of the deprecation
     * @param int $level The error level
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    private function handleDeprecationError(string $message, string $file, int $line, int $level): void
    {
        $this->log($level, $message);
    }

    /**
     * Handle uncaught exceptions by reporting and rendering them.
     *
     * @param Throwable $th The exception to handle
     * @return void
     * @throws Throwable
     */
    public function handleException(Throwable $th): void
    {
        self::$reserveMemory = null;

        $handler = $this->getHandler();
        $handler->report($th);

        if (php_sapi_name() !== 'cli') {
            $handler->render($this->app['request'], $th)->send();
        }
    }

    /**
     * Handle shutdown events, checking for fatal errors.
     *
     * @return void
     * @throws Throwable
     */
    public function handleShutdown(): void
    {
        self::$reserveMemory = null;

        $error = error_get_last();
        if ($error && $this->isFatal($error['type'])) {
            $this->handleException(
                new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])
            );
        }
    }

    /**
     * Log a message if the logger is available in the application.
     *
     * @param int $level The error or deprecation level
     * @param string $message The message to log
     * @return bool True if logged, false otherwise
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    private function log(int $level, string $message): bool
    {
        if ($this->app->has('log')) {
            $this->app['log']->log($level, $message);
            return true;
        }

        return false;
    }

    /**
     * Get the exception handler instance from the container.
     *
     * @return ExceptionHandler
     */
    private function getHandler(): ExceptionHandler
    {
        return $this->app[ExceptionHandler::class];
    }

    /**
     * Determine if an error level corresponds to a deprecation notice.
     *
     * @param int $level The error level
     * @return bool True if it is a deprecation, false otherwise
     */
    private function isDeprecation(int $level): bool
    {
        return in_array($level, [E_DEPRECATED, E_USER_DEPRECATED]);
    }

    /**
     * Determine if an error level corresponds to a fatal error.
     *
     * @param int $level The error level
     * @return bool True if it is fatal, false otherwise
     */
    private function isFatal(int $level): bool
    {
        return in_array($level, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }
}
