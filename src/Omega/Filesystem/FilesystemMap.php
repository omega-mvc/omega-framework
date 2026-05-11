<?php

/**
 * Part of Omega - Filesystem Package.
 *
 * @see       https://omegamvc.github.io
 *
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 */

/*
 * @declare
 */
declare(strict_types=1);

/**
 * @namespace
 */

namespace Omega\Filesystem;

/*
 * @use
 */
use function preg_match;
use function sprintf;
use InvalidArgumentException;

/**
 * Class FilesystemMap.
 *
 * The `FilesystemMap` class implements the `FilesystemMapInterface`
 * and serves as a registry for associating filesystem instances with
 * their respective names. It provides methods to register, retrieve,
 * check the existence of, and remove filesystems by name,
 * enabling a structured and manageable way to handle multiple
 * filesystem implementations.
 * This class ensures that the names of the filesystems follow a
 * specific format, preventing the use of invalid characters.
 *
 * It is particularly useful in scenarios where multiple filesystems
 * are utilized, such as local storage, cloud storage, or remote
 * filesystems, allowing easy access and management of these
 * resources through a common interface.
 *
 * @category    Omega
 * @package     Filesystem
 *
 * @see        https://omegamvc.github.io
 *
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 *
 * @version     1.0.0
 */
class FilesystemMap implements FilesystemMapInterface
{
    /**
     *  An associative array that stores the registered filesystems.
     *
     * @var array Holds an associarive array that stores registered filesystem, where the key is the name of the
     *            filesystem and the value is the instance of the filesystem.
     */
    private array $filesystems = [];

    /**
     * Get all the registered filesystems.
     *
     * Each entry in the array has the filesystem name as the key
     * and the corresponding filesystem instance as the value.
     *
     * @return array Return an array of registered filesystem.
     */
    public function all(): array
    {
        return $this->filesystems;
    }

    /**
     * Registers the given filesystem for the specified name.
     *
     * This method associates a filesystem instance with a name
     * in the registry. The name must contain only valid characters
     * (letters, numbers, hyphens, and underscores) to ensure
     * proper identification of the filesystem.
     *
     * @param string              $name       The name to associate with the filesystem.
     * @param FilesystemInterface $filesystem The filesystem instance to register.
     * @return void
     * @throws InvalidArgumentException When the specified name contains forbidden characters.
     */
    public function set(string $name, FilesystemInterface $filesystem): void
    {
        if (!preg_match('/^[-_a-zA-Z0-9]+$/', $name)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The specified name "%s" is not valid.',
                    $name
                )
            );
        }

        $this->filesystems[$name] = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return isset($this->filesystems[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): FilesystemInterface
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(
                sprintf(
                    'There is no filesystem defined having "%s" name.',
                    $name
                )
            );
        }

        return $this->filesystems[$name];
    }

    /**
     * Removes the filesystem registered for the specified name.
     *
     * This method allows for the removal of a filesystem from
     * the registry by its name. It throws an exception if the
     * filesystem is not found in the registry.
     *
     * @param string $name The name of the filesystem to remove.
     * @return void
     *
     * @throws InvalidArgumentException When the specified filesystem is not defined.
     */
    public function remove(string $name): void
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot remove the "%s" filesystem as it is not defined.',
                    $name
                )
            );
        }

        unset($this->filesystems[$name]);
    }

    /**
     * Clears all the registered filesystems.
     *
     * This method removes all entries from the filesystem registry,
     * effectively resetting it to an empty state.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->filesystems = [];
    }
}
