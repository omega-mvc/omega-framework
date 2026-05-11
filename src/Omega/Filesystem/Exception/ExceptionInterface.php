<?php

/**
 * Part of Omega - Filesystem Package.
 * php version 8.3
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Omega\Filesystem\Exception;

/**
 * Interface for the Omega related exceptions.
 *
 * This interface serves as a marker for all exceptions that are specific
 * to the Omega filesystem component. It allows for type-hinting and
 * catching of exceptions that are specific to this namespace, ensuring
 * consistency and clarity in exception handling throughout the Omega
 * filesystem implementation.
 *
 * All custom exception classes related to the Omega filesystem should
 * implement this interface to provide a clear structure for error
 * management and to facilitate the identification of filesystem-related
 * errors in client code.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Exception
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
interface ExceptionInterface
{
}
