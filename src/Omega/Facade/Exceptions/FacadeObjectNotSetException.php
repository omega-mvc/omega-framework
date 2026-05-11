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

namespace Omega\Facade\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when a facade is used without a registered underlying instance.
 *
 * This typically indicates that the facade was not properly initialized with the
 * application container. Ensure that the facade is registered and that the container
 * is correctly configured before calling facade methods.
 *
 * @category   Omega
 * @package    Fcade
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class FacadeObjectNotSetException extends RuntimeException
{
    /**
     * Create a new FacadeObjectNotSetException instance.
     *
     * @param string $className The facade class name that attempted to resolve an instance
     * @return void
     */
    public function __construct(string $className)
    {
        parent::__construct(
            sprintf(
                "The facade instance for %s has not been set. " .
                "Please ensure that the facade is registered with the application container " .
                "and that the container is configured correctly.",
                $className
            )
        );
    }
}
