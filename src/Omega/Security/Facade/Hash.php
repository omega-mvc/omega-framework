<?php

/**
 * Part of Omega - Security Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Security\Facade;

use Omega\Security\Hashing\HashInterface;
use Omega\Security\Hashing\HashManager;
use Omega\Facade\AbstractFacade;

/**
 * Facade for the Hash service.
 *
 * This facade provides a static interface to the underlying `Hash` instance
 * resolved from the application container. It allows convenient static-style
 * calls while still relying on dependency injection and the container under the hood.
 *
 * Usage of this facade does not create a global state; the underlying instance
 * is still managed by the container and may be swapped, mocked, or replaced
 * for testing or customization purposes.
 *
 * @category   Omega
 * @package    Security
 * @subpackges Facade
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @method static HashManager   setDefaultDriver(HashInterface $driver)
 * @method static HashManager   setDriver(string $driver_name, HashInterface $driver)
 * @method static HashInterface driver(?string $driver = null)
 * @method static array         info(string $hashed_value)
 * @method static string        make(string $value, array $options = [])
 * @method static bool          verify(string $value, string $hashed_value, array $options = [])
 * @method static bool          isValidAlgorithm(string $hash)
 *
 * @see HashManager
 */
final class Hash extends AbstractFacade
{
    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor(): string
    {
        return HashManager::class;
    }
}
