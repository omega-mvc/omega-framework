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

namespace Omega\Filesystem\Util;

use RuntimeException;

use function md5;
use function md5_file;

/**
 * Class Checksum.
 *
 * The `Checksum` class provides utility methods for calculating
 * MD5 checksums for both strings and files. This class is
 * designed to facilitate the verification of data integrity by
 * allowing users to generate checksums that can be used to
 * identify changes or ensure the consistency of file contents.
 * It encapsulates the functionality to compute checksums in a
 * straightforward manner, making it easy to use in various
 * applications dealing with file storage and data management.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Util
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class Checksum
{
    /**
     * Calculates the MD5 checksum of the provided content.
     *
     * This method takes a string as input and computes its MD5
     * checksum. The resulting checksum can be used to verify
     * data integrity or detect changes in the content.
     *
     * @param string $content The content for which to calculate the checksum.
     * @return string The MD5 checksum of the given content.
     */
    public static function fromContent(string $content): string
    {
        return md5($content);
    }

    /**
     * Calculates the MD5 checksum of the specified file.
     *
     * This method reads the specified file and computes its MD5
     * checksum. The checksum can be used for file integrity checks
     * to ensure that the content has not been altered since the
     * checksum was generated.
     *
     * @param string $filename The path to the file for which to calculate the checksum.
     * @return string The MD5 checksum of the specified file.
     * @throws RuntimeException If the file cannot be read.
     */
    public static function fromFile(string $filename): string
    {
        return md5_file($filename);
    }
}
