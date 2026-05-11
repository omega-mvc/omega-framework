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

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Tests\Support\Helper;

use Exception;
use InvalidArgumentException;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\ApplicationNotAvailableException;
use Omega\View\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

use function Omega\Application\app;
use function Omega\Environment\env;
use function Omega\Application\get_path;
use function Omega\Application\is_dev;
use function Omega\Application\is_production;
use function Omega\Application\os_detect;
use function Omega\Application\path;
use function Omega\Application\set_path;
use function Omega\Application\slash;
use function Omega\Support\vite;

/**
 * Test suite for Omega global helper functions.
 *
 * This class verifies the behavior and consistency of all core helper
 * functions provided by the Omega\Support namespace.
 *
 * The tests cover:
 * - Application container access via the `app()` helper, including lifecycle
 *   handling and exception scenarios after flushing the application instance.
 * - Environment helpers such as `env()`, `is_dev()`, and `is_production()`.
 * - Filesystem and path utilities including `path()`, `set_path()`,
 *   `get_path()`, and `slash()`, with special attention to:
 *     - Support for both string and array inputs
 *     - Correct normalization of directory separators
 *     - Proper handling of suffixes and dot-notation conversion
 *     - Validation and exception handling for invalid inputs
 * - Operating system detection via `os_detect()`, including all supported
 *   OS families and default fallback behavior.
 * - Integration of helpers with the application container, ensuring
 *   bindings are correctly resolved and returned values are accurate.
 * - The `vite()` helper, validating both single and multiple entry point
 *   resolution using mocked dependencies.
 *
 * This suite ensures that helper functions behave consistently across
 * different input types (scalar vs array), maintain cross-platform
 * compatibility, and correctly integrate with the underlying application
 * infrastructure.
 *
 * @category  Tests
 * @package   Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversFunction('Omega\Support\app')]
#[CoversFunction('Omega\Support\env')]
#[CoversFunction('Omega\Support\get_path')]
#[CoversFunction('Omega\Support\is_dev')]
#[CoversFunction('Omega\Support\is_production')]
#[CoversFunction('Omega\Support\os_detect')]
#[CoversFunction('Omega\Support\path')]
#[CoversFunction('Omega\Support\set_path')]
#[CoversFunction('Omega\Support\slash')]
#[CoversFunction('Omega\Support\vite')]
final class HelperTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test it throw error after flush application.
     *
     * @return void
     */
    public function testItThrowErrorAfterFlushApplication(): void
    {
        $app = new Application('/');
        $app->flush();

        $this->expectException(ApplicationNotAvailableException::class);
        app();
        app()->flush();
    }

    /**
     * Test it can load app.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanLoadApp(): void
    {
        $app = new Application('');

        $this->assertEquals('/', app()->get('path.base'));

        $app->flush();
    }

    /**
     * Test environment helpers.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testEnvironmentHelpers(): void
    {
        $app = new Application($this->setFixtureBasePath());

        $app->set('environment', 'prod');
        $this->assertFalse(is_dev());
        $this->assertTrue(is_production());
    }

    /**
     * Test os detect identifies all supported families.
     *
     * @return void
     */
    public function testOsDetectIdentifiesAllSupportedFamilies(): void
    {
        $this->assertEquals('windows', os_detect('Windows'));
        $this->assertEquals('linux',   os_detect('Linux'));
        $this->assertEquals('mac',     os_detect('Darwin'));
        $this->assertEquals('bsd',     os_detect('Bsd'));
        $this->assertEquals('solaris', os_detect('Solaris'));
        $this->assertEquals('unknown', os_detect('AmigaOS')); // Ramo default

        $currentOs = strtolower(PHP_OS_FAMILY);
        $expected = match($currentOs) {
            'darwin' => 'mac',
            'windows', 'linux', 'bsd', 'solaris' => $currentOs,
            default => 'unknown'
        };

        $this->assertEquals($expected, os_detect());
    }

    /**
     * Test slash handles both strings and array.
     *
     * @return void
     */
    public function testSlashHandlesBothStringsAndArrays(): void
    {
        $separator = DIRECTORY_SEPARATOR;

        $this->assertEquals("a{$separator}b", slash('a/b'));
        $this->assertEquals("c", slash('c')); // Caso senza slash per il path coverage

        $input = ['a/b', 'c/d'];
        $expected = ["a{$separator}b", "c{$separator}d"];

        $this->assertEquals($expected, slash($input));
    }

    /**
     * Test path normalization.
     *
     * @return void
     */
    public function testPathNormalization(): void
    {
        $ds = DIRECTORY_SEPARATOR;

        $this->assertEquals("app{$ds}config{$ds}", path('app.config'));

        $this->assertEquals("app{$ds}logs{$ds}", path("app.logs{$ds}"));

        $input = ['core.view', 'cache'];
        $expected = ["core{$ds}view{$ds}", "cache{$ds}"];
        $this->assertEquals($expected, path($input));

        $this->assertEquals("{$ds}", path(''));
    }

    /**
     * Test env helper returns value if not exists.
     *
     * @return void
     */
    public function testEnvHelperReturnsValueIfNotExists(): void
    {
        $default = 'default_value';

        $this->assertSame($default, env('NON_EXISTING_KEY', $default));
    }

    /**
     * Test get path with array and suffix.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testGetPathWithArrayAndSuffix(): void
    {
        $app = new Application(__DIR__);
        $ds  = DIRECTORY_SEPARATOR;

        $paths = [
            'logs' => 'storage/logs/',
            'cache' => 'storage/framework/cache/'
        ];
        $app->set('custom_paths', $paths);

        $result = get_path('custom_paths', 'daily/');

        $expected = [
            'logs' => "storage/logs/daily{$ds}",
            'cache' => "storage/framework/cache/daily{$ds}"
        ];

        $this->assertSame($expected, $result);
    }

    /**
     * Test get path with string and suffix.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testGetPathWithStringAndSuffix(): void
    {
        $app = new Application(__DIR__);
        $ds  = DIRECTORY_SEPARATOR;

        $app->set('single_path', 'app/core/');

        $result = get_path('single_path', 'test/');

        $this->assertSame("app/core/test{$ds}", $result);
    }

    /**
     * Test set path with single string.
     *
     * @return void
     */
    public function testSetPathWithSingleString(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $input = 'app.config.storage';
        $expected = "{$ds}app{$ds}config{$ds}storage{$ds}";

        $this->assertSame($expected, set_path($input));
    }

    /**
     * Test set path with array of strings.
     *
     * @return void
     */
    public function testSetPathWithArrayOfStrings(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $input = ['app.config', 'public.assets'];
        $expected = [
            "{$ds}app{$ds}config{$ds}",
            "{$ds}public{$ds}assets{$ds}"
        ];

        $this->assertSame($expected, set_path($input));
    }

    /**
     * Test set path throws eception on empty string.
     *
     * @return void
     */
    public function testSetPathThrowsExceptionOnEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The path key cannot be an empty string');

        set_path('');
    }

    /**
     * Test set path throws exception on empty array.
     *
     * @return void
     */
    public function testSetPathThrowsExceptionOnEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);

        set_path([]);
    }

    /**
     * Test vite helper handles single and multiple entry points.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testViteHelperHandlesSingleAndMultipleEntryPoints(): void
    {
        $app = new Application(__DIR__);
        $viteMock = $this->createMock(Vite::class);
        $app->set('vite.gets', $viteMock);

        $viteMock->expects($this->exactly(2))
        ->method('gets')
            ->willReturnOnConsecutiveCalls(
                ['main.js' => 'url_string'],
                ['a.js' => 'url_a', 'b.js' => 'url_b']
            );

        $this->assertSame('url_string', vite('main.js'));

        $resultArray = vite('a.js', 'b.js');
        $this->assertIsArray($resultArray);
        $this->assertCount(2, $resultArray);
        $this->assertSame('url_a', $resultArray['a.js']);
    }
}
