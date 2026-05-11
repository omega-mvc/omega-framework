<?php

/**
 * Part of Omega - Filesystem Package.
 * php verion 8.2
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Omega\Filesystem\Stream;

use InvalidArgumentException;
use RuntimeException;
use Omega\Filesystem\Filesystem;

use function array_merge;
use function in_array;
use function parse_url;
use function sprintf;
use function stream_get_wrappers;
use function stream_wrapper_register;
use function stream_wrapper_unregister;
use function substr;

/**
 * Stream wrapper class for the Omega filesystem.
 *
 * Provides an abstraction layer to interact with streams using Omega's filesystem.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Stream
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class StreamWrapper
{
    /**
     * The map of filesystems used by the stream wrapper.
     *
     * @var FilesystemMap Holds the map of filesystems used by the stream wrapper.
     */
    private static FilesystemMap $filesystemMap;

    /**
     * The current stream being manipulated by this wrapper.
     *
     * @var StreamInterface Holds the current stream being manipulated by this wrapper.
     */
    private StreamInterface $stream;

    /**
     * Sets the filesystem map instance.
     *
     * @param FilesystemMap $map The filesystem map to be used.
     * @return void
     */
    public static function setFilesystemMap(FilesystemMap $map): void
    {
        self::$filesystemMap = $map;
    }

    /**
     * Retrieves the filesystem map instance.
     *
     * @return FilesystemMap The current filesystem map.
     */
    public static function getFilesystemMap(): FilesystemMap
    {
        if (null === self::$filesystemMap) {
            self::$filesystemMap = self::createFilesystemMap();
        }

        return self::$filesystemMap;
    }

    /**
     * Registers the stream wrapper for a given scheme.
     *
     * @param string $scheme The protocol scheme to register (default: 'omega').
     * @return void
     * @throws RuntimeException If the stream wrapper registration fails.
     */
    public static function register(string $scheme = 'omega'): void
    {
        self::streamWrapperUnregister($scheme);

        if (!self::streamWrapperRegister($scheme, __CLASS__)) {
            throw new RuntimeException(
                sprintf(
                    'Could not register stream wrapper class %s for scheme %s.',
                    __CLASS__,
                    $scheme
                )
            );
        }
    }

    /**
     * Creates a new instance of the filesystem map.
     *
     * @return FilesystemMap A new filesystem map instance.
     */
    protected static function createFilesystemMap(): FilesystemMap
    {
        return new FilesystemMap();
    }

    /**
     * Unregisters a stream wrapper for the given scheme.
     *
     * @param string $scheme The protocol scheme to unregister.
     * @return bool True if the scheme was unregistered, false otherwise.
     */
    protected static function streamWrapperUnregister(string $scheme): bool
    {
        if (in_array($scheme, stream_get_wrappers())) {
            return stream_wrapper_unregister($scheme);
        }

        return false;
    }

    /**
     * Registers a stream wrapper class for the given scheme.
     *
     * @param string $scheme The protocol scheme.
     * @param string $className The class name implementing the stream wrapper.
     * @return bool True if registration was successful, false otherwise.
     */
    protected static function streamWrapperRegister(string $scheme, string $className): bool
    {
        return stream_wrapper_register($scheme, $className);
    }

    /**
     * Opens a stream for the specified path and mode.
     *
     * @param string $path The resource path to open.
     * @param string $mode The file mode (e.g., 'r', 'w', 'a').
     * @return bool True if the stream was opened successfully, false on failure.
     */
    public function stream_open(string $path, string $mode): bool
    {
        $this->stream = $this->createStream($path);

        return $this->stream->open($this->createStreamMode($mode));
    }

    /**
     * Reads data from the stream.
     *
     * @param int $bytes Number of bytes to read.
     * @return string|false The read data, or false on failure.
     */
    public function stream_read(int $bytes): string|false
    {
        return $this->stream->read($bytes);
    }

    /**
     * Writes data to the stream.
     *
     * @param string $data The data to write.
     * @return int The number of bytes written.
     */
    public function stream_write(string $data): int
    {
        return $this->stream->write($data);
    }

    /**
     * Closes the stream.
     *
     * @return void
     */
    public function stream_close(): void
    {
        $this->stream->close();
    }

    /**
     * Flushes the stream buffer.
     *
     * @return bool True on success, false on failure.
     */
    public function stream_flush(): bool
    {
        return $this->stream->flush();
    }

    /**
     * Moves the stream pointer to a new position.
     *
     * @param int $offset The new position.
     * @param int $whence The reference position (SEEK_SET, SEEK_CUR, SEEK_END).
     * @return bool True on success, false on failure.
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return $this->stream->seek($offset, $whence);
    }

    /**
     * Retrieves the current position of the stream pointer.
     *
     * @return int The current position.
     */
    public function stream_tell(): int
    {
        return $this->stream->tell();
    }

    /**
     * Checks if the end of the stream has been reached.
     *
     * @return bool True if EOF is reached, false otherwise.
     */
    public function stream_eof(): bool
    {
        return $this->stream->eof();
    }

    /**
     * Retrieves stream metadata.
     *
     * @return array|false An array of metadata or false on failure.
     */
    public function stream_stat(): array|false
    {
        return $this->stream->stat();
    }

    /**
     * Retrieves metadata about a URL-based stream.
     *
     * @param string $path The resource path.
     * @param int $flags URL stat flags.
     * @return array|false Stream metadata or false on failure.
     */
    public function url_stat(string $path, int $flags): array|false
    {
        $stream = $this->createStream($path);

        $suppressErrors = ($flags & STREAM_URL_STAT_QUIET) === STREAM_URL_STAT_QUIET;

        try {
            if (($flags & STREAM_URL_STAT_LINK) !== STREAM_URL_STAT_LINK) {
                $stream->open($this->createStreamMode('r+'));
            }
        } catch (RuntimeException $e) {
            if (!$suppressErrors) {
                throw $e;
            }
        }

        return $stream->stat();
    }

    /**
     * Deletes a resource at the given path.
     *
     * @param string $path The resource path.
     * @return bool True on success, false on failure.
     */
    public function unlink(string $path): bool
    {
        $stream = $this->createStream($path);

        try {
            $stream->open($this->createStreamMode('w+'));
        } catch (RuntimeException) {
            return false;
        }

        return $stream->unlink();
    }

    /**
     * Casts the stream to a different resource type.
     *
     * @param int $castAs The cast mode (e.g., STREAM_CAST_AS_STREAM).
     * @return mixed The underlying stream resource or false on failure.
     */
    public function stream_cast(int $castAs): mixed
    {
        return $this->stream->cast($castAs);
    }

    /**
     * Creates a stream instance for the given path.
     *
     * @param string $path The resource path.
     * @return StreamInterface The created stream.
     * @throws InvalidArgumentException If the path is invalid.
     */
    protected function createStream(string $path): StreamInterface
    {
        $parts = array_merge(
            [
                'scheme'   => null,
                'host'     => null,
                'path'     => null,
                'query'    => null,
                'fragment' => null,
            ],
            parse_url($path) ?: []
        );

        $domain = $parts['host'];
        $key    = !empty($parts['path']) ? substr($parts['path'], 1) : '';

        if (null !== $parts['query']) {
            $key .= '?' . $parts['query'];
        }

        if (null !== $parts['fragment']) {
            $key .= '#' . $parts['fragment'];
        }

        if (empty($domain) || empty($key)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The specified path (%s) is invalid.',
                    $path
                )
            );
        }

        return self::getFilesystemMap()->get($domain)->createStream($key);
    }

    /**
     * Creates a StreamMode instance for the given mode.
     *
     * @param string $mode The file mode (e.g., 'r', 'w', 'a').
     * @return StreamMode The created StreamMode instance.
     */
    protected function createStreamMode(string $mode): StreamMode
    {
        return new StreamMode($mode);
    }
}
