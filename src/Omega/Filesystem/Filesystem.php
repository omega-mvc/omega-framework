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

use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Omega\Filesystem\Exception\FileAlreadyExistsException;
use Omega\Filesystem\Exception\FileNotFoundException;
use Omega\Filesystem\Exception\UnexpectedFileExcption;
use Omega\Filesystem\Adapter\FilesystemAdapterInterface;
use Omega\Filesystem\Contracts\ChecksumCalculatorInterface;
use Omega\Filesystem\Contracts\FileFactoryInterface;
use Omega\Filesystem\Contracts\ListKeysAwareInterface;
use Omega\Filesystem\Contracts\MimeTypeProviderInterface;
use Omega\Filesystem\Contracts\SizeCalculatorInterface;
use Omega\Filesystem\Contracts\StreamFactoryInterface;
use Omega\Filesystem\Stream\InMemoryBuffer;
use Omega\Filesystem\Stream\StreamInterface;
use Omega\Filesystem\Util\Checksum;
use Omega\Filesystem\Util\Size;

use function get_class;
use function sprintf;
use function str_starts_with;

/**
 * Class Filesystem.
 *
 * The `Filesystem` class provides a comprehensive interface for managing files and directories within a storage
 * system. It facilitates storing, retrieving, renaming, and deleting files, as well as performing various operations
 * like checking for existence, calculating sizes, checksums, and MIME types. The class is built around an adapter
 * pattern, allowing compatibility with different storage backends through the `FilesystemAdapterInterface`.
 *
 * This class maintains a registry of `File` objects created during its operation, which allows for efficient access
 * and manipulation of files without repeatedly creating new instances. It also provides utility methods to manage
 * file existence and validate keys, ensuring robust error handling and improved maintainability of the filesystem
 * operations.
 *
 * @category    Omega
 * @package     Filesystem
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class Filesystem implements FilesystemInterface
{
    /**
     * File register array.
     *
     * @var array An associative array that stores `File` objects created with
     *            the `createFile()` method. The key is the file key, and
     *            the value is the corresponding `File` instance.
     */
    protected array $fileRegister = [];

    /**
     * Filesystem clss constructor.
     *
     * @param FilesystemAdapterInterface $adapter A configured Adapter instance
     *                                            that implements the required methods to interact with the storage
     *                                            backend.
     * @return void
     */
    public function __construct(
        protected FilesystemAdapterInterface $adapter
    ) {
    }

    /**
     * Returns the adapter instance associated with the filesystem.
     *
     * This method allows access to the underlying adapter, enabling
     * direct interaction with the storage backend if needed.
     *
     * @return FilesystemAdapterInterface The adapter used by the filesystem.
     */
    public function getAdapter(): FilesystemAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        self::assertValidKey($key);

        return $this->adapter->exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        self::assertValidKey($sourceKey);
        self::assertValidKey($targetKey);

        $this->assertHasFile($sourceKey);

        if ($this->has($targetKey)) {
            throw new UnexpectedFileExcption($targetKey);
        }

        if (!$this->adapter->rename($sourceKey, $targetKey)) {
            throw new RuntimeException(
                sprintf(
                    'Could not rename the "%s" key to "%s".',
                    $sourceKey,
                    $targetKey
                )
            );
        }

        if ($this->isFileInRegister($sourceKey)) {
            $this->fileRegister[$targetKey] = $this->fileRegister[$sourceKey];
            unset($this->fileRegister[$sourceKey]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, bool $create = false): File
    {
        self::assertValidKey($key);

        if (!$create) {
            $this->assertHasFile($key);
        }

        return $this->createFile($key);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, string $content, bool $overwrite = false): int
    {
        self::assertValidKey($key);

        if (!$overwrite && $this->has($key)) {
            throw new FileAlreadyExistsException($key);
        }

        $numBytes = $this->adapter->write($key, $content);

        if (false === $numBytes) {
            throw new RuntimeException(
                sprintf(
                    'Could not write the "%s" key content.',
                    $key
                )
            );
        }

        return $numBytes;
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        $content = $this->adapter->read($key);

        if (false === $content) {
            throw new RuntimeException(
                sprintf(
                    'Could not read the "%s" key content.',
                    $key
                )
            );
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        if ($this->adapter->delete($key)) {
            $this->removeFromRegister($key);

            return true;
        }

        throw new RuntimeException(
            sprintf(
                'Could not remove the "%s" key.',
                $key
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return $this->adapter->keys();
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(string $prefix = ''): array
    {
        if ($this->adapter instanceof ListKeysAwareInterface) {
            return $this->adapter->listKeys($prefix);
        }

        $dirs = [];
        $keys = [];

        foreach ($this->keys() as $key) {
            if (empty($prefix) || str_starts_with($key, $prefix)) {
                if ($this->adapter->isDirectory($key)) {
                    $dirs[] = $key;
                } else {
                    $keys[] = $key;
                }
            }
        }

        return [
            'keys' => $keys,
            'dirs' => $dirs,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        return $this->adapter->mtime($key);
    }

    /**
     * {@inheritdoc}
     */
    public function checksum(string $key): string
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        if ($this->adapter instanceof ChecksumCalculatorInterface) {
            return $this->adapter->checksum($key);
        }

        return Checksum::fromContent($this->read($key));
    }

    /**
     * {@inheritdoc}
     */
    public function size(string $key): int
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        if ($this->adapter instanceof SizeCalculatorInterface) {
            return $this->adapter->size($key);
        }

        return Size::fromContent($this->read($key));
    }

    /**
     * {@inheritdoc}
     */
    public function createStream(string $key): StreamInterface|InMemoryBuffer
    {
        self::assertValidKey($key);

        if ($this->adapter instanceof StreamFactoryInterface) {
            return $this->adapter->createStream($key);
        }

        return new InMemoryBuffer($this, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function createFile(string $key): File
    {
        self::assertValidKey($key);

        if (false === $this->isFileInRegister($key)) {
            if ($this->adapter instanceof FileFactoryInterface) {
                $this->fileRegister[$key] = $this->adapter->createFile($key, $this);
            } else {
                $this->fileRegister[$key] = new File($key, $this);
            }
        }

        return $this->fileRegister[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(string $key): string
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        if ($this->adapter instanceof MimeTypeProviderInterface) {
            return $this->adapter->mimeType($key);
        }

        throw new LogicException(
            sprintf(
                'Adapter "%s" cannot provide MIME type',
                get_class($this->adapter)
            )
        );
    }

    /**
     * Checks if a file exists in the filesystem.
     *
     * Throws a FileNotFoundException if the file does not exist.
     *
     * @param string $key The key of the file to check.
     * @return void
     * @throws FileNotFoundException if the file does not exist.
     */
    private function assertHasFile(string $key): void
    {
        if (!$this->has($key)) {
            throw new FileNotFoundException($key);
        }
    }

    /**
     * Checks if a File object is registered.
     *
     * @param string $key The key of the file.
     * @return bool True if the file is registered, false otherwise.
     */
    private function isFileInRegister(string $key): bool
    {
        return array_key_exists($key, $this->fileRegister);
    }

    /**
     * Clears the file register, removing all registered files.
     *
     * @return void
     */
    public function clearFileRegister(): void
    {
        $this->fileRegister = [];
    }

    /**
     * Removes a file from the register.
     *
     * @param string $key The key of the file to remove from the register.
     * @return void
     */
    public function removeFromRegister(string $key): void
    {
        if ($this->isFileInRegister($key)) {
            unset($this->fileRegister[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        return $this->adapter->isDirectory($key);
    }

    /**
     * Validates the given key to ensure it is not empty.
     *
     * @param string $key The key to validate.
     * @return void
     * @throws InvalidArgumentException if the key is empty.
     */
    private static function assertValidKey(string $key): void
    {
        if (empty($key)) {
            throw new InvalidArgumentException(
                'Object path is empty.'
            );
        }
    }
}
