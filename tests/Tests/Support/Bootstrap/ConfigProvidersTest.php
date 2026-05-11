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

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use RuntimeException;
use Tests\FixturesPathTrait;

use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function unlink;

/**
 * Class ConfigProvidersTest
 *
 * This test suite verifies that the ConfigProviders bootstrapper correctly loads
 * configuration values into the application container. It tests two scenarios:
 *
 * 1. Loading configuration directly from configuration files when no cache is present.
 * 2. Loading configuration from a pre-generated cache file when available.
 *
 * These tests ensure that the application's configuration system behaves consistently
 * and that configuration values are properly accessible through the container once
 * bootstrapped.
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
#[CoversClass(ConfigBootstrapper::class)]
#[CoversClass(EntryNotFoundException::class)]
class ConfigProvidersTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test it can load config from file.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanLoadConfigFromFile(): void
    {
        $app = new Application($this->setFixtureBasePath());
        $app->set('path.config', $this->setFixturePath('/fixtures/application-read/config/'));

        new ConfigBootstrapper()->bootstrap($app);
        $config = $app->get('config');

        $this->assertEquals('prod', $config->get('environment'));

        $app->flush();
    }

    /**
     * Test it can load config from cache.
     *
     * Assume this test is boostrap application.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanLoadConfigFromCache(): void
    {
        $app = new Application($this->setFixturePath('/fixtures/application-read/'));
        new ConfigBootstrapper()->bootstrap($app);
        $config = $app->get('config');

        $this->assertEquals('prod', $config->get('environment'));

        $app->flush();
    }

    /**
     * Test it throws exception on invalid config file.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItThrowsExceptionOnInvalidConfigFile(): void
    {
        $app = new Application($this->setFixtureBasePath());

        $tempConfigDir = $this->setFixturePath('/fixtures/application-write/config_test/');

        if (!is_dir($tempConfigDir)) {
            mkdir($tempConfigDir, 0777, true);
        }

        $filePath = $tempConfigDir . '/corrupted_config.php';
        file_put_contents($filePath, "<?php return 'Invalid content'; ");

        $app->set('path.config', $tempConfigDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid config file');

        try {
            new ConfigBootstrapper()->bootstrap($app);
        } finally {
            if (file_exists($filePath)) unlink($filePath);
            if (is_dir($tempConfigDir)) rmdir($tempConfigDir);
        }
    }

    /**
     * Test throws if cacge is not array.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testThrowsIfCacheIsNotArray(): void
    {
        $basePath = $this->setFixturePath('/fixtures/');

        $app = new Application($basePath);

        $cachePath = $app->getApplicationCachePath();

        $cacheFile = $cachePath . 'config.php';

        file_put_contents($cacheFile, "<?php return 'not-an-array';");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid config cache file');

        new ConfigBootstrapper()->bootstrap($app);

        unlink($cacheFile);

        $app->flush();
    }

    /**
     * Test it loads valid cache.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItLoadsValidCache(): void
    {
        $basePath = $this->setFixturePath('/fixtures/bootstrap1/');

        $app = new Application($basePath);

        $cacheFile = $app->getApplicationCachePath() . 'config.php';

        file_put_contents(
            $cacheFile,
            "<?php return ['environment' => 'cached'];"
        );

        new ConfigBootstrapper()->bootstrap($app);

        $config = $app->get('config');

        $this->assertSame('cached', $config->get('environment'));

        unlink($cacheFile);
    }

    /**
     * Test it returns empty array when no config files are found.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItReturnsEmptyArrayWhenNoConfigFilesFound(): void
    {
        $app = new Application($this->setFixtureBasePath());

        $emptyDir = $this->setFixturePath('/fixtures/application-write/empty_config_test/');

        if (!is_dir($emptyDir)) {
            mkdir($emptyDir, 0777, true);
        }

        $app->set('path.config', $emptyDir);

        new ConfigBootstrapper()->bootstrap($app);

        $config = $app->get('config');
        $this->assertEmpty($config->getAll());

        rmdir($emptyDir);
        $app->flush();
    }
}
