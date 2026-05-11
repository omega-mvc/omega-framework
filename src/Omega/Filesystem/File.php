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

use RuntimeException;
use Omega\Filesystem\Contracts\MetadataSupporterInterface;
use Omega\Filesystem\Exception\FileNotFoundException;
use Omega\Filesystem\Stream\StreamInterface;

/**
 * Class File.
 *
 * This class represents a file within a filesystem. It provides methods to manage the file's content,
 * metadata, and existence in the filesystem. The file content is lazily loaded, meaning it will not be
 * retrieved from the filesystem until it is specifically requested. This optimizes performance by avoiding
 * unnecessary file reads.
 *
 * @category    Omega
 * @package     Filesystem
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class File
{
    /**
     * The content of the file. It is lazy-loaded and will be retrieved from the filesystem on first request.
     *
     * @var mixed|null
     */
    protected mixed $content = null;

    /**
     * Metadata associated with the file, stored as an associative array.
     * This is only applicable for adapters that support metadata.
     *
     * @var array|null
     */
    protected ?array $metadata = null;

    /**
     * The human-readable name of the file, usually derived from the end of the key.
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * The size of the file in bytes.
     *
     * @var int
     */
    protected int $size = 0;

    /**
     * The last modified time of the file as a Unix timestamp.
     *
     * @var int|null
     */
    protected ?int $mtime = null;

    /**
     * Constructor to initialize the File object.
     *
     * @param string              $key        The key (path) of the file in the filesystem.
     * @param FilesystemInterface $filesystem The filesystem interface to interact with the filesystem.
     */
    public function __construct(
        protected string $key,
        protected FilesystemInterface $filesystem
    ) {
        $this->name = $key;
    }

    /**
     * Get the key (path) of the file.
     *
     * @return string The key of the file.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Retrieve the content of the file. The content is loaded lazily on the first call.
     *
     * @param array $metadata Optional metadata to be set when reading.
     * @return string The content of the file.
     * @throws FileNotFoundException If the file cannot be found in the filesystem.
     */
    public function getContent(array $metadata = []): string
    {
        if (isset($this->content)) {
            return $this->content;
        }
        $this->setMetadata($metadata);

        return $this->content = $this->filesystem->read($this->key);
    }

    /**
     * Get the name of the file.
     *
     * @return string The name of the file.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the size of the file in bytes.
     *
     * @return int The size of the file.
     */
    public function getSize(): int
    {
        if ($this->size) {
            return $this->size;
        }

        try {
            return $this->size = $this->filesystem->size($this->getKey());
        } catch (FileNotFoundException) {
            // Handle exception silently, return size as 0 if file not found
        }

        return 0;
    }

    /**
     * Get the last modified time of the file.
     *
     * @return int The last modified time as a Unix timestamp.
     */
    public function getMtime(): int
    {
        return $this->mtime = $this->filesystem->mtime($this->key);
    }

    /**
     * Set the size of the file.
     *
     * @param int $size The size of the file in bytes.
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * Set the content of the file. This will also update the size and metadata.
     *
     * @param string $content  The content to be written to the file.
     * @param array  $metadata Optional metadata to be sent when writing.
     * @return int The number of bytes written to the file, or FALSE on failure.
     */
    public function setContent(string $content, array $metadata = []): int
    {
        $this->content = $content;
        $this->setMetadata($metadata);

        return $this->size = $this->filesystem->write($this->key, $this->content, true);
    }

    /**
     * Set the name of the file.
     *
     * @param string $name The new name of the file.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Check if the file exists in the filesystem.
     *
     * @return bool TRUE if the file exists, FALSE otherwise.
     */
    public function exists(): bool
    {
        return $this->filesystem->has($this->key);
    }

    /**
     * Delete the file from the filesystem.
     *
     * @param array $metadata Optional metadata to be sent when deleting.
     * @return bool TRUE on success, FALSE on failure.
     * @throws FileNotFoundException If the file cannot be found.
     * @throws RuntimeException      If the file cannot be deleted.
     */
    public function delete(array $metadata = []): bool
    {
        $this->setMetadata($metadata);

        return $this->filesystem->delete($this->key);
    }

    /**
     * Create a new file stream instance.
     *
     * @return StreamInterface A stream interface for the file.
     */
    public function createStream(): StreamInterface
    {
        return $this->filesystem->createStream($this->key);
    }

    /**
     * Rename the file and move it to a new location.
     *
     * @param string $newKey The new key (path) for the file.
     * @return void
     */
    public function rename(string $newKey): void
    {
        $this->filesystem->rename($this->key, $newKey);

        $this->key = $newKey;
    }

    /**
     * Set the metadata for the file if the filesystem adapter supports it.
     *
     * @param array $metadata The metadata to be set.
     * @return bool TRUE if metadata was set, FALSE otherwise.
     */
    protected function setMetadata(array $metadata): bool
    {
        if ($metadata && $this->supportsMetadata()) {
            $this->filesystem->getAdapter()->setMetadata($this->key, $metadata);

            return true;
        }

        return false;
    }

    /**
     * Check if the filesystem adapter supports metadata.
     *
     * @return bool TRUE if metadata is supported, FALSE otherwise.
     */
    private function supportsMetadata(): bool
    {
        return $this->filesystem->getAdapter() instanceof MetadataSupporterInterface;
    }
}
