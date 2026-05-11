<?php

/**
 * Part of Omega -  Logging Package.
 * php version 8.3
 *
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Omega\Logging\ServiceProvider;

use Omega\Application\Application;
use Omega\Container\Contracts\ServiceProvider\ServiceProviderInterface;
use Omega\Support\Facade\Facades\Config;
use Omega\Logging\Factory\LoggingFactory;

/**
 * Logging service provider class.
 *
 * The `LoggingServiceProvider` class is responsible for creating the LoggingFactory instance
 * and defining the available drivers for the logging service.
 *
 * @category   Omega
 * @package    Logging
 * @subpackage ServiceProvider
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
class LoggingServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function bind(Application $application): void
    {
        $application->alias('logging', function () {
            $config  = Config::get('logging');
            $default = $config['default'];

            return (new LoggingFactory())->create($config[$default]);
        });
    }
}
