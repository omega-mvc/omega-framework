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

use Random\RandomException;
use Omega\Csrf\Exception\CsrfInvalidTokenException;
use Omega\Csrf\Exception\CsrfMalformedTokenException;

use function bin2hex;
use function hash_equals;
use function random_bytes;
use function strlen;

/**
 * CSRF Protection class.
 *
 * This class provides methods to generate and validate CSRF tokens.
 *
 * @category   Omega
 * @package    Csrf
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
class Csrf extends AbstractCsrf
{
    /**
     * {@inheritdoc}
     * @throws RandomException
     */
    public function generateToken(): string
    {
        $session = $this->getSession();
        $token   = bin2hex(random_bytes(32));

        $session->put('csrf_token', $token);

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function validateToken(?string $token = null): bool
    {
        if (empty($token)) {
            throw new CsrfMalformedTokenException('CSRF token is missing or empty.');
        }

        if (!ctype_xdigit($token) || strlen($token) !== 64) {
            throw new CsrfMalformedTokenException('CSRF token is malformed.');
        }

        if (!$this->verifyToken($token)) {
            throw new CsrfInvalidTokenException('CSRF token mismatch.');
        }

        return true;
    }

    /**
     * Verifies the CSRF token against the stored session token.
     *
     * @param string $token The token to verify.
     * @return bool Returns true if the token matches, false otherwise.
     */
    private function verifyToken(string $token): bool
    {
        $session     = $this->getSession();
        $storedToken = $session->get('csrf_token', '');

        if (!is_string($storedToken)) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }
}
