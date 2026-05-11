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

/**
 * Interface that adds MIME type detection support to an adapter.
 *
 * This interface defines a method for retrieving the MIME type of a file
 * in the filesystem, identified by a specific key. Adapters implementing
 * this interface should provide a way to detect or fetch the MIME type
 * based on the file content or file extension.
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
interface MimeTypeProviderInterface
{
    /**
     * Retrieves the MIME type of the specified file (key).
     *
     * This method returns the MIME type of a file identified by its key.
     * If the MIME type cannot be determined, it should return `false`.
     *
     * @param string $key The file key for which the MIME type is being retrieved.
     * @return string|false The MIME type of the file, or `false` if it cannot be determined.
     */
    public function mimeType(string $key): string|false;
}
