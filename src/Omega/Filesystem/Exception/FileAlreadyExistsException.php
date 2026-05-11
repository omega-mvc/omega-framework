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
 * Exception to be thrown when a file already exists.
 *
 * This exception is specifically designed to handle scenarios where
 * an attempt is made to create or overwrite a file that already
 * exists in the filesystem. It extends the RuntimeException,
 * providing a clear indication that this error is due to an
 * improper use of the filesystem operations.
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
class FileAlreadyExistsException extends RuntimeException implements ExceptionInterface
{
    /**
     * Constructs a new FileAlreadyExistsException.
     *
     * @param string         $key      The key (path) of the existing file.
     * @param int            $code     The error code (default is 0).
     * @param Exception|null $previous The previous exception for
     *                                 exception chaining (default is null).
     * @return void
     */
    public function __construct(
        protected string $key,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct(
            sprintf(
                'The file %s already exists and can not be overwritten.',
                $key
            ),
            $code,
            $previous
        );
    }
}
