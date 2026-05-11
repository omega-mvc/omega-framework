<?php

/**
 * Part of Omega - CSRF Protection Package.
 * PHP version 8.3
 *
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Omega\Csrf;

use Omega\Csrf\Exception\CsrfInvalidTokenException;
use Omega\Csrf\Exception\CsrfMalformedTokenException;

/**
 * CSRF Protection Interface.
 *
 * This interface defines methods for generating and validating CSRF tokens
 * to protect against cross-site request forgery attacks.
 *
 * Implementing classes must provide a way to generate unique CSRF tokens and
 * validate them against stored session values.
 *
 * @category   Omega
 * @package    Csrf
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
interface CsrfInterface
{
    /**
     * Generates a new CSRF token and stores it in the session.
     *
     * @return string Returns the generated CSRF token.
     */
    public function generateToken(): string;

    /**
     * Validates a CSRF token and throws an exception if invalid.
     *
     * This method checks the provided token against the stored session token.
     * If the token is invalid, it throws a CsrfValidationException.
     *
     * @param string|null $token The token received from the request.
     * @return bool Returns true if the token is valid.
     * @throws CsrfInvalidTokenException If the token is invalid.
     * @throws CsrfMalformedTokenException if token is malformed.
     */
    public function validateToken(?string $token): bool;
}
