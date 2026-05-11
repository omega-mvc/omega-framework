<?php

/**
 * Part of Omega - Database Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Database\Seeder;

use Omega\Database\Connectioninterface;
use Omega\Database\Query\Insert;

/**
 * AbstractSeeder
 *
 * Base class for database seeders.
 * Provides common functionality for creating and running seed data.
 *
 * This class implements the SeederInterface and requires a database connection
 * to perform insert operations. Concrete seeders should extend this class and
 * implement the `run()` method to define the specific seed logic.
 *
 * Example usage:
 * ```
 * class UserSeeder extends AbstractSeeder {
 *     public function run(): void {
 *         $this->create('users')->values([
 *             'name' => 'Admin',
 *             'email' => 'admin@example.com'
 *         ])->execute();
 *     }
 * }
 * ```
 *
 * @category   Omega
 * @package    Database
 * @subpackage Seeder
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
abstract class AbstractSeeder implements SeederInterface
{
    /**
     * AbstractSeeder constructor.
     *
     * Initializes the seeder with a database connection.
     *
     * @param ConnectionInterface $pdo The PDO-like connection object used to perform database operations.
     */
    public function __construct(protected ConnectionInterface $pdo)
    {
    }

    /**
     * {@inherotdoc}
     */
    public function call(string $className): void
    {
        $class = new $className($this->pdo);
        $class->run();
    }

    /**
     * {@inherotdoc}
     */
    public function create(string $tableName): Insert
    {
        return new Insert($tableName, $this->pdo);
    }

    /**
     * {@inherotdoc}
     */
    abstract public function run(): void;
}
