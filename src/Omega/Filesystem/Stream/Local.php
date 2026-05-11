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
use RuntimeException;
use Omega\Filesystem\Uti\Path;

use function fclose;
use function feof;
use function fflush;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function is_dir;
use function is_resource;
use function mkdir;
use function sprintf;
use function stat;
use function unlink;

/**
 * Local stream class for file handling.
 *
 * This class provides an interface for reading from and writing to
 * local file streams. It manages the file handle and supports
 * various stream operations such as opening, reading, writing,
 * seeking, and flushing. It also ensures that directories are created
 * as needed when writing to a file.
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
class Local implements StreamInterface
{
    /**
     * @var StreamMode|null The current mode of the stream (e.g., read, write).
     */
    private ?StreamMode $mode = null;

    /**
     * @var mixed The file handle resource used for stream operations.
     */
    private mixed $fileHandle;

    /**
     * Constructs a Local stream instance.
     *
     * @param string $path      The path to the file.
     * @param int    $mkdirMode The mode to use when creating directories (`default is 0755`).
     * @return void
     */
    public function __construct(
        private readonly string $path,
        private readonly int $mkdirMode = 0755
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function open(StreamMode $mode): bool
    {
        $baseDirPath = Path::dirname($this->path);
        if ($mode->allowsWrite() && !is_dir($baseDirPath)) {
            @mkdir($baseDirPath, $this->mkdirMode, true);
        }

        try {
            $fileHandle = @fopen($this->path, $mode->getMode());
        } catch (Exception) {
            $fileHandle = false;
        }

        if (false === $fileHandle) {
            throw new RuntimeException(
                sprintf(
                    'File "%s" cannot be opened',
                    $this->path
                )
            );
        }

        $this->mode       = $mode;
        $this->fileHandle = $fileHandle;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read(int $count): string|false
    {
        if (!$this->fileHandle) {
            return false;
        }

        if (false === $this->mode->allowsRead()) {
            throw new LogicException(
                'The stream does not allow read.'
            );
        }

        return fread($this->fileHandle, $count);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $data): int|false
    {
        if (!$this->fileHandle) {
            return false;
        }

        if (false === $this->mode->allowsWrite()) {
            throw new LogicException(
                'The stream does not allow write.'
            );
        }

        return fwrite($this->fileHandle, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        if (!$this->fileHandle) {
            return false;
        }

        $closed = fclose($this->fileHandle);

        if ($closed) {
            $this->mode       = null;
            $this->fileHandle = null;
        }

        return $closed;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): bool
    {
        if ($this->fileHandle) {
            return fflush($this->fileHandle);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function seek(int $offset, int $whence = SEEK_SET): bool
    {
        if ($this->fileHandle) {
            return 0 === fseek($this->fileHandle, $offset, $whence);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): int|false
    {
        if ($this->fileHandle) {
            return ftell($this->fileHandle);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        if ($this->fileHandle) {
            return feof($this->fileHandle);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stat(): array|false
    {
        if ($this->fileHandle) {
            return fstat($this->fileHandle);
        } elseif (!is_resource($this->fileHandle) && is_dir($this->path)) {
            return stat($this->path);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function cast(int $castAs): mixed
    {
        if ($this->fileHandle) {
            return $this->fileHandle;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(): bool
    {
        if ($this->mode->impliesExistingContentDeletion()) {
            return @unlink($this->path);
        }

        return false;
    }
}
