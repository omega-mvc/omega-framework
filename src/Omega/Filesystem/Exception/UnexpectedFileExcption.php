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
 * Exception to be thrown when an unexpected file exists.
 *
 * This exception is thrown when a file that is not anticipated to exist in the
 * filesystem is found. It extends the `RuntimeException` and implements the
 * `ExceptionInterface` to maintain a consistent exception handling approach
 * across the filesystem.
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
class UnexpectedFileExcption extends RuntimeException implements ExceptionInterface
{
    /**
     * Constructs a new `UnexpectedFileException` instance.
     *
     * @param string         $key      The key (path) of the file that was unexpectedly found.
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
                'The file "%s" was not supposed to exist.',
                $key
            ),
            $code,
            $previous
        );
    }
}
