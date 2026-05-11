<?php

/**
 * Part of Omega - CSRF Protection Package.
 * PHP version 8.3
 *
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Omega\Csrf\Exception;

use Exception;

/**
 * Exception thrown when a CSRF token is malformed.
 *
 * This exception is triggered when the provided CSRF token does not meet
 * the expected format or length, indicating a potential integrity issue.
 *
 * @category   Omega
 * @package    Csrf
 * @subpackage Exception
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
class CsrfMalformedTokenException extends Exception implements CsrfTokenExceptionInterface
{
}
