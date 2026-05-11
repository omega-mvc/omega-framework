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
use Omega\Collection\Collection;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Facade\Bootstrapper\FacadeBootstrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\Support\Bootstrap\Support\TestCollectionFacade;

/**
 * Class RegisterFacadesTest
 *
 * This test suite verifies that facades are properly registered and bound to the
 * application container during the bootstrap process. It ensures that the
 * facade base is correctly initialized and that facades can successfully
 * proxy calls to the underlying container-bound instances.
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
#[CoversClass(Collection::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(FacadeBootstrapper::class)]
class RegisterFacadesTest extends TestCase
{
    /**
     * Test bootstrap.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testBootstrap(): void
    {
        $app = new Application(basePath: __DIR__ . '/fixtures/');
        $app->set(Collection::class, fn () => new Collection(['php' => 'greater']));
        $app->bootstrapWith([FacadeBootstrapper::class]);

        $this->assertTrue(TestCollectionFacade::has('php'));
    }
}
