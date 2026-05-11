<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'migrate:run',
    description: 'Run migration (up).',
    options: [
        'force'   => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        'dry-run' => [null, InputOption::VALUE_NONE, 'Dump the SQL queries without executing'],
        'seed'    => [null, InputOption::VALUE_NONE, 'Seed the database after migrating']
    ],
    aliases: ['migrate']
)]
final class MigrateRunCommand extends AbstractMigration
{
    /**
     * @throws ContainerExceptionInterface
     * @throws ExceptionInterface
     */
    public function __invoke(): int
    {
        return $this->migration();
    }
}
