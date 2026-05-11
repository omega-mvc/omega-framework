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
 * Interface for adapters that support checksum calculation.
 *
 * This interface defines a method to calculate and return a checksum for a file
 * identified by its key. It is intended to be implemented by filesystem adapters
 * that offer checksum validation functionality.
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
interface ChecksumCalculatorInterface
{
    /**
     * Returns the checksum of the specified file.
     *
     * This method calculates and returns a checksum (such as MD5, SHA-256, etc.)
     * for the file identified by the given key. The checksum can be used to verify
     * the integrity of the file or ensure that no changes have occurred.
     *
     * @param string $key The identifier of the file for which to calculate the checksum.
     * @return string The calculated checksum as a string.
     */
    public function checksum(string $key): string;
}
