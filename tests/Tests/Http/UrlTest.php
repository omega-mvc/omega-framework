<?php

/**
 * Part of Omega - Tests\Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\Request;
use Omega\Http\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Omega\Application\path;

/**
 * Class UrlTest
 *
 * This test suite validates the behavior of the Url value object,
 * ensuring correct parsing and inspection of URL components.
 *
 * It verifies:
 * - Accurate extraction of schema, host, port, user, password, path,
 *   query parameters, and fragment from full URLs.
 * - URL creation from a Request instance.
 * - Proper handling of schema-less URLs.
 * - Correct detection of existing and missing URL components.
 *
 * The goal is to guarantee reliable and consistent URL parsing logic.
 *
 * @category  Tests
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Request::class)]
#[CoversClass(Url::class)]
#[CoversFunction('Omega\Support\path')]
final class UrlTest extends TestCase
{
    /**
     * Test it url parse.
     *
     * @return void
     */
    public function testItUrlParse(): void
    {
        $url = Url::parse('http://username:password@hostname:9090/path?arg=value#anchor');

        $this->assertEquals('http', $url->schema());
        $this->assertEquals('hostname', $url->host());
        $this->assertEquals(9090, $url->port());
        $this->assertEquals('username', $url->user());
        $this->assertEquals('password', $url->password());
        $this->assertEquals('/path', $url->path());
        $this->assertEquals(['arg' => 'value'], $url->query());
        $this->assertEquals('anchor', $url->fragment());
    }

    /**
     * Test it url parse using request.
     *
     * @return void
     */
    public function testItUrlParseUsingRequest(): void
    {
        $request = new Request('http://username:password@hostname:9090/path?arg=value#anchor');
        $url     = Url::fromRequest($request);

        $this->assertEquals('http', $url->schema());
        $this->assertEquals('hostname', $url->host());
        $this->assertEquals(9090, $url->port());
        $this->assertEquals('username', $url->user());
        $this->assertEquals('password', $url->password());
        $this->assertEquals('/path', $url->path());
        $this->assertEquals(['arg' => 'value'], $url->query());
        $this->assertEquals('anchor', $url->fragment());
    }

    /**
     * Test it url parse missing schema.
     *
     * @return void
     */
    public function testItUrlParseMissingSchema(): void
    {
        $url = Url::parse('//www.example.com/path?googleguy=googley');

        $this->assertEquals('www.example.com', $url->host());
        $this->assertEquals('/path', $url->path());
        $this->assertEquals(['googleguy' => 'googley'], $url->query());
    }

    /**
     * Test it can check url parse.
     *
     * @return void
     */
    public function testItCanCheckUrlParse(): void
    {
        $url = Url::parse('http://username:password@hostname:9090/path?arg=value#anchor');

        $this->assertTrue($url->hasSchema());
        $this->assertTrue($url->hasHost());
        $this->assertTrue($url->hasPort());
        $this->assertTrue($url->hasUser());
        $this->assertTrue($url->hasPassword());
        $this->assertTrue($url->hasPath());
        $this->assertTrue($url->hasQuery());
        $this->assertTrue($url->hasFragment());
    }

    /**
     * Test it can check url parse missing schema.
     *
     * @return void
     */
    public function testItCanCheckUrlParseMissingSchema(): void
    {
        $url = Url::parse('//www.example.com/path?googleguy=googley');

        $this->assertFalse($url->hasSchema());
        $this->assertTrue($url->hasHost());
        $this->assertFalse($url->hasPort());
        $this->assertFalse($url->hasUser());
        $this->assertFalse($url->hasPassword());
        $this->assertTrue($url->hasPath());
        $this->assertTrue($url->hasQuery());
        $this->assertFalse($url->hasFragment());
    }
}
