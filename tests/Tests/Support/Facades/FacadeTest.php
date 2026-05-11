<?php

/**
 * Part of Omega - Tests\Support\Facades Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpConditionAlreadyCheckedInspection */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpRedundantOptionalArgumentInspection */

declare(strict_types=1);

namespace Tests\Support\Facades;

use Exception;
use Omega\Application\Application;
use Omega\Collection\Collection;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Database\Connection;
use Omega\Database\DatabaseManager;
use Omega\Database\Query\Table;
use Omega\Security\Hashing\HashManager;
use Omega\Facade\AbstractFacade;
use Omega\Cache\Facade\Cache;
use Omega\Config\Facade\Config;
use Omega\Database\Facades\DB;
use Omega\Facade\Exception\FacadeObjectNotSetException;
use Omega\Support\Facades\FacadeInterface;
use Omega\Security\Facade\Hash;
use Omega\Database\Facades\PDO;
use Omega\Cron\Facade\Schedule;
use Omega\Database\Facades\Schema;
use Omega\View\Facades\View;
use Omega\View\Facades\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversClassesThatImplementInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception as PHPUnitException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Tests\FixturesPathTrait;
use Tests\Support\Facades\Sample\FacadesTestClass;
use Tests\Support\Facades\Support\NullFacade;
use Tests\Support\Facades\Support\TestAbstractFacade;

/**
 * Tests the behavior of the facade system.
 *
 * This test suite verifies that:
 * - facades correctly resolve their underlying instances from the application container,
 * - static method calls are properly proxied to the resolved objects,
 * - cached instances are reused,
 * - each facade returns the correct accessor key,
 * - convenience methods like DB::table() and DB::from() return the correct query builder.
 *
 * It also ensures that appropriate errors are thrown when the facade base
 * application has not been initialized.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Facades
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(AbstractFacade::class)]
#[CoversClass(Application::class)]
#[CoversClass(DB::class)]
#[CoversClass(DatabaseManager::class)]
#[CoversClass(Cache::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Collection::class)]
#[CoversClass(Config::class)]
#[CoversClass(ConfigRepository::class)]
#[CoversClass(Connection::class)]
#[CoversClassesThatImplementInterface(FacadeInterface::class)]
#[CoversClass(FacadeObjectNotSetException::class)]
#[CoversClass(Hash::class)]
#[CoversClass(HashManager::class)]
#[CoversClass(PDO::class)]
#[CoversClass(Schedule::class)]
#[CoversClass(Schema::class)]
#[CoversClass(Table::class)]
#[CoversClass(View::class)]
#[CoversClass(Vite::class)]
final class FacadeTest extends TestCase
{
    use FixturesPathTrait;

    protected function tearDown(): void
    {
        AbstractFacade::setFacadeBase(null);
        AbstractFacade::flushInstance();

        parent::tearDown();
    }

    /**
     * Test it can call static.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    final public function testItCanCallStatic(): void
    {
        $app = new Application($this->setFixtureBasePath());
        $app->set(Collection::class, fn () => new Collection(['php' => 'greater']));

        AbstractFacade::setFacadeBase($app);

        $this->assertTrue(FacadesTestClass::has('php'));
        $app->flush();
        AbstractFacade::flushInstance();
    }

    /**
     * Test it throw error when application is not set.
     *
     * @return void
     */
    public function testItThrowErrorWhenApplicationIsNotSet(): void
    {
        AbstractFacade::flushInstance();
        AbstractFacade::setFacadeBase(null);

        $this->expectException(FacadeObjectNotSetException::class);
        $this->expectExceptionMessage('The facade instance for Tests\Support\Facades\Sample\FacadesTestClass has not been set.');

        FacadesTestClass::has('php');
    }

    /**
     * Test constructor sets application.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testConstructorSetsApplication(): void
    {
        $app = new Application($this->setFixtureBasePath());

        new TestAbstractFacade($app);

        $ref = new ReflectionProperty(AbstractFacade::class, 'app');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $ref->setAccessible(true);

        $this->assertSame($app, $ref->getValue());

        AbstractFacade::setFacadeBase(null);
    }

    /**
     * test it throws excetiion when app is not set.
     *
     * @return void
     */
    public function testItThrowsExceptionWhenAppIsNotSet(): void
    {
        $this->expectException(FacadeObjectNotSetException::class);

        NullFacade::has('php');
    }

    /**
     * Test facade uses cached instance.
     *
     * @return void
     * @throws Exception Throw when a generic error occurred.
     */
    public function testFacadeUsesCachedInstance(): void
    {
        $app = new Application($this->setFixtureBasePath());

        $app->set(Collection::class, fn () => new Collection(['php' => 'greater']));

        AbstractFacade::setFacadeBase($app);

        $this->assertTrue(FacadesTestClass::has('php'));
        $this->assertTrue(FacadesTestClass::has('php'));
    }

    /**
     * Test that a facade correctly returns its accessor.
     *
     * This ensures that each facade resolves the correct binding or class string
     * used internally to fetch the underlying instance from the container.
     *
     * @param class-string $facade The fully qualified facade class to test.
     * @param string $accessor The expected container binding key or class name.
     * @return void
     */
    #[DataProvider('facadeAccessorProvider')]
    public function testFacadeAccessor(string $facade, string $accessor): void
    {
        $this->assertSame($accessor, $facade::getFacadeAccessor());
    }

    /**
     * Provides a list of facades and their expected accessors.
     *
     * Each item contains:
     * - The facade class name to test.
     * - The expected string or class that should be returned by getFacadeAccessor().
     *
     * This is used as a data provider for {@see testFacadeAccessor}.
     *
     * @return array<int, array{0: class-string, 1: string}>
     */
    public static function facadeAccessorProvider(): array
    {
        return [
            [DB::class, DatabaseManager::class],
            [Config::class, ConfigRepository::class],
            [Cache::class, 'cache'],
            [Hash::class, HashManager::class],
            [PDO::class, Connection::class],
            [Schedule::class, 'schedule'],
            [Schema::class, 'Schema'],
            [View::class, 'view.instance'],
            [Vite::class, 'vite.gets'],
        ];
    }

    /**
     * Test table returns query builder.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws Exception Throw whena generic error occurred.
     */
    public function testTableReturnsQueryBuilder(): void
    {
        $app = new Application($this->setFixtureBasePath());

        $connection = $this->createStub(Connection::class);

        $app->set(Connection::class, fn () => $connection);

        AbstractFacade::setFacadeBase($app);

        $table = DB::table('users');

        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * Test from returns query builder.
     *
     * @return void
     * @throws PHPUnitException
     */
    public function testFromReturnsQueryBuilder(): void
    {
        $connection = $this->createStub(Connection::class);

        $table = DB::from('users', $connection);

        $this->assertInstanceOf(Table::class, $table);
    }
}
