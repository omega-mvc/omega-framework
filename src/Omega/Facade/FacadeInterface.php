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

namespace Omega\Facade;

/**
 * Defines the contract for facades, which act as static proxies to
 * objects resolved from the application container.
 *
 * @category  Omega
 * @package   Facade
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
interface FacadeInterface
{
    /**
     * Get the container binding key for the underlying instance.
     *
     * @return string The service identifier used to resolve the underlying instance
     */
    public static function getFacadeAccessor(): string;
}
