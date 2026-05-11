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

use InvalidArgumentException;

use function extension_loaded;
use function filesize;
use function fstat;
use function mb_strlen;
use function strlen;

/**
 * Class Size.
 *
 * The `Size` class provides utility methods for calculating file sizes
 * in bytes from different types of inputs, such as strings, files, and
 * resources. It supports both multibyte and single-byte character encodings,
 * ensuring accurate size calculations even for various types of content.
 * This class is useful for applications that need to handle and manipulate
 * file sizes efficiently.
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
class Size
{
    /**
     * Returns the size in bytes of the given content.
     *
     * This method calculates the byte size of a string. If the `mbstring`
     * extension is loaded, it uses `mb_strlen` with the '8bit' encoding
     * to ensure accurate byte measurement for multibyte characters.
     * Otherwise, it falls back to `strlen`.
     *
     * @param string $content The content whose size is to be calculated.
     * @return int The size of the content in bytes.
     */
    public static function fromContent(string $content): int
    {
        if (!extension_loaded('mbstring')) {
            return strlen($content);
        }

        return mb_strlen($content, '8bit');
    }

    /**
     * Returns the size in bytes of the given file.
     *
     * This method retrieves the size of a file specified by its filename.
     * If the file does not exist or an error occurs, it returns false.
     *
     * @param string $filename The path to the file whose size is to be calculated.
     * @return int The size of the file in bytes.
     * @throws InvalidArgumentException if the file does not exist.
     */
    public static function fromFile(string $filename): int
    {
        return filesize($filename);
    }

    /**
     * Returns the size in bytes from the given resource.
     *
     * This method calculates the size of a resource using `fstat`. If the
     * resource points to a remote file, the size will be returned as 0.
     * It handles both local and remote resources effectively.
     *
     * @param resource $handle The resource whose size is to be calculated.
     * @return string|int The size of the resource in bytes, or 0 if the size is unavailable.
     */
    public static function fromResource(mixed $handle): string|int
    {
        $cStat = fstat($handle);

        return $cStat ? $cStat['size'] : 0;
    }
}
