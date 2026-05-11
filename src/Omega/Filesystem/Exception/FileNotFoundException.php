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

namespace Omega\Filesystem\Exception;

use Exception;
use RuntimeException;

use function sprintf;

/**
 * Exception to be thrown when a file was not found.
 *
 * This exception is thrown when a requested file cannot be located within the filesystem.
 * It extends the `RuntimeException` and implements the `ExceptionInterface` to provide
 * a consistent exception handling mechanism across the filesystem.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Exception
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class FileNotFoundException extends RuntimeException implements ExceptionInterface
{
    /**
     * Constructs a new `FileNotFoundException` instance.
     *
     * @param string         $key      The key (path) of the file that was not found.
     * @param int            $code     The exception code (default is 0).
     * @param Exception|null $previous Optional previous exception for chaining.
     * @return void
     */
    public function __construct(
        protected string $key,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct(
            sprintf(
                'The file "%s" was not found.',
                $key
            ),
            $code,
            $previous
        );
    }
}
