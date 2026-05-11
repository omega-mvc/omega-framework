<?php

/**
 * Part of Omega MVC - Archive Package
 * php version 8.3
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Omega\Archive;

/**
 * AdapterInterface class.
 *
 * Interface defining basic operations for managing file archives.
 * It provides methods to open, close, read, write, delete, and manipulate files within an archive.
 * The archive should support checking for the existence of keys and directory management.
 *
 * @category    Omega
 * @package     Archive
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
interface AdapterInterface
{
    /**
     * Opens the archive specified by the file path.
     *
     * @param string $file Holds the path to the archive file to open.
     * @return void
     */
    public function open(string $file): void;

    /**
     * Closes the currently open archive.
     *
     * @return void
     */
    public function close(): void;

    /**
     * Reads the content of a specific key within the archive.
     *
     * @param string $key  Holds the key of the file to read.
     * @return string|bool Return the content of the file, or false if the key does not exist.
     */
    public function read(string $key): string|bool;

    /**
     * Writes content to the specified key in the archive.
     *
     * @param string $key     Holds the key to write the content to.
     * @param string $content Holds the content to write.
     * @return int|bool The number of bytes written, or false on failure.
     */
    public function write(string $key, string $content): int|bool;

    /**
     * Deletes the specified key from the archive.
     *
     * This method deletes the specified key or file from the archive.
     * In some implementations (like Bz2), this functionality is not supported,
     * and the method returns false.
     *
     * @param string $key Holds the key to delete.
     * @return bool True if the deletion was successful, false otherwise.
     */
    public function delete(string $key): bool;

    /**
     * Checks if a key exists within the archive.
     *
     * @param string $key Holds the key to check.
     * @return bool Return true if the key exists, false otherwise.
     */
    public function exists(string $key): bool;

    /**
     * Returns an array of all keys present in the archive.
     *
     * @return string[] Return an array of all keys in the archive.
     */
    public function keys(): array;

    /**
     * Checks if a given key corresponds to a directory within the archive.
     *
     * @param string $key Holds the key to check.
     * @return bool Return true if the key is a directory, false otherwise.
     */
    public function isDirectory(string $key): bool;

    /**
     * Returns the timestamp of the last modification for a key in the archive.
     *
     * This method returns the last modification timestamp of the specified file or key.
     * If the key does not exist or cannot retrieve the timestamp, it returns `false`.
     *
     * @param string $key Holds the key to check.
     * @return int|bool Return the last modification timestamp, or false on failure.
     */
    public function mtime(string $key): int|bool;

    /**
     * Renames a file from the source to the target location.
     *
     * This method checks for the existence of both the source and target files,
     * as well as the readability and writability of the involved files and directories.
     * If any of these checks fail, or if the renaming operation fails, a `RuntimeException` is thrown.
     *
     * @param string $sourceKey Holds the current key name.
     * @param string $targetKey Holds the new key name.
     * @return bool Return true if the rename was successful, false otherwise.
     */
    public function rename(string $sourceKey, string $targetKey): bool;
}
