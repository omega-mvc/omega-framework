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

declare(strict_types=1);

namespace Tests\Support\Facades\Support;

use Omega\Facade\AbstractFacade;

/**
 * A facade that intentionally points to a non-existent service.
 *
 * This class is used in tests to simulate scenarios where the underlying
 * service is not bound or registered in the container. It allows testing
 * error handling and fallback behaviors in code that uses facades.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Facades\Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class NullFacade extends AbstractFacade
{
    /**
     * Get the service container key that this facade resolves.
     *
     * For NullFacade, this intentionally returns a key that does not exist
     * to test failure handling.
     *
     * @return string The container accessor key
     */
    public static function getFacadeAccessor(): string
    {
        return 'i.do.not.exist';
    }
}
