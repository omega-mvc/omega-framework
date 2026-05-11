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

use Exception;
use LogicException;
use Omega\Filesystem\Filesystem;
use Omega\Filesystem\Util\Size;

use function array_merge;
use function array_values;
use function strlen;
use function str_pad;
use function substr;

/**
 * Class InMemoryBuffer.
 *
 * Implements a stream interface that allows reading from and writing to
 * an in-memory buffer, providing temporary storage for file content
 * managed by a Filesystem instance. It handles synchronization with
 * the underlying filesystem and manages read/write positions.
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
class InMemoryBuffer implements StreamInterface
{
    /**
     * The mode of the stream (read/write).
     *
     * @var StreamMode Holds the mode of the stream (read/write).
     */
    private StreamMode $mode;

    /**
     * The content of the stream as a string.
     *
     * @var string Holds the content of the stream as a string.
     */
    private string $content;

    /**
     * Total number of bytes in the buffer.
     *
     * @var int Holds total number of bytes in the buffer.
     */
    private int $numBytes;

    /**
     * Current read/write position in the buffer.
     *
     * @var int Holds the current read/write position in the buffer.
     */
    private int $position;

    /**
     * Indicates if the buffer content is synchronized with the filesystem.
     *
     * @var bool Indicates if the buffer content is synchronized with the filesystem.
     */
    private bool $synchronized;

    /**
     * InMemoryBuffer constructor.
     *
     * @param Filesystem $filesystem The filesystem managing the file to stream
     * @param string     $key        The file key
     * @return void
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $key
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function open(StreamMode $mode): bool
    {
        $this->mode = $mode;

        $exists = $this->filesystem->has($this->key);

        if (
            ($exists && !$mode->allowsExistingFileOpening())
            || (!$exists && !$mode->allowsNewFileOpening())
        ) {
            return false;
        }

        if ($mode->impliesExistingContentDeletion()) {
            $this->content = $this->writeContent();
        } elseif (!$exists && $mode->allowsNewFileOpening()) {
            $this->content = $this->writeContent();
        } else {
            $this->content = $this->filesystem->read($this->key);
        }

        $this->numBytes = Size::fromContent($this->content);
        $this->position = $mode->impliesPositioningCursorAtTheEnd() ? $this->numBytes : 0;

        $this->synchronized = true;

        return true;
    }

    /**
     * Reads a specified number of bytes from the current position in the buffer.
     *
     * @param int $count The number of bytes to read
     * @return string The data read from the stream
     * @throws LogicException If the stream does not allow reading
     */
    public function read(int $count): string
    {
        if (false === $this->mode->allowsRead()) {
            throw new LogicException(
                'The stream does not allow read.'
            );
        }

        $chunk = substr($this->content, $this->position, $count);
        $this->position += Size::fromContent($chunk);

        return $chunk;
    }

    /**
     * Writes data to the stream at the current position.
     *
     * @param string $data The data to write to the stream
     * @return int The number of bytes written
     * @throws LogicException If the stream does not allow writing
     */
    public function write(string $data): int
    {
        if (false === $this->mode->allowsWrite()) {
            throw new LogicException(
                'The stream does not allow write.'
            );
        }

        $numWrittenBytes = Size::fromContent($data);

        $newPosition = $this->position + $numWrittenBytes;
        $newNumBytes = max($newPosition, $this->numBytes);

        if ($this->eof()) {
            $this->numBytes += $numWrittenBytes;
            if ($this->hasNewContentAtFurtherPosition()) {
                $data = str_pad($data, $this->position + strlen($data), ' ', STR_PAD_LEFT);
            }
            $this->content .= $data;
        } else {
            $before        = substr($this->content, 0, $this->position);
            $after         = $newNumBytes > $newPosition ? substr($this->content, $newPosition) : '';
            $this->content = $before . $data . $after;
        }

        $this->position     = $newPosition;
        $this->numBytes     = $newNumBytes;
        $this->synchronized = false;

        return $numWrittenBytes;
    }

    /**
     * Closes the stream and flushes unsynchronized changes.
     *
     * @return false Always returns false to indicate closure
     */
    public function close(): false
    {
        if (!$this->synchronized) {
            $this->flush();
        }

        return false;
    }

    /**
     * Moves the read/write position to a new location in the buffer.
     *
     * @param int $offset The offset to move the position
     * @param int $whence The reference point for the offset (SEEK_SET, SEEK_CUR, SEEK_END)
     * @return bool True if the seek was successful, false otherwise
     */
    public function seek(int $offset, int $whence = SEEK_SET): bool
    {
        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;

                break;
            case SEEK_CUR:
                $this->position += $offset;

                break;
            case SEEK_END:
                $this->position = $this->numBytes + $offset;

                break;
            default:
                return false;
        }

        return true;
    }

    /**
     * Returns the current read/write position in the buffer.
     *
     * @return int The current position
     */
    public function tell(): int
    {
        return $this->position;
    }

    /**
     * Flushes unsynchronized content from the buffer to the filesystem.
     *
     * @return bool True if the flush was successful, false otherwise
     */
    public function flush(): bool
    {
        if ($this->synchronized) {
            return true;
        }

        try {
            $this->writeContent($this->content);
        } catch (Exception) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the end of the stream has been reached.
     *
     * @return bool True if the end of the stream has been reached, false otherwise
     */
    public function eof(): bool
    {
        return $this->position >= $this->numBytes;
    }

    /**
     * {@inheritdoc}
     */
    public function stat(): array|false
    {
        if ($this->filesystem->has($this->key)) {
            $isDirectory = $this->filesystem->isDirectory($this->key);
            $time        = $this->filesystem->mtime($this->key);

            $stats = [
                'dev'     => 1,
                'ino'     => 0,
                'mode'    => $isDirectory ? 16893 : 33204,
                'nlink'   => 1,
                'uid'     => 0,
                'gid'     => 0,
                'rdev'    => 0,
                'size'    => $isDirectory ? 0 : Size::fromContent($this->content),
                'atime'   => $time,
                'mtime'   => $time,
                'ctime'   => $time,
                'blksize' => -1,
                'blocks'  => -1,
            ];

            return array_merge(array_values($stats), $stats);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function cast(int $castAs): false
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(): bool
    {
        if ($this->mode->impliesExistingContentDeletion()) {
            return $this->filesystem->delete($this->key);
        }

        return false;
    }

    /**
     * Checks if new content exists at a position beyond the current one.
     *
     * @return bool True if there is new content at a further position, false otherwise
     */
    protected function hasNewContentAtFurtherPosition(): bool
    {
        return $this->position > 0 && !$this->content;
    }

    /**
     * Writes content to the filesystem.
     *
     * @param string $content   The content to write (empty string by default)
     * @param bool   $overwrite Indicates whether to overwrite existing content (true by default)
     * @return string The content that was written
     */
    protected function writeContent(string $content = '', bool $overwrite = true): string
    {
        $this->filesystem->write($this->key, $content, $overwrite);

        return $content;
    }
}
