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
 * A test facade for verifying abstract facade behavior.
 *
 * This facade is used in unit tests to ensure that facades correctly
 * resolve their underlying services when registered in the container.
 * It points to a real, test-specific binding named 'test'.
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
class TestAbstractFacade extends AbstractFacade
{
    /**
     * Get the service container key that this facade resolves.
     *
     * @return string The container accessor key
     */
    public static function getFacadeAccessor(): string
    {
        return 'test';
    }
}
