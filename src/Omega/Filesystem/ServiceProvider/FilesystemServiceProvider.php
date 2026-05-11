<?php

/**
 * Part of Omega -  Filesystem Package.
 * php version 8.3
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Omega\Filesystem\ServiceProvider;

use Omega\Application\Application;
use Omega\Container\Contracts\ServiceProvider\ServiceProviderInterface;
use Omega\Filesystem\Factory\FilesystemFactory;
use Omega\Support\Facade\Facades\Config;

/**
 * Filesystem service provider class.
 *
 * The `FilesystemServiceProvider` class is responsible for creating the FilesystemFactory instance
 * and defining the available drivers for the filesystem service, such as the 'local' driver.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  ServiceProvider
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class FilesystemServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function bind(Application $application): void
    {
        $application->alias('filesystem', function () {
            $config  = Config::get('filesystem');
            $default = $config['default'];

            return (new FilesystemFactory())->create($config[$default]);
        });
    }
}
