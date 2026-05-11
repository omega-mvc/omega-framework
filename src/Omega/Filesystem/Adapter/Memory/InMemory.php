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

namespace Omega\Filesystem\Adapter\Memory;

use finfo;
use Omega\Filesystem\Adapter\FilesystemAdapterInterface;
use Omega\Filesystem\Contracts\MimeTypeProviderInterface;
use Omega\Filesystem\Util\Size;

use function array_keys;
use function array_key_exists;
use function array_merge;
use function clearstatcache;
use function is_array;
use function time;

/**
 * InMemory Adapter for Filesystem.
 *
 * This class provides an in-memory file storage system, allowing for
 * the temporary storage and management of files. It is primarily used for
 * testing purposes where persistence is not required. All files are stored
 * in memory, making it a lightweight and fast alternative to file-based
 * storage solutions.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Adapter\InMemory
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class InMemory implements
    FilesystemAdapterInterface,
    MimeTypeProviderInterface
{
    /**
     * Constructor for the InMemory adapter.
     *
     * Initializes the in-memory filesystem adapter with an optional array of files.
     * The provided files can be in the form of strings or associative arrays containing
     * 'content' and optional 'mtime' (last modified time). If no files are provided,
     * the adapter will start with an empty file storage.
     *
     * @param array $files An optional array of files to initialize the adapter with.
     *                     Each file can be represented as a string or an associative
     *                     array with 'content' and optional 'mtime'.
     *
     * @return void
     */
    public function __construct(
        protected array $files = []
    ) {
        $this->setFiles($files);
    }

    /**
     * Defines the files stored in memory.
     *
     * @param array $files An array of files to store in memory. Each file can be
     *                     represented as a string or an associative array with
     *                     'content' and optional 'mtime' (modification time).
     * @return void
     */
    public function setFiles(array $files): void
    {
        $this->files = [];
        foreach ($files as $key => $file) {
            if (!is_array($file)) {
                $file = ['content' => $file];
            }

            $file = array_merge([
                'content' => null,
                'mtime'   => null,
            ], $file);

            $this->setFile($key, $file['content'], $file['mtime']);
        }
    }

    /**
     * Defines a single file in memory.
     *
     * @param string      $key     The unique key for the file.
     * @param string|null $content The content of the file.
     * @param int|null    $mtime   The last modified time (automatically set to now if NULL).
     * @return void
     */
    public function setFile(string $key, ?string $content = null, ?int $mtime = null): void
    {
        if (null === $mtime) {
            $mtime = time();
        }

        $this->files[$key] = [
            'content' => (string) $content,
            'mtime'   => (int) $mtime,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|bool
    {
        return $this->files[$key]['content'];
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        $content = $this->read($sourceKey);
        $this->delete($sourceKey);

        return (bool) $this->write($targetKey, $content);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, string $content, array $metadata = null): int|bool
    {
        $this->files[$key]['content'] = $content;
        $this->files[$key]['mtime']   = time();

        return Size::fromContent($content);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->files);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return array_keys($this->files);
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|bool
    {
        return $this->files[$key]['mtime'] ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        unset($this->files[$key]);
        clearstatcache();

        return true;
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
     */
    public function mimeType(string $key): string
    {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);

        return $fileInfo->buffer($this->files[$key]['content']);
    }
}
