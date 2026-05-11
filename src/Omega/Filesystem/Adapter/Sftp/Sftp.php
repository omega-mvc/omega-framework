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

namespace Omega\Filesystem\Adapter\Sftp;

use LogicException;
use RuntimeException;
use phpseclib\Net\SFTP as SecLibSFTP;
use Omega\Filesystem\Adapter\FilesystemAdapterInterface;
use Omega\Filesystem\Contracts\FileFactoryInterface;
use Omega\Filesystem\Contracts\ListKeysAwareInterface;
use Omega\Filesystem\File;
use Omega\Filesystem\Filesystem;
use Omega\Filesystem\Util\Path;

use function array_merge;
use function array_merge_recursive;
use function class_exists;
use function ltrim;
use function preg_match;
use function rtrim;
use function str_starts_with;

/**
 * Sftp Adapter.
 *
 * This class provides an interface for interacting with a remote SFTP server.
 * It implements methods for reading, writing, renaming, deleting files, and managing directories.
 * The adapter can be initialized with a remote directory and offers options to create it if it does not exist.
 * This class requires the "phpseclib/phpseclib" package for SFTP functionality.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Adapter\Sftp
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class Sftp implements
    FilesystemAdapterInterface,
    FileFactoryInterface,
    ListKeysAwareInterface
{
    /**
     * Indicates if the adapter has been initialized.
     *
     * @var bool Indicates if the adapter has been initialized.
     */
    protected bool $initialized = false;

    /**
     * Constructor for the Sftp adapter.
     *
     * Initializes an instance of the Sftp adapter with the provided SFTP connection,
     * remote directory, and option to create the directory if it does not exist.
     *
     * @param SecLibSFTP  $sftp      An Sftp instance for SFTP operations.
     * @param string|null $directory The remote directory to use.
     * @param bool        $create    Whether to create the remote directory if it does not exist.
     * @return void
     * @throws LogicException if the SecLibSFTP class is not available.
     */
    public function __construct(
        protected SecLibSFTP $sftp,
        protected ?string $directory = null,
        protected bool $create = false
    ) {
        if (!class_exists(SecLibSFTP::class)) {
            throw new LogicException(
                'You need to install package "phpseclib/phpseclib" to use this adapter'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|bool
    {
        return $this->sftp->get($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        $this->initialize();

        $sourcePath = $this->computePath($sourceKey);
        $targetPath = $this->computePath($targetKey);

        $this->ensureDirectoryExists(Path::dirname($targetPath), true);

        return $this->sftp->rename($sourcePath, $targetPath);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, string $content): int|bool
    {
        $this->initialize();

        $path = $this->computePath($key);
        $this->ensureDirectoryExists(Path::dirname($path), true);
        if ($this->sftp->put($path, $content)) {
            return $this->sftp->size($path);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        $this->initialize();

        return false !== $this->sftp->stat($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        $this->initialize();

        $pwd = $this->sftp->pwd();
        if ($this->sftp->chdir($this->computePath($key))) {
            $this->sftp->chdir($pwd);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        $keys = $this->fetchKeys();

        return $keys['keys'];
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(string $prefix = ''): array
    {
        preg_match('/(.*?)[^\/]*$/', $prefix, $match);
        $directory = rtrim($match[1], '/');

        $keys = $this->fetchKeys($directory, false);

        if ($directory === $prefix) {
            return $keys;
        }

        $filteredKeys = [];
        foreach (['keys', 'dirs'] as $hash) {
            $filteredKeys[$hash] = [];
            foreach ($keys[$hash] as $key) {
                if (str_starts_with($key, $prefix)) {
                    $filteredKeys[$hash][] = $key;
                }
            }
        }

        return $filteredKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|bool
    {
        $this->initialize();

        $stat = $this->sftp->stat($this->computePath($key));

        return $stat['mtime'] ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return $this->sftp->delete($this->computePath($key), false);
    }

    /**
     * {@inheritdoc}
     */
    public function createFile(string $key, Filesystem $filesystem): File
    {
        $file = new File($key, $filesystem);

        $stat = $this->sftp->stat($this->computePath($key));
        if (isset($stat['size'])) {
            $file->setSize($stat['size']);
        }

        return $file;
    }

    /**
     * Initializes the SFTP adapter.
     *
     * Ensures the root directory exists and sets the initialized flag to true.
     *
     * @return void
     */
    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->ensureDirectoryExists($this->directory, $this->create);

        $this->initialized = true;
    }

    /**
     * Ensures that the specified directory exists.
     *
     * If the directory does not exist and $create is true, it will attempt to create it.
     *
     * @param string $directory The directory to check.
     * @param bool   $create    Whether to create the directory if it does not exist.
     * @return void
     * @throws RuntimeException if the directory does not exist and cannot be created.
     */
    protected function ensureDirectoryExists(string $directory, bool $create): void
    {
        $pwd = $this->sftp->pwd();
        if ($this->sftp->chdir($directory)) {
            $this->sftp->chdir($pwd);
        } elseif ($create) {
            if (!$this->sftp->mkdir($directory, 0777, true)) {
                throw new RuntimeException(
                    sprintf(
                        'The directory \'%s\' does not exist and could not be created (%s).',
                        $this->directory,
                        $this->sftp->getLastSFTPError()
                    )
                );
            }
        } else {
            throw new RuntimeException(
                sprintf(
                    'The directory \'%s\' does not exist.',
                    $this->directory
                )
            );
        }
    }

    /**
     * Computes the full path for a given key.
     *
     * @param string $key The key for which to compute the path.
     * @return string The full path to the file or directory.
     */
    protected function computePath(string $key): string
    {
        return $this->directory . '/' . ltrim($key, '/');
    }

    /**
     * Fetches keys and directories from the specified directory.
     *
     * @param string $directory The directory to fetch keys from.
     * @param bool   $onlyKeys  Whether to only fetch file keys.
     * @return array An associative array containing keys and directories.
     */
    protected function fetchKeys(string $directory = '', bool $onlyKeys = true): array
    {
        $keys         = ['keys' => [], 'dirs' => []];
        $computedPath = $this->computePath($directory);

        if (!$this->sftp->file_exists($computedPath)) {
            return $keys;
        }

        $list = $this->sftp->rawlist($computedPath);
        foreach ((array) $list as $filename => $stat) {
            if ('.' === $filename || '..' === $filename) {
                continue;
            }

            $path = ltrim($directory . '/' . $filename, '/');
            if (isset($stat['type']) && $stat['type'] === NET_SFTP_TYPE_DIRECTORY) {
                $keys['dirs'][] = $path;
            } else {
                $keys['keys'][] = $path;
            }
        }

        $dirs = $keys['dirs'];

        if ($onlyKeys && !empty($dirs)) {
            $keys['keys'] = array_merge($keys['keys'], $dirs);
            $keys['dirs'] = [];
        }

        foreach ($dirs as $dir) {
            $keys = array_merge_recursive($keys, $this->fetchKeys($dir, $onlyKeys));
        }

        return $keys;
    }
}
