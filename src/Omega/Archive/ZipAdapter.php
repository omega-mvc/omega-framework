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

use ZipArchive;
use RuntimeException;

use function strlen;

/**
 * ZipAdapter class.
 *
 * The ZipAdapter class provides an implementation of the AdapterInterface for handling ZIP archive files.
 * It uses the ZipAdapter class from PHP to open, read, write, delete, and manage files within ZIP archives.
 * The class supports operations like checking if a file exists, retrieving file metadata, and renaming files
 * within the archive. The constructor ensures that the ZIP file is opened successfully, throwing an exception
 * if the file cannot be accessed.
 *
 * @category    Omega
 * @package     Archive
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini. (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class ZipAdapter extends AbstractAdapter
{
    /**
     * ZipArchive instance.
     *
     * @var ZipArchive Holds an instance of built-in ZipArchive class.
     */
    protected ZipArchive $zipArchive;

    /**
     * Constructor that initializes the ZipArchive and opens the specified ZIP file.
     *
     * @param string $zipFile The path to the ZIP file to be opened or created.
     * @throws RuntimeException if the ZIP file cannot be opened.
     */
    public function __construct(
        protected string $zipFile
    ) {
        $this->zipArchive = new ZipArchive();

        if ($this->zipArchive->open($this->zipFile, ZipArchive::CREATE) !== true) {
            throw new RuntimeException("Unable to open the ZIP file '$this->zipFile'.");
        }
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if unable to open file.
     */
    public function open(string $file): void
    {
        if ($this->zipArchive->open($file) !== true) {
            throw new RuntimeException(
                "Unable to open the ZIP file '$file'. Please check if the file exists and is a valid ZIP archive."
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->zipArchive->close();
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if failed to read the key.
     */
    public function read(string $key): string|bool
    {
        $content = $this->zipArchive->getFromName($key);

        if ($content === false) {
            throw new RuntimeException("Failed to read the key '$key' from the ZIP archive.");
        }
        return $content;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if failed to write the key.
     */
    public function write(string $key, string $content): int|bool
    {
        if ($this->zipArchive->addFromString($key, $content) === false) {
            throw new RuntimeException("Failed to write the key '$key' to the ZIP archive.");
        }

        return strlen($content);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return $this->zipArchive->deleteName($key);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return $this->zipArchive->locateName($key) !== false;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the zip archive is not open or is invalid.
     */
    public function keys(): array
    {
        if ($this->zipArchive->status !== ZipArchive::ER_OK) {
            throw new RuntimeException("The ZIP archive is not open or is invalid.");
        }

        $keys = [];
        for ($i = 0; $i < $this->zipArchive->numFiles; ++$i) {
            $name = $this->zipArchive->getNameIndex($i);
            if ($name !== false) {
                $keys[] = $name;
            }
        }

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        return str_ends_with($key, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|bool
    {
        $stat = $this->zipArchive->statName($key);

        return $stat['mtime'] ?? false;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException If any of the following conditions occur:
     * - The source file does not exist.
     * - The target file already exists.
     * - The renaming operation fails for any other reason.
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        if (!$this->zipArchive->locateName($sourceKey)) {
            throw new RuntimeException("The source file '$sourceKey' does not exist in the ZIP archive.");
        }

        if ($this->zipArchive->locateName($targetKey)) {
            throw new RuntimeException("The target file '$targetKey' already exists in the ZIP archive.");
        }

        if (!$this->zipArchive->renameName($sourceKey, $targetKey)) {
            throw new RuntimeException("Failed to rename '$sourceKey' to '$targetKey' in the ZIP archive.");
        }

        return true;
    }
}
