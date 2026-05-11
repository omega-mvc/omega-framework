<?php

/**
 * Part of Omega - Filesystem Package.
 * php version 8.3
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0+
 */

declare(strict_types=1);

namespace Omega\Filesystem\Factory;

use Omega\Container\Contracts\Factory\GenericFactoryInterface;

/**
 * Filesystem factory interface.
 *
 * The `FilesystemFactoryInterface` is an extension of the GenericFactoryInterface, specifically
 * for creating filesystem-related instances. It follows the structure defined in the
 * `GenericFactoryInterface` and is used to standardize the creation of filesystem components in
 * the Omega system.
 *
 * This interface inherits the `create` method from `GenericFactoryInterface`, allowing it to
 * return any type of database-related object or value, based on an optional configuration array.
 *
 * - `create(?array $config = null): mixed`
 *   - The inherited method allows for the creation of session instances, using an optional
 *     configuration array.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Factory
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
interface FilesystemFactoryInterface extends GenericFactoryInterface
{
}
