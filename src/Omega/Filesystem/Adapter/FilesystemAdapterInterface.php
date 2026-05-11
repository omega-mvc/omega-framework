<?php

/**
 * Parto of Omega MVC - Filesystem Package.
 * php version 8.3
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Omega\Filesystem\Adapter;

/**
 * Interface for the filesystem adapters.
 *
 * This interface defines the contract for adapters that manage the interaction with different
 * types of filesystems. The implementing classes provide basic operations like reading, writing,
 * and deleting files, as well as working with file metadata.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Adapter
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
interface FilesystemAdapterInterface
{
    /**
     * Reads the content of the file.
     *
     * This method reads the contents of a file identified by its key. If the file cannot
     * be read, it returns false.
     *
     * @param string $key The key or path of the file to read.
     * @return string|bool The content of the file or false if it cannot be read.
     */
    public function read(string $key): string|bool;

    /**
     * Writes the given content into the file.
     *
     * This method writes the provided content into a file identified by its key. It returns the
     * number of bytes written or false if the write operation fails.
     *
     * @param string $key     The key or path of the file to write.
     * @param string $content The content to write into the file.
     * @return int|bool The number of bytes written or false on failure.
     */
    public function write(string $key, string $content): int|bool;

    /**
     * Indicates whether the file exists.
     *
     * This method checks if a file exists at the specified key.
     *
     * @param string $key The key or path of the file to check.
     * @return bool True if the file exists, false otherwise.
     */
    public function exists(string $key): bool;

    /**
     * Returns an array of all keys (files and directories).
     *
     * This method retrieves a list of all keys (representing files and directories)
     * managed by the filesystem.
     *
     * @return array The list of keys.
     */
    public function keys(): array;

    /**
     * Returns the last modified time.
     *
     * This method returns the last modified time of a file, in a UNIX timestamp format. If
     * the file cannot be found or accessed, it returns false.
     *
     * @param string $key The key or path of the file.
     * @return int|bool The last modified time as a UNIX timestamp, or false on failure.
     */
    public function mtime(string $key): int|bool;

    /**
     * Deletes the file.
     *
     * This method deletes the file identified by its key. It returns true if the operation
     * is successful, or false if it fails.
     *
     * @param string $key The key or path of the file to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(string $key): bool;

    /**
     * Renames a file.
     *
     * This method renames a file from the source key to the target key. It returns true
     * if the operation is successful, or false if it fails.
     *
     * @param string $sourceKey The current key or path of the file.
     * @param string $targetKey The new key or path for the file.
     * @return bool True on success, false on failure.
     */
    public function rename(string $sourceKey, string $targetKey): bool;

    /**
     * Check if the key represents a directory.
     *
     * This method checks whether the specified key corresponds to a directory.
     *
     * @param string $key The key or path to check.
     * @return bool True if the key is a directory, false otherwise.
     */
    public function isDirectory(string $key): bool;
}
