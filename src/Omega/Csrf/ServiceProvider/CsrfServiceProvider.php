<?php

/**
 * Part of Omega - Csrf Package.
 * php version 8.3
 *
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Omega\Csrf\ServiceProvider;

use Omega\Application\Application;
use Omega\Container\Contracts\ServiceProvider\ServiceProviderInterface;
use Omega\Csrf\Csrf;

/**
 * Csrf service provider class.
 *
 * The `CsrfServiceProvider` class provides a service binding for the `Csrf` class
 * within the Omega framework. It allows you to easily access configuration parameters
 * throughout your application.
 *
 * @category   Omega
 * @package    Csrf
 * @subpackage ServiceProvider
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
class CsrfServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function bind(Application $application): void
    {
        $application->alias('csrf', function () {
            return new Csrf();
        });
    }
}
