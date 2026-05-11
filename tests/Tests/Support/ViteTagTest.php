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

/** @noinspection PhpExpressionResultUnusedInspection */
/** @noinspection HtmlWrongAttributeValue */

declare(strict_types=1);

namespace Tests\Support;

use Exception;
use Omega\View\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Tests\FixturesPathTrait;

use function file_put_contents;
use function mkdir;
use function unlink;

/**
 * Test suite for the Vite tag generation functionality.
 *
 * This class verifies the behavior of the Vite helper in creating HTML tags
 * for assets, including scripts, styles, and module preloads. It tests
 * private helper methods such as URL escaping, CSS detection, attribute
 * string building, and tag generation with custom attributes.
 *
 * The tests cover:
 * - Proper escaping of URLs.
 * - Correct identification of CSS files.
 * - Generation of script, style, and preload HTML tags.
 * - Handling of custom attributes for tags.
 * - Integration with Vite manifests to resolve asset paths.
 * - Combination of multiple entry points into valid HTML output.
 *
 * It ensures that both standard and manifest-driven assets are processed
 * correctly and that the resulting HTML meets the expected structure.
 *
 * @category  Tests
 * @package   Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Vite::class)]
final class ViteTagTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Vite::flush();
    }

    /**
     * Test escape url.
     *
     * @return void
     */
    public function testEscapeUrl(): void
    {
        $vite   = new Vite(__DIR__, '');
        $escape = (fn ($url) => $this->{'escapeUrl'}($url))->call($vite, 'foo"bar');
        $this->assertEquals('foo&quot;bar', $escape, 'this must return escaped url for double quote');
        $escape2 = (fn ($url) => $this->{'escapeUrl'}($url))->call($vite, 'https://example.com/path');
        $this->assertEquals('https://example.com/path', $escape2, 'this must return escaped url for normal url');
    }

    /**
     * Test is CSS file.
     *
     * @return void
     */
    public function testIsCssFile(): void
    {
        $vite  = new Vite(__DIR__, '');
        $isCss = (fn ($file) => $this->{'isCssFile'}($file))->call($vite, 'foo.css');
        $this->assertTrue($isCss, 'should detect .css as css file');
        $isCss2 = (fn ($file) => $this->{'isCssFile'}($file))->call($vite, 'bar.scss');
        $this->assertTrue($isCss2, 'should detect .scss as css file');
        $isCss3 = (fn ($file) => $this->{'isCssFile'}($file))->call($vite, 'baz.js');
        $this->assertFalse($isCss3, 'should not detect .js as css file');
    }

    /**
     * Test build attribute string.
     *
     * @return void
     */
    public function testBuildAttributeString(): void
    {
        $vite   = new Vite(__DIR__, '');

        $buildAttributeString    = (fn ($attributes) => $this->{'buildAttributeString'}($attributes))->call($vite, [
            'data-foo'                => 123,
            'async'                   => 'true',
            'defer'                   => true,
            'false-should-be-ignored' => false,
            'null-should-be-ignored'  => null,
        ]);
        $this->assertEquals(
            'data-foo="123" async="true" defer',
            $buildAttributeString,
            'should build attribute string from array'
        );
    }

    /**
     * Test create style tag.
     *
     * @return void
     */
    public function testCreateStyleTag(): void
    {
        $vite = new Vite(__DIR__, '');

        $createStyleTag    = (fn () => $this->{'createStyleTag'}('foo.css'))->call($vite);
        $this->assertEquals('<link rel="stylesheet" href="foo.css">', $createStyleTag);
    }

    /**
     * Test create script tag.
     *
     * @return void
     */
    public function testCreateScriptTag(): void
    {
        $vite   = new Vite(__DIR__, '');

        $createScriptTag    = (fn () => $this->{'createScriptTag'}('foo.js'))->call($vite);
        $this->assertEquals('<script type="module" src="foo.js"></script>', $createScriptTag);
    }

    /**
     * Test create tag with attributes.
     *
     * @return void
     */
    public function testCreateTagWithAttributes(): void
    {
        $vite   = new Vite(__DIR__, '');

        $createTagWithAttributes = (
            fn (
                string $url,
                string $entrypoint,
                array $attributes
            ) => $this->{'createTag'}($url, $entrypoint, $attributes)
        )->call(
            $vite,
            'foo.js',
            'resources/js/app.js',
            [
                'data-foo' => 'bar',
                'async'    => 'true',
            ],
        );

        $this->assertEquals(
            '<script type="module" data-foo="bar" async="true" src="foo.js"></script>',
            $createTagWithAttributes
        );
    }

    /**
     * Test create preload tag.
     *
     * @return void
     */
    public function testCreatePreloadTag(): void
    {
        $vite = new Vite(__DIR__, '');

        $createPreloadTag    = (fn () => $this->{'createPreloadTag'}('foo.css'))->call($vite);
        $this->assertEquals('<link rel="modulepreload" href="foo.css">', $createPreloadTag);
    }

    /**
     * Test fet tags.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred
     */
    public function testGetTags(): void
    {
        $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

        $tag = $vite->getTags(['resources/js/app.js', 'resources/css/app.css']);
        $this->assertEquals(
            '<link rel="stylesheet" href="build/fixtures/app-4ed993c7.css">' . "\n" .
            '<script type="module" src="build/fixtures/app-0d91dc04.js"></script>',
            $tag
        );
    }

    /**
     * Test get tags attributes.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred
     */
    public function testGetTagsAttributes(): void
    {
        $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

        $tag = $vite->getTags(
            entryPoints: [
                'resources/js/app.js',
            ],
            attributes: [
                'defer' => true,
                'async' => 'true',
                'crossorigin',
            ],
        );

        $this->assertEquals(
            '<script type="module" defer async="true" crossorigin src="build/fixtures/app-0d91dc04.js"></script>',
            $tag
        );
    }

    /**
     * Test get tags attributes with exception.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred
     */
    public function testGetTagsAttributesWithException(): void
    {
        $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

        $tag = $vite->getCustomTags(
            entryPoints: [
                'resources/js/app.js' => [
                    'defer' => true,
                    'async' => 'true',
                    'crossorigin',
                ],
                'resources/css/app.css' => [],
            ],
        );

        $this->assertEquals(
            '<link rel="stylesheet" href="build/fixtures/app-4ed993c7.css">' . "\n" .
            '<script type="module" defer async="true" crossorigin src="build/fixtures/app-0d91dc04.js"></script>',
            $tag
        );
    }

    /**
     * Test get preload tags.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred
     */
    public function testGetPreloadTags(): void
    {
        $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'preload/');

        $tag = $vite->getPreloadTags(['resources/js/app.js']);
        $this->assertEquals(
            '<link rel="modulepreload" href="preload/fixtures/vendor.222bbb.js">' . "\n" .
            '<link rel="modulepreload" href="preload/fixtures/chunk-vue.333ccc.js">' . "\n" .
            '<link rel="modulepreload" href="preload/fixtures/chunk-utils.444ddd.js">' . "\n" .
            '<link rel="stylesheet" href="preload/fixtures/app.111aaa.css">',
            $tag
        );
    }

    /**
     * Test it can render head HTML tag.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanRenderHeadHtmlTag(): void
    {
        $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

        $headTag = $vite(
            'resources/css/app.css',
            'resources/js/app.js',
        );

        $this->assertEquals(
            '<link rel="stylesheet" href="build/fixtures/app-4ed993c7.css">' . "\n" .
            '<script type="module" src="build/fixtures/app-0d91dc04.js"></script>',
            $headTag
        );
    }

    /**
     * Test it can render head HTML tag in hrm mode.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanRenderHeadHtmlTagInHrmMode(): void
    {
        $vite = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

        $headTags = $vite(
            'resources/css/app.css',
            'resources/js/app.js'
        );

        $this->assertEquals(
            '<script type="module" src="http://[::1]:5173/@vite/client"></script>' . "\n" .
            '<script type="module" src="http://[::1]:5173/resources/css/app.css"></script>' . "\n" .
            '<script type="module" src="http://[::1]:5173/resources/js/app.js"></script>',
            $headTags
        );
    }

    /**
     * Test it can render head HTML tag with preload.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanRenderHeadHtmlTagWithPreload(): void
    {
        $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'preload/');

        $headTag = $vite('resources/js/app.js');

        $this->assertEquals(
            '<link rel="modulepreload" href="preload/fixtures/vendor.222bbb.js">' . "\n" .
            '<link rel="modulepreload" href="preload/fixtures/chunk-vue.333ccc.js">' . "\n" .
            '<link rel="modulepreload" href="preload/fixtures/chunk-utils.444ddd.js">' . "\n" .
            '<link rel="stylesheet" href="preload/fixtures/app.111aaa.css">' . "\n" .
            '<script type="module" src="preload/fixtures/app.111aaa.js"></script>',
            $headTag
        );
    }

    /**
     * Test get custom tags woth hmr.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred
     */
    public function testGetCustomTagsWithHmr(): void
    {
        $tmpDir = $this->setFixturePath('/fixtures/application-write/manifest1/public');
        @mkdir($tmpDir, 0777, true);

        file_put_contents($tmpDir . '/hot', 'http://localhost:5173');

        $vite = new Vite($tmpDir, 'build');

        $entryPoints = [
            'app.js' => ['async' => true],
            'main.css' => []
        ];

        $tags = $vite->getCustomTags($entryPoints);

        $this->assertStringContainsString('@vite/client', $tags);

        $this->assertStringContainsString(
            '<script type="module" async src="http://localhost:5173/app.js"></script>',
            $tags
        );

        $this->assertStringContainsString(
            'http://localhost:5173/main.css',
            $tags
        );

        unlink($tmpDir . '/hot');
    }

    /**
     * Test build attrbute string with empty attributes returns empty string.
     *
     * @return void
     * @throws ReflectionException If the method does not exist or reflection fails.
     */
    public function testBuildAttributeStringWithEmptyAttributesReturnsEmptyString(): void
    {
        $vite = new Vite('/public', '/build');

        $reflection = new ReflectionClass($vite);
        $method = $reflection->getMethod('buildAttributeString');
        $method->setAccessible(true);

        $result = $method->invoke($vite, []);

        $this->assertSame('', $result, 'Expected empty string when attributes array is empty');
    }

    /**
     * Test create script tag with type already set.
     *
     * @return void
     * @throws ReflectionException If the method does not exist or reflection fails.
     */
    public function testCreateScriptTagWithTypeAlreadySet(): void
    {
        $vite = new Vite('/public', '/build');

        $attributes = ['type' => 'text/javascript', 'async' => true];

        $result = $this->invokeCreateScriptTag($vite, 'app.js', $attributes);

        $this->assertStringContainsString('type="text/javascript"', $result);
        $this->assertStringContainsString('src="app.js"', $result); // corretto
        $this->assertStringContainsString('async', $result);

        $this->assertStringStartsWith('<script ', $result);
        $this->assertStringEndsWith('</script>', $result);
    }

    /**
    /**
     * Invokes the private `createScriptTag` method of the Vite class using reflection.
     *
     * This helper allows tests to access the internal behavior of `createScriptTag`,
     * which generates a <script> HTML tag for a given URL with optional attributes.
     *
     * It handles private visibility by setting the method accessible.
     *
     * @param Vite $vite The instance of the Vite class on which to invoke the method.
     * @param string $url The URL of the JavaScript file to include in the script tag.
     * @param array|null $attributes Optional associative array of HTML attributes to include
     *                              in the <script> tag. Boolean values are handled according
     *                              to HTML standards (true = present attribute, false/null = ignored).
     * @return string The resulting <script> HTML tag as a string.
     * @throws ReflectionException If the method does not exist or reflection fails.
     */
    private function invokeCreateScriptTag(Vite $vite, string $url, ?array $attributes = null): string
    {
        $method = new ReflectionMethod(Vite::class, 'createScriptTag');
        $method->setAccessible(true);

        return $method->invoke($vite, $url, $attributes);
    }
}
