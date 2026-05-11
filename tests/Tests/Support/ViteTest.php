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

namespace Tests\Support;

use Exception;
use Omega\View\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

use function chmod;
use function dirname;
use function file_put_contents;
use function is_dir;
use function json_encode;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function touch;
use function uniqid;
use function unlink;

/**
 * Test suite for the Vite support class.
 *
 * This test class verifies the integration between the Vite helper and the
 * filesystem-based fixtures, ensuring correct resolution of asset paths
 * from the Vite manifest, proper handling of Hot Module Replacement (HMR),
 * and correct generation of HTML tags for scripts, styles, and preload.
 *
 * The tests cover both standard build mode and HMR mode, validate internal
 * caching behavior, and ensure that generated URLs and HTML output match
 * the expected Vite conventions.
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
final class ViteTest extends TestCase
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
     * Test it can get file resource name.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanGetFileResourceName(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

        $file = $asset->get('resources/css/app.css');

        $this->assertEquals('build/fixtures/app-4ed993c7.css', $file);
    }

    /**
     * Test it can get file resource names.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanGetFileResourceNames(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

        $files = $asset->gets([
            'resources/css/app.css',
            'resources/js/app.js',
        ]);

        $this->assertEquals([
            'resources/css/app.css' => 'build/fixtures/app-4ed993c7.css',
            'resources/js/app.js'   => 'build/fixtures/app-0d91dc04.js',
        ], $files);
    }

    /**
     * Test it can check running hrm exist.
     *
     * @return void
     */
    public function testItCanCheckRunningHRMExist(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

        $this->assertTrue($asset->isRunningHRM());
    }

    /**
     * Test it can check running hrm does exist.
     *
     * @return void
     */
    public function testItCanCheckRunningHRMDoestExist(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

        $this->assertFalse($asset->isRunningHRM());
    }

    /**
     * Test it can get hot file resource name.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanGetHotFileResourceName(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

        $file = $asset->get('resources/css/app.css');

        $this->assertEquals('http://[::1]:5173/resources/css/app.css', $file);
    }

    /**
     * Test it can get hot file resource names.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanGetHotFileResourceNames(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

        $files = $asset->gets([
            'resources/css/app.css',
            'resources/js/app.js',
        ]);

        $this->assertEquals([
            'resources/css/app.css' => 'http://[::1]:5173/resources/css/app.css',
            'resources/js/app.js'   => 'http://[::1]:5173/resources/js/app.js',
        ], $files);
    }

    /**
     * Test it can use cache.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanUseCache(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');
        $asset->get('resources/css/app.css');

        $this->assertCount(1, Vite::$cache);
    }

    /**
     * Test it can get hot uri.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanGetHotUrl(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

        $this->assertEquals(
            'http://[::1]:5173/',
            $asset->getHmrUrl()
        );
    }

    /**
     * Test it can get hmr script.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanGetHmrScript(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

        $this->assertEquals(
            '<script type="module" src="http://[::1]:5173/@vite/client"></script>',
            $asset->getHmrScript()
        );
    }

    /**
     * Test invoke returns empty string when not entry points are provided.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testInvokeReturnsEmptyStringWhenNoEntryPointsAreProvided(): void
    {
        $vite = new Vite(__DIR__, 'build');

        $result = $vite();

        $this->assertSame('', $result);
    }

    /**
     * Test it uses custom manifest name.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItUsesCustomManifestName(): void
    {
        $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

        $vite->manifestName('custom-manifest.json');

        $this->assertStringEndsWith(
            'custom-manifest.json',
            $vite->manifest()
        );
    }

    /**
     * Test it thorws exception if manifest file not found.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItThrowsExceptionIfManifestFileNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Manifest file not found');

        $vite = new Vite(__DIR__ . '/fixtures', 'build');

        // Nome volutamente inesistente
        $vite->manifestName('does-not-exist.json');

        $vite->manifest();
    }

    /**
     * Test manifest throws exception if file does not exists.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testManifestThrowsExceptionIfFileDoesNotExist(): void
    {
        $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build');
        $vite->manifestName('nonexistent.json');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Manifest file not found/');

        $vite->manifest();
    }

    /**
     * Test loader throws exception if file cannot be read.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testLoaderThrowsExceptionIfFileCannotBeRead(): void
    {
        $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build');
        $vite->manifestName('unreadable.json');

        $filePath = $this->setFixturePath('/fixtures/support/manifest/public/build/unreadable.json');
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, '{"key":"value"}');
        chmod($filePath, 0000);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Failed to read manifest file/');

        try {
            @$vite->loader();
        } finally {
            chmod($filePath, 0644);
        }
    }

    /**
     * Test loader throws exception on invalid json.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testLoaderThrowsExceptionOnInvalidJson(): void
    {
        $vite = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build');
        $vite->manifestName('invalid.json');

        $filePath = $this->setFixturePath('/fixtures/support/manifest/public/build/invalid.json');
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, '{invalid json');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Manifest JSON decode error/');

        $vite->loader();
    }

    /**
     * Test it can get the manifest path for a specific resource.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItCanGetManifestResourcePath(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

        $path = $asset->getManifest('resources/js/app.js');

        $this->assertEquals('build/fixtures/app-0d91dc04.js', $path);
    }

    /**
     * Test it throws an exception when the resource is missing in manifest.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItThrowsExceptionIfResourceNotFoundInManifest(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/manifest/public'), 'build/');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Resource file not found non-existent-file.js');

        $asset->getManifest('non-existent-file.js');
    }

    /**
     * Test it returns cached hot URL on second call.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItReturnsCachedHotUrl(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/support/hot/public'), 'build/');

        $url1 = $asset->getHmrUrl();
        $url2 = $asset->getHmrUrl();

        $this->assertEquals($url1, $url2);
        $this->assertNotNull(Vite::$hot);
    }

    /**
     * Test it throws exception if hot file is unreadable.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItThrowsExceptionIfHotFileIsUnreadable(): void
    {
        $asset = new Vite($this->setFixturePath('/fixtures/application-write/public/'), 'build/');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to read hot file');

        $asset->getHmrUrl();
    }

    /**
     * Test it handles hot file with trailing slash already present.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItHandlesHotFileWithTrailingSlash(): void
    {
        $tempDir = sys_get_temp_dir() . '/vite_test_' . uniqid();
        mkdir($tempDir);
        file_put_contents($tempDir . '/hot', "http://localhost:5173/\n");

        $asset = new Vite($tempDir, 'build/');
        $url = $asset->getHmrUrl();

        $this->assertEquals('http://localhost:5173/', $url);

        unlink($tempDir . '/hot');
        rmdir($tempDir);
    }

    /**
     * Test it updates and returns the correct cache time.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testItUpdatesAndReturnsCacheTime(): void
    {
        $manifestDir = $this->setFixturePath('/fixtures/application-write/manifest/public/build');

        if (!is_dir($manifestDir)) {
            mkdir($manifestDir, 0777, true);
        }

        $manifestPath = "{$manifestDir}/manifest.json";
        file_put_contents($manifestPath, json_encode([
            'resources/js/app.js' => ['file' => 'app.js']
        ]));

        $expectedTime = time() - 3600;
        touch($manifestPath, $expectedTime);

        $vite = new Vite($this->setFixturePath('/fixtures/application-write/manifest/public'), 'build');

        $this->assertEquals(0, $vite->cacheTime());

        $vite->loader();

        $this->assertEquals($expectedTime, $vite->cacheTime());
        $this->assertEquals($expectedTime, $vite->manifestTime());

        unlink($manifestPath);
        rmdir($manifestDir);
    }

    /**
     * Test che getPreloadTags restituisca una stringa vuota quando HMR è attivo.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testGetPreloadTagsReturnsEmptyStringWhenHmrIsRunning(): void
    {
        $publicPath = $this->setFixturePath('/fixtures/application-write/manifest/public');
        @mkdir($publicPath, 0777, true);

        file_put_contents("{$publicPath}/hot", 'http://localhost:3000');

        $vite = new Vite($publicPath, 'build');

        $result = $vite->getPreloadTags(['main.js']);

        $this->assertSame('', $result, 'getPreloadTags dovrebbe restituire una stringa vuota se il file hot esiste.');

        unlink("{$publicPath}/hot");
        rmdir($publicPath);
    }
}
