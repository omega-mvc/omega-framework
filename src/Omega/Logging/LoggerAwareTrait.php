<?php

/**
 * Part of Omega - Logging Package.
 * php versiom 8.2
 *
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Omega\Logging;

/**
 * Logger aware trait.
 *
 * The LoggerAwareTrait provides the implementation for objects that need to be logger-aware by allowing them to store
 * and use a logger instance. This trait is part of the Omega Logging package and helps in injecting a logger into
 * any class that uses it. It includes the following elements:
 *
 * * $logger: A protected property that stores an instance of the LoggerInterface. It is nullable, meaning an object
 *            can either have a logger instance assigned or none.
 *
 * * setLogger(LoggerInterface $logger): A method used to assign a logger instance to the object. This enables the
 *                                       object to log messages through the provided logger.
 *
 * This trait is typically used in combination with the LoggerAwareInterface to ensure objects can accept and manage
 * logger instances, promoting consistency in logging across different parts of the application.
 *
 * @category   Omega
 * @package    Logging
 * @link       https://omegamvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    1.0.0
 */
trait LoggerAwareTrait
{
    /**
     * The logger instance.
     *
     * @var LoggerInterface|null Holds the current logger instance.
     */
    protected ?LoggerInterface $logger = null;

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
