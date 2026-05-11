<?php

/**
 * Part of Omega - Tests\Support\Bootstrap Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support\Bootstrap;

use ErrorException;
use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ExceptionHandler;
use Omega\Http\Request;
use Omega\Exceptions\Bootstrapper\HandleExceptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\Support\Bootstrap\Support\TestHandleExceptions;
use Tests\Support\Bootstrap\Support\TestLog;
use Throwable;

/**
 * Class HandleExceptionsTest
 *
 * This test suite verifies that the application's exception and error handling
 * system works as intended. It ensures that:
 *
 * - Runtime errors are transformed into ErrorException instances when appropriate.
 * - User deprecation warnings are routed through the application's exception handler.
 * - Thrown exceptions are handled properly via the configured ExceptionHandler.
 * - Shutdown handling is registered correctly, even though its execution cannot be
 *   fully tested within the PHPUnit runtime environment.
 *
 * These tests validate that the HandleExceptions bootstrapper integrates with the
 * application container and environment settings to provide consistent and safe
 * exception handling behavior.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Bootstrap
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(ExceptionHandler::class)]
#[CoversClass(Request::class)]
#[CoversClass(HandleExceptions::class)]
class HandleExceptionsTest extends TestCase
{
    /**
     * Test it can handle error.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception if a generic error occurred
     */
    public function testItCanHandleError(): void
    {
        $app = new Application(basePath: __DIR__ . '/fixtures');
        $app->set('environment', 'testing');

        $handle = new HandleExceptions();
        $handle->bootstrap($app);

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage(__CLASS__);
        $handle->handleError(E_ERROR, __CLASS__, __FILE__, __LINE__);

        $app->flush();
    }

    /**
     * Test it can handle error deprecation.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception if a generic error occurred
     */
    public function testItCanHandleErrorDeprecation(): void
    {
        $app = new Application(basePath: __DIR__ . '/fixtures');
        $app->set('environment', 'testing');
        $app->set(ExceptionHandler::class, fn () => new TestHandleExceptions($app));
        $app->set('log', fn () => new TestLog());

        $handle = new HandleExceptions();
        $handle->bootstrap($app);

        $app[ExceptionHandler::class]->deprecated();
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('deprecation');
        $handle->handleError(E_USER_DEPRECATED, 'deprecation', __FILE__, __LINE__);

        $app->flush();
    }

    /**
     * Test it can handle exception.
     *
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     */
    public function testItCanHandleException(): void
    {
        $app = new Application(basePath: __DIR__ . '/fixtures');
        $app->set('request', fn (): Request => new Request('/'));
        $app->set('environment', 'testing');
        $app->set(ExceptionHandler::class, fn () => new TestHandleExceptions($app));

        $handle = new HandleExceptions();
        $handle->bootstrap($app);

        try {
            throw new ErrorException('testing');
        } catch (Throwable $th) {
            $handle->handleException($th);
        }
        $app->flush();
    }

    /**
     * This test is intentionally skipped because shutdown handlers cannot be
     * reliably tested inside the same PHPUnit process. PHPUnit intercepts fatal
     * errors and prevents the application from reaching the natural shutdown
     * phase where HandleExceptions::handleShutdown() would normally trigger.
     *
     * A real test would require running the application in a separate PHP
     * process and letting it terminate naturally, which is outside the scope
     * of these unit tests.
     *
     * @return void
     */
    public function testItCanHandleShutdown(): void
    {
        $this->markTestSkipped('Shutdown behavior cannot be tested within PHPUnit runtime.');
    }
}
