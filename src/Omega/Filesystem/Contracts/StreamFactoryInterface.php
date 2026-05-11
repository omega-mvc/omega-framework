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

namespace Omega\Filesystem\Contracts;

use Omega\Filesystem\Stream\StreamInterface;

/**
 * Interface for the stream creation class.
 *
 * This interface defines a contract for creating stream instances associated with a file
 * in the filesystem. Implementations of this interface are responsible for returning a stream
 * that can be used for reading, writing, and manipulating the content of the specified file.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Contracts
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
interface StreamFactoryInterface
{
    /**
     * Creates a new stream instance for the specified file.
     *
     * This method creates and returns a stream object for the file identified by its key.
     * The stream can be used for reading, writing, and other file operations. The method
     * should return an object that implements the `StreamInterface`.
     *
     * @param string $key The key or path of the file for which the stream is created.
     * @return StreamInterface The stream instance associated with the specified file.
     */
    public function createStream(string $key): StreamInterface;
}
