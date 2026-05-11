<?php

/**
 * Part of Omega - Filesystem Package.
 * php version 8.3
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

declare(strict_types=1);

namespace Omega\Filesystem\Contracts;

/**
 * Interface that adds support for file metadata management.
 *
 * This interface defines methods for setting and retrieving metadata associated
 * with a specific file (or key) in the filesystem. Any adapter implementing this
 * interface should provide mechanisms to store and fetch metadata for files.
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
interface MetadataSupporterInterface
{
    /**
     * Sets metadata for the specified file (or key).
     *
     * This method allows setting an array of metadata information for a specific
     * file in the filesystem. The metadata could include custom information like
     * timestamps, file ownership, or any other descriptive data.
     *
     * @param string $key     The file key for which the metadata is being set.
     * @param array  $content An associative array containing the metadata to set.
     * @return void
     */
    public function setMetadata(string $key, array $content): void;

    /**
     * Retrieves metadata for the specified file (or key).
     *
     * This method fetches the metadata associated with the given file key from
     * the filesystem. It returns an associative array of metadata, which may
     * include information such as size, last modification time, and more.
     *
     * @param string $key The file key for which metadata is being retrieved.
     *
     * @return array An associative array containing the metadata of the file.
     */
    public function getMetadata(string $key): array;
}
