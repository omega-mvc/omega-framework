<?php

/**
 * Part of Omega MVC - Archive Package
 * php version 8.3
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini. (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Omega\Archive;

use RuntimeException;

use function bzcompress;
use function bzdecompress;
use function file_exists;
use function file_get_contents;
use function filemtime;
use function file_put_contents;
use function is_string;
use function rename;
use function sprintf;
use function strlen;

/**
 * Bz2Adapter class.
 *
 * This class implements the `AdapterInterface` for handling bz2 (bzip2) compressed archive files.
 * It provides methods to open, close, read, write, check existence, and rename bz2 files.
 * Some methods such as delete, keys, and isDirectory are not supported by bz2 and will require
 * external handling. This class is useful for managing single bz2 files that contain compressed
 * content.
 *
 * @category    Omega
 * @package     Archive
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini. (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class Bz2Adapter extends AbstractAdapter
{
    /**
     * Bz2Adapter constructor.
     *
     * Initializes the Bz2Adapter class with the path to the bz2 archive file.
     *
     * @param string $bz2File The path to the bz2 file to be used for the archive operations.
     * @return void
     */
    public function __construct(
        protected string $bz2File
    ) {
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the file not exists or is not readable.
     */
    public function open(string $file): void
    {
        if (!file_exists($file)) {
            throw new RuntimeException(sprintf("The file %s does not exist.", $file));
        }

        if (!is_readable($file)) {
            throw new RuntimeException(sprintf("The file %s is not readable.", $file));
        }

        $this->bz2File = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the file is corrupted.
     */
    public function read(string $key): string|bool
    {
        $fileContent = file_get_contents($this->bz2File);

        if ($fileContent === false) {
            throw new RuntimeException(sprintf("Failed to read the file %s.", $this->bz2File));
        }

        $content = @bzdecompress($fileContent);

        return ($content !== false && is_string($content)) ? $content : false;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if compression fails or if the file cannot be written.
     */
    public function write(string $key, string $content): int|bool
    {
        $compressedContent = bzcompress($content);

        if (!is_string($compressedContent) || $compressedContent === '') {
            throw new RuntimeException(sprintf("Failed to compress content for %s.", $this->bz2File));
        }

        if (file_put_contents($this->bz2File, $compressedContent) === false) {
            throw new RuntimeException(sprintf("Failed to write to the file %s.", $this->bz2File));
        }

        return strlen($compressedContent);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return file_exists($this->bz2File);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if failed to retrieve the modification time.
     */
    public function mtime(string $key): int|bool
    {
        $mtime = filemtime($this->bz2File);

        if ($mtime === false) {
            throw new RuntimeException(sprintf("Failed to retrieve the modification time for %s.", $this->bz2File));
        }

        return $mtime;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException If any of the following conditions occur:
     * - The source file does not exist.
     * - The target file already exists.
     * - The source file is not readable.
     * - The directory of the target file is not writable.
     * - The renaming operation fails for any other reason.
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        if (!file_exists($sourceKey)) {
            throw new RuntimeException(sprintf("Source file %s does not exist.", $sourceKey));
        }

        if (file_exists($targetKey)) {
            throw new RuntimeException(sprintf("Target file %s already exists.", $targetKey));
        }

        if (!is_readable($sourceKey)) {
            throw new RuntimeException(sprintf("Source file %s is not readable.", $sourceKey));
        }

        $targetDir = dirname($targetKey);
        if ($targetDir === '' || !is_writable($targetDir)) {
            throw new RuntimeException(sprintf("Cannot write to the directory of the target file %s.", $targetKey));
        }

        if (!rename($sourceKey, $targetKey)) {
            throw new RuntimeException(sprintf("Failed to rename %s to %s.", $sourceKey, $targetKey));
        }

        return true;
    }
}
