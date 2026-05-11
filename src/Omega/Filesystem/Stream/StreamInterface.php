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

namespace Omega\Filesystem\Stream;

/**
 * Interface StreamInterface.
 *
 * This interface defines the contract for file stream operations,
 * providing methods to manipulate and interact with file streams.
 * Implementations of this interface should handle opening, reading,
 * writing, closing, and managing the state of a stream, allowing for
 * flexible and efficient file handling in the filesystem.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Stream
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
interface StreamInterface
{
    /**
     * Opens the stream in the specified mode.
     *
     * This method should initialize the stream for reading or writing
     * based on the provided mode. If the stream is already open, it may
     * need to be closed first.
     *
     * @param StreamMode $mode The mode in which to open the stream.
     * @return bool TRUE on success, or FALSE on failure.
     */
    public function open(StreamMode $mode): bool;

    /**
     * Reads the specified number of bytes from the current position.
     *
     * If the current position is at the end-of-file, this method must
     * return an empty string.
     *
     * @param int $count The number of bytes to read from the stream.
     * @return string|false The read bytes as a string, or FALSE on failure.
     */
    public function read(int $count): string|false;

    /**
     * Writes the specified data to the stream.
     *
     * This method should update the current position of the stream
     * by the number of bytes that were successfully written. If an error
     * occurs during writing, it should return FALSE.
     *
     * @param string $data The data to write to the stream.
     * @return int|false The number of bytes that were successfully written, or FALSE on failure.
     */
    public function write(string $data): int|false;

    /**
     * Closes the stream.
     *
     * This method must free all resources associated with the stream.
     * If there is any buffered data, it should be flushed to the underlying
     * storage before closing.
     *
     * @return bool TRUE on success, or FALSE on failure.
     */
    public function close(): bool;

    /**
     * Flushes the output buffer.
     *
     * If there is any cached data that has not yet been written to the
     * underlying storage, this method should perform that operation.
     *
     * @return bool TRUE on success, or FALSE on failure.
     */
    public function flush(): bool;

    /**
     * Seeks to the specified offset within the stream.
     *
     * This method should change the current position of the stream to the
     * specified offset according to the whence parameter.
     *
     * @param int $offset The offset to seek to.
     * @param int $whence The reference point for the offset (default is SEEK_SET).
     * @return bool TRUE on success, or FALSE on failure.
     */
    public function seek(int $offset, int $whence = SEEK_SET): bool;

    /**
     * Returns the current position of the stream.
     *
     * @return int|false The current position as an integer, or FALSE on failure.
     */
    public function tell(): int|false;

    /**
     * Indicates whether the current position is at the end-of-file.
     *
     * @return bool TRUE if at end-of-file, FALSE otherwise.
     */
    public function eof(): bool;

    /**
     * Gathers statistics about the stream.
     *
     * This method returns an array containing various statistics about
     * the stream. If the statistics cannot be gathered, it should return FALSE.
     *
     * @return array|false An associative array of stream statistics, or FALSE on failure.
     */
    public function stat(): array|false;

    /**
     * Retrieves the underlying resource associated with the stream.
     *
     * @param int $castAs An optional parameter to cast the resource as a specific type.
     * @return mixed The underlying resource or FALSE on failure.
     */
    public function cast(int $castAs): mixed;

    /**
     * Deletes the file associated with the stream.
     *
     * This method attempts to remove the file from the filesystem.
     *
     * @return bool TRUE on success, or FALSE otherwise.
     */
    public function unlink(): bool;
}
