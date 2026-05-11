<?php

/**
 * Part of Omega - Tests\Support Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support\Helper;

use Exception;
use Omega\Router\Router;
use Omega\Testing\TestResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Omega\Http\redirect;
use function Omega\Http\redirect_route;;

/**
 * Test suite for redirect helper functions and RedirectResponse behavior.
 *
 * This class verifies that the `redirect()` and `redirect_route()` helpers
 * correctly generate HTTP redirect responses with the expected status code
 * and target URL.
 *
 * The tests cover:
 * - Redirecting to a named route with dynamic parameters.
 * - Redirecting to a named route without parameters.
 * - Redirecting directly to a given URL.
 * - Proper integration with the Router for route resolution.
 * - Correct HTTP status code (302) and response content.
 *
 * It ensures that redirect responses are properly constructed and that
 * route-based redirection behaves consistently with the defined routing
 * configuration.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Helper
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Router::class)]
#[CoversClass(TestResponse::class)]
#[CoversFunction('Omega\Support\redirect')]
#[CoversFunction('Omega\Support\redirect_route')]
final class RedirectResponseTest extends TestCase
{
    /**
     * Test it redirect to correct url.
     *
     * @return void
     * @throws Exception
     */
    public function testItRedirectToCorrectUrl(): void
    {
        Router::get('/test/(:any)', fn ($test) => $test)->name('test');
        $redirect = redirect_route('test', ['ok']);
        $response = new TestResponse($redirect);
        $response->assertStatusCode(302);
        $response->assertSee('Redirecting to /test/ok');

        Router::reset();
    }

    /**
     * Test it redirect to correct url with plan url.
     *
     * @return void
     * @throws Exception
     */
    public function testItRedirectToCorrectUrlWithPlanUrl(): void
    {
        Router::get('/test', fn ($test) => $test)->name('test');
        $redirect = redirect_route('test');
        $response = new TestResponse($redirect);
        $response->assertStatusCode(302);
        $response->assertSee('Redirecting to /test');

        Router::reset();
    }

    /**
     * Test it can redirect using given url.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanRedirectUsingGivenUrl(): void
    {
        $redirect = redirect('/test');
        $response = new TestResponse($redirect);
        $response->assertStatusCode(302);
        $response->assertSee('Redirecting to /test');
    }
}
