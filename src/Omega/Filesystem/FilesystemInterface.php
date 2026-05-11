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
use RuntimeException;
use Omega\Filesystem\Exception\FileAlreadyExistsException;
use Omega\Filesystem\Exception\FileNotFoundException;
use Omega\Filesystem\Exception\UnexpectedFileExcption;
use Omega\Filesystem\Stream\InMemoryBuffer;
use Omega\Filesystem\Stream\StreamInterface;

/**
 * Interface FilesystemInterface.
 *
 * The `FilesystemInterface` defines a contract for filesystem operations,
 * providing a set of methods for interacting with files within a filesystem.
 * This interface allows for the creation, reading, writing, deletion, and
 * management of files, as well as querying their metadata. Implementing this
 * interface enables different filesystem backends (e.g., local storage, cloud
 * storage) to be used interchangeably, promoting a consistent API for file
 * operations across various storage solutions.
 *
 * @category    Omega
 * @package     Filesystem
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
interface FilesystemInterface
{
    /**
     * Check if a file exists in the filesystem.
     *
     * @param string $key The key (path) of the file.
     * @return bool TRUE if the file exists, FALSE otherwise.
     * @throws InvalidArgumentException If $key is invalid.
     */
    public function has(string $key): bool;

    /**
     * Rename a file in the filesystem.
     *
     * This method should not be directly used; prefer using the
     * File::rename method to ensure filesystem consistency.
     *
     * @param string $sourceKey The current key (path) of the file.
     * @param string $targetKey The new key (path) for the file.
     * @return bool TRUE if the rename was successful.
     * @throws FileNotFoundException    If the source file does not exist.
     * @throws UnexpectedFileExcption   If the target key already exists.
     * @throws RuntimeException         If the rename operation fails.
     * @throws InvalidArgumentException If either $sourceKey or $targetKey is invalid.
     *
     * @see File::rename()
     */
    public function rename(string $sourceKey, string $targetKey): bool;

    /**
     * Retrieve a file from the filesystem.
     *
     * @param string $key    The key (path) of the file.
     * @param bool   $create Whether to create the file if it does not exist.
     * @return File The requested file object.
     * @throws InvalidArgumentException If $key is invalid.
     * @throws FileNotFoundException    If the file does not exist and $create is false.
     */
    public function get(string $key, bool $create = false): File;

    /**
     * Write content to a file in the filesystem.
     *
     * @param string $key       The key (path) of the file.
     * @param string $content   The content to write to the file.
     * @param bool   $overwrite Whether to overwrite the file if it already exists.
     * @return int The number of bytes written to the file.
     * @throws RuntimeException           If the content could not be written.
     * @throws InvalidArgumentException   If $key is invalid.
     * @throws FileAlreadyExistsException If the file already exists and $overwrite is false.
     */
    public function write(string $key, string $content, bool $overwrite = false): int;

    /**
     * Read content from a file in the filesystem.
     *
     * @param string $key The key (path) of the file.
     * @return string The content of the file.
     * @throws RuntimeException         If the file could not be read.
     * @throws InvalidArgumentException If $key is invalid.
     * @throws FileNotFoundException    If the file does not exist.
     */
    public function read(string $key): string;

    /**
     * Delete a file from the filesystem.
     *
     * @param string $key The key (path) of the file to delete.
     * @return bool TRUE on success, FALSE otherwise.
     * @throws RuntimeException         If the file could not be deleted.
     * @throws InvalidArgumentException If $key is invalid.
     */
    public function delete(string $key): bool;

    /**
     * Retrieve an array of all file keys in the filesystem.
     *
     * @return array An array of file keys.
     */
    public function keys(): array;

    /**
     * List file keys that begin with a specified prefix.
     *
     * If the adapter implements the ListKeysAware interface, its
     * implementation will be used. Otherwise, all keys will be
     * retrieved and filtered.
     *
     * @param string $prefix The prefix to filter keys.
     * @return array An array of file keys matching the prefix.
     */
    public function listKeys(string $prefix = ''): array;

    /**
     * Retrieve the last modified time of a specified file.
     *
     * @param string $key The key (path) of the file.
     * @return int The last modified time as a Unix timestamp.
     * @throws InvalidArgumentException If $key is invalid.
     */
    public function mtime(string $key): int;

    /**
     * Calculate the checksum of a specified file's content.
     *
     * @param string $key The key (path) of the file.
     * @return string An MD5 hash representing the file's content checksum.
     * @throws InvalidArgumentException If $key is invalid.
     */
    public function checksum(string $key): string;

    /**
     * Retrieve the size of a specified file's content.
     *
     * @param string $key The key (path) of the file.
     * @return int The size of the file in bytes.
     * @throws InvalidArgumentException If $key is invalid.
     */
    public function size(string $key): int;

    /**
     * Create a new stream instance for a specified file.
     *
     * @param string $key The key (path) of the file.
     * @return StreamInterface|InMemoryBuffer A stream interface for the file.
     * @throws InvalidArgumentException If $key is invalid.
     */
    public function createStream(string $key): StreamInterface|InMemoryBuffer;

    /**
     * Create a new file in the filesystem.
     *
     * @param string $key The key (path) for the new file.
     * @return File The newly created file object.
     * @throws InvalidArgumentException If $key is invalid.
     */
    public function createFile(string $key): File;

    /**
     * Get the mime type of a specified file.
     *
     * @param string $key The key (path) of the file.
     * @return string The MIME type of the file.
     * @throws InvalidArgumentException If $key is invalid.
     */
    public function mimeType(string $key): string;

    /**
     * Check if the specified key represents a directory.
     *
     * @param string $key The key (path) to check.
     * @return bool TRUE if the key represents a directory, FALSE otherwise.
     */
    public function isDirectory(string $key): bool;
}
