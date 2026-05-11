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

namespace Omega\Filesystem\Contracts;

/**
 * Interface that adds native support for listing keys to an adapter.
 *
 * This interface defines a method for listing keys (file or directory identifiers)
 * in the filesystem that begin with a specified prefix. It ensures that any
 * filesystem adapter implementing this interface can retrieve a list of keys
 * starting with a given string.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Contracts
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
interface ListKeysAwareInterface
{
    /**
     * Lists keys that begin with the given prefix.
     *
     * This method retrieves an array of keys (file or directory names)
     * from the filesystem that start with the provided prefix. It does not
     * support wildcard or regex matching, only simple prefix-based filtering.
     *
     * @param string $prefix The prefix to filter the keys (optional, defaults to an empty string).
     * @return array An array of keys starting with the specified prefix.
     */
    public function listKeys(string $prefix = ''): array;
}
