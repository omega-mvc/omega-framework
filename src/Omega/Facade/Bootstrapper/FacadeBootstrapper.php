<?php

/**
 * Part of Omega - Facade Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Facade\Bootstrapper;

use Omega\Application\Application;
use Omega\Facade\AbstractFacade;

/**
 * RegisterFacades is responsible for initializing the facades system in the application.
 *
 * It sets the base application instance used by all facades, allowing static calls
 * to facades to resolve underlying services from the container.
 *
 * @category  Omega
 * @package   Facade
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class FacadeBootstrapper
{
    /**
     * Bootstrap facades for the given application instance.
     *
     * This method sets the base application instance in the AbstractFacade class,
     * which is then used by all facades to resolve the underlying service objects.
     *
     * @param Application $app The application instance to associate with facades
     * @return void
     */
    public function bootstrap(Application $app): void
    {
        AbstractFacade::setFacadeBase($app);
    }
}
