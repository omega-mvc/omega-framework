<?php

/**
 * Part of Omega - Filesystem Package.
 * php version 8.3
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Omega\Filesystem;

use InvalidArgumentException;

/**
 * Interface FilesystemMapInterface.
 *
 * The `FilesystemMapInterface` defines a contract for managing and retrieving
 * filesystem instances by their associated names. It provides methods to check
 * for the existence of a filesystem and to retrieve it when needed. This interface
 * is useful in scenarios where multiple filesystems are utilized, allowing for
 * seamless access and management without the need for complex logic.
 * Implementing classes are responsible for maintaining a mapping between filesystem
 * names and their corresponding instances, enabling efficient interaction with
 * various filesystems in a unified manner.
 *
 * @category    Omega
 * @package     Filesystem
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
interface FilesystemMapInterface
{
    /**
     * Indicates whether there is a filesystem registered for the specified name.
     *
     * @param string $name The name of the filesystem to check.
     * @return bool TRUE if a filesystem is registered for the specified name, FALSE otherwise.
     */
    public function has(string $name): bool;

    /**
     * Returns the filesystem registered for the specified name.
     *
     * @param string $name The name of the filesystem to retrieve.
     * @return FilesystemInterface The filesystem registered for the specified name.
     * @throws InvalidArgumentException When there is no filesystem registered
     *                                  for the specified name.
     */
    public function get(string $name): FilesystemInterface;
}
