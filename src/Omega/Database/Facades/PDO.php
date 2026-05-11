<?php

/**
 * Part of Omega - Facades Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Database\Facades;

use Omega\Database\ConnectionInterface;
use Omega\Facade\AbstractFacade;

/**
 * Facade for the Connection service.
 *
 * This facade provides a static interface to the underlying `Connection` instance
 * resolved from the application container. It allows convenient static-style
 * calls while still relying on dependency injection and the container under the hood.
 *
 * Usage of this facade does not create a global state; the underlying instance
 * is still managed by the container and may be swapped, mocked, or replaced
 * for testing or customization purposes.
 *
 * @category   Omega
 * @package    Database
 * @subpackges Facades
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @method static ConnectionInterface   getInstance()
 * @method static array        configs()
 * @method static ConnectionInterface   query(string $query)
 * @method static ConnectionInterface   bind(string|int|bool|null $param, mixed $value, string|int|bool|null $type = null)
 * @method static bool         execute()
 * @method static array|false  resultset()
 * @method static mixed        single()
 * @method static int          rowCount()
 * @method static string|false lastInsertId()
 * @method static bool         transaction(callable $callable)
 * @method static bool         beginTransaction()
 * @method static bool         endTransaction()
 * @method static bool         cancelTransaction()
 * @method static void         flushLogs()
 * @method static array        getLogs()
 *
 * @see Connection
 */
final class PDO extends AbstractFacade
{
    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor(): string
    {
        return 'database';
    }
}
