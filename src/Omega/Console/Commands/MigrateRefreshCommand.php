<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Exception;
use Omega\Console\Attribute\AsCommand;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'migrate:refresh',
    description: 'Rolling back and run migration all',
    options: [
        'force'    => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        'dry-run'  => [null, InputOption::VALUE_NONE, 'Dump the SQL queries without executing'],
        'seed'     => [null, InputOption::VALUE_NONE, 'Seed the database after migrating']
    ]
)]
final class MigrateRefreshCommand extends AbstractMigration
{
    /**
     * Reset all migrations and immediately re-run them.
     *
     * @return int Exit code indicating the result of the refresh operation:
     *             0 on success, 2 if aborted due to environment restrictions,
     *             or a propagated non-zero code from reset or migration.
     * @return int
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception|ExceptionInterface Thrown if reading input from STDIN fails during the prompt.
     */
    public function __invoke(): int
    {
        if (false === $this->runInDev()) {
            return self::INVALID;
        }

        if (($reset = $this->call('migrate:reset', ['--silent' => true])) > 0) {
            return $reset;
        }

        if (($migration = $this->migration(true)) > 0) {
            return $migration;
        }

        return self::SUCCESS;
    }
}
