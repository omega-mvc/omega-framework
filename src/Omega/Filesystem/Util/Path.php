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

use FilesystemIterator;

use function dirname;
use function implode;
use function preg_match;
use function strlen;
use function str_replace;
use function strtolower;
use function substr;

/**
 * Class Path.
 *
 * The `Path` class provides utility methods for handling and manipulating
 * file system paths in a consistent manner. It includes functionalities
 * for normalizing paths, checking if a path is absolute, and extracting
 * the directory name from a path. This class is designed to facilitate
 * operations on paths in a cross-platform manner, ensuring that
 * path manipulations conform to UNIX-style conventions.
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
class Path
{
    /**
     * Normalizes the given path by resolving relative path segments (like "." and "..")
     * and converting backslashes to slashes. The normalization process removes any
     * redundant separators and ensures that the path is clean and usable.
     *
     * @param string $path The path to normalize.
     * @return string The normalized path.
     */
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $prefix = static::getAbsolutePrefix($path);
        $path = substr($path, strlen($prefix));
        $tokens = [];

        $iterator = new FilesystemIterator($path);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $tokens[] = $fileInfo->getFilename();
        }

        return $prefix . implode('/', $tokens);
    }

    /**
     * Indicates whether the given path is absolute or not.
     *
     * An absolute path is one that starts from the root directory,
     * while a relative path starts from the current working directory.
     *
     * @param string $path A normalized path.
     * @return bool Returns true if the path is absolute, false otherwise.
     */
    public static function isAbsolute(string $path): bool
    {
        return '' !== static::getAbsolutePrefix($path);
    }

    /**
     * Returns the absolute prefix of the given path.
     *
     * The absolute prefix can include the drive letter and protocol
     * for paths in certain operating systems (like Windows).
     *
     * @param string $path A normalized path.
     * @return string The absolute prefix of the path.
     */
    public static function getAbsolutePrefix(string $path): string
    {
        preg_match('|^(?P<prefix>([a-zA-Z]+:)?//?)|', $path, $matches);

        if (empty($matches['prefix'])) {
            return '';
        }

        return strtolower($matches['prefix']);
    }

    /**
     * Wraps the native dirname function to handle only UNIX-style paths.
     *
     * This method returns the directory name of the given path.
     * It ensures that the path is treated as a UNIX-style path,
     * replacing any backslashes with slashes.
     *
     * @param string $path The path from which to extract the directory name.
     * @return string The directory name of the path.
     */
    public static function dirname(string $path): string
    {
        return str_replace('\\', '/', dirname($path));
    }
}
