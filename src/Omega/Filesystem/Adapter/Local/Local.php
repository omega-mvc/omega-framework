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

namespace Omega\Filesystem\Adapter\Local;

use EmptyIterator;
use Exception;
use FilesystemIterator;
use finfo;
use InvalidArgumentException;
use OutOfBoundsException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Omega\Filesystem\Adapter\FilesystemAdapterInterface;
use Omega\Filesystem\Contracts\ChecksumCalculatorInterface;
use Omega\Filesystem\Contracts\MimeTypeProviderInterface;
use Omega\Filesystem\Contracts\SizeCalculatorInterface;
use Omega\Filesystem\Contracts\StreamFactoryInterface;
use Omega\Filesystem\Stream\Local as LocalStream;
use Omega\Filesystem\Util\Checksum;
use Omega\Filesystem\Util\Path;
use Omega\Filesystem\Util\Size;

use function file_get_contents;
use function file_exists;
use function filemtime;
use function file_put_contents;
use function is_dir;
use function is_file;
use function is_link;
use function ltrim;
use function mkdir;
use function realpath;
use function rename;
use function rmdir;
use function sort;
use function sprintf;
use function strlen;
use function str_starts_with;
use function strval;
use function substr;
use function unlink;

/**
 * Adapter for the local filesystem.
 *
 * This class provides an interface to interact with the local filesystem.
 * It allows reading, writing, deleting, and renaming files and directories
 * within a specified base directory. It also supports stream creation,
 * checksum calculation, size retrieval, and MIME type detection for files.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Adapter\Local
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class Local implements
    FilesystemAdapterInterface,
    StreamFactoryInterface,
    ChecksumCalculatorInterface,
    SizeCalculatorInterface,
    MimeTypeProviderInterface
{
    /**
     * The base directory where the filesystem operations are performed.
     *
     * @var string Holds the base directory where the fiesystem operation are performed,
     */
    protected string $directory;

    /**
     * Constructs a new Local filesystem adapter instance.
     *
     * @param string $directory Directory where the filesystem is located.
     * @param bool   $create    Whether to create the directory if it does not
     *                          exist (default FALSE).
     * @param int    $mode      Mode for mkdir.
     *
     * @return void
     *
     * @throws RuntimeException if the specified directory does not exist and
     *                          could not be created.
     */
    public function __construct(
        string $directory,
        private readonly bool $create = false,
        private readonly int $mode = 0777
    ) {
        $this->directory = Path::normalize($directory);

        if (is_link($this->directory)) {
            $this->directory = realpath($this->directory);
        }
    }

    /**
     * {@inheritdoc}
     * @throws OutOfBoundsException     If the computed path is out of the directory
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    public function read(string $key): string|bool
    {
        if ($this->isDirectory($key)) {
            return false;
        }

        return file_get_contents($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     * @throws OutOfBoundsException     If the computed path is out of the directory
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    public function write(string $key, string $content): int|bool
    {
        $path = $this->computePath($key);
        $this->ensureDirectoryExists(Path::dirname($path), true);

        return file_put_contents($path, $content);
    }

    /**
     * {@inheritdoc}
     * @throws OutOfBoundsException     If the computed path is out of the directory
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        $targetPath = $this->computePath($targetKey);
        $this->ensureDirectoryExists(Path::dirname($targetPath), true);

        return rename($this->computePath($sourceKey), $targetPath);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return is_file($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     * @throws OutOfBoundsException     If the computed path is out of the directory
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    public function keys(): array
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->directory,
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            );
        } catch (Exception) {
            $files = new EmptyIterator();
        }

        $keys = [];
        foreach ($files as $file) {
            $keys[] = $this->computeKey($file);
        }
        sort($keys);

        return $keys;
    }

    /**
     * {@inheritdoc}
     * @throws OutOfBoundsException     If the computed path is out of the directory
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    public function mtime(string $key): int|bool
    {
        return filemtime($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        if ($this->isDirectory($key)) {
            return $this->deleteDirectory($this->computePath($key));
        } elseif ($this->exists($key)) {
            return unlink($this->computePath($key));
        }

        return false;
    }

    /**
     * {@inheritdoc}
     * @throws OutOfBoundsException     If the computed path is out of the directory
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    public function isDirectory(string $key): bool
    {
        return is_dir($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     * @throws OutOfBoundsException     If the computed path is out of the directory
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    public function createStream($key): LocalStream
    {
        return new LocalStream($this->computePath($key), $this->mode);
    }

    /**
     * {@inheritdoc}
     * @throws OutOfBoundsException     If the computed path is out of the directory
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    public function checksum(string $key): string
    {
        return Checksum::fromFile($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     * @throws OutOfBoundsException     If the computed path is out of the directory
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    public function size(string $key): int
    {
        return Size::fromFile($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     * @throws OutOfBoundsException     If the computed path is out of the directory
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    public function mimeType(string $key): string
    {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);

        return $fileInfo->file($this->computePath($key));
    }

    /**
     * Computes the key from the specified path.
     *
     * @param string $path The path to compute the key from.
     * @return string The computed key.
     */
    public function computeKey(string $path): string
    {
        $path = $this->normalizePath($path);

        return ltrim(substr($path, strlen($this->directory)), '/');
    }

    /**
     * Computes the full path from the specified key.
     *
     * @param string $key The key to compute the path from.
     * @return string The computed path.
     * @throws OutOfBoundsException If the computed path is out of the directory.
     */
    protected function computePath(string $key): string
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        return $this->normalizePath($this->directory . '/' . $key);
    }

    /**
     * Normalizes the given path.
     *
     * @param string $path
     * @return string
     * @throws OutOfBoundsException If the computed path is out of the
     *                              directory
     */
    protected function normalizePath(string $path): string
    {
        $path = Path::normalize($path);

        if (!str_starts_with($path, $this->directory)) {
            throw new OutOfBoundsException(
                sprintf(
                    'The path "%s" is out of the filesystem.',
                    $path
                )
            );
        }

        return $path;
    }

    /**
     * Ensures that the specified directory exists.
     *
     * @param string $directory The directory path to check.
     * @param bool   $create    Whether to create the directory if it does not exist.
     * @return void
     * @throws RuntimeException if the directory could not be created.
     */
    protected function ensureDirectoryExists(string $directory, bool $create = false): void
    {
        if (!is_dir($directory)) {
            if (!$create) {
                throw new RuntimeException(
                    sprintf(
                        'The directory "%s" does not exist.',
                        $directory
                    )
                );
            }

            $this->createDirectory($directory);
        }
    }

    /**
     * Creates the specified directory and its parents.
     *
     * @param string $directory Path of the directory to create
     * @return void
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    protected function createDirectory(string $directory): void
    {
        if (!@mkdir($directory, $this->mode, true) && !is_dir($directory)) {
            throw new RuntimeException(
                sprintf(
                    'The directory \'%s\' could not be created.',
                    $directory
                )
            );
        }
    }

    /**
     * Deletes a directory and its contents recursively.
     *
     * @param string $directory The directory to delete.
     * @return bool True if the deletion was successful, false otherwise.
     */
    private function deleteDirectory(string $directory): bool
    {
        if ($this->directory === $directory) {
            throw new InvalidArgumentException(
                sprintf(
                    'Impossible to delete the root directory of this Local adapter ("%s").',
                    $directory
                )
            );
        }

        $status = true;

        if (file_exists($directory)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $directory,
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $status = $status && rmdir(strval($item));
                } else {
                    $status = $status && unlink(strval($item));
                }
            }

            $status = $status && rmdir($directory);
        }

        return $status;
    }
}
