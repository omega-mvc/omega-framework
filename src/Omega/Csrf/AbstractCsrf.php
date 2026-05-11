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

use RuntimeException;
use Omega\Application\Application;
use Omega\Session\Storage\NativeStorage;

/**
 * Abstract CSRF Protection Class.
 *
 * This abstract class provides a base implementation for CSRF protection.
 * It defines a method to retrieve the session storage and enforces the
 * implementation of token generation and validation methods.
 *
 * Concrete implementations must define how CSRF tokens are created and validated.
 *
 * @category   Omega
 * @package    Csrf
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
abstract class AbstractCsrf implements CsrfInterface
{
    /**
     * Retrieves the session storage instance.
     *
     * This method accesses the session object via the application container.
     * The session storage is used to store and retrieve CSRF tokens.
     *
     * @return NativeStorage The session storage instance.
     * @throws RuntimeException if storage is not instance of NativeStorage.
     */
    protected function getSession(): NativeStorage
    {
        $session = Application::getInstance()->get('session');

        if (!$session instanceof NativeStorage) {
            throw new RuntimeException('Session storage is not an instance of NativeStorage.');
        }

        return $session;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function generateToken(): string;

    /**
     * {@inheritdoc}
     */
    abstract public function validateToken(?string $token): bool;
}
