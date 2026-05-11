<?php

/**
 * Part of Omega MVC - Archive Package
 * php version 8.3
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Omega\Archive;

/**
 * AbstractAdapter class.
 *
 * The `AbstractAdapter` class serves as an abstract base for implementing various types of file archive
 * adapters. It defines the essential operations needed for managing files within an archive, including
 * opening, closing, reading, writing, deleting, checking file existence, retrieving file keys, managing
 * directories, and renaming files. This abstract class enforces the implementation of these  operations
 * through abstract methods, ensuring that any concrete adapter class adheres to the required interface
 * for managing file archives.
 *
 * @category    Omega
 * @package     Archive
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function open(string $file): void;

    /**
     * {@inheritdoc}
     */
    abstract public function close(): void;

    /**
     * {@inheritdoc}
     */
    abstract public function read(string $key): string|bool;

    /**
     * {@inheritdoc}
     */
    abstract public function write(string $key, string $content): int|bool;

    /**
     * {@inheritdoc}
     */
    abstract public function delete(string $key): bool;

    /**
     * {@inheritdoc}
     */
    abstract public function exists(string $key): bool;

    /**
     * {@inheritdoc}
     */
    abstract public function keys(): array;

    /**
     * {@inheritdoc}
     */
    abstract public function isDirectory(string $key): bool;

    /**
     * {@inheritdoc}
     */
    abstract public function mtime(string $key): int|bool;

    /**
     * {@inheritdoc}
     */
    abstract public function rename(string $sourceKey, string $targetKey): bool;
}
