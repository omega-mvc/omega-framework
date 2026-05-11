<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'migrate:rollback',
    description: 'Rolling back last migrations (down)',
    options: [
        'force'    => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        'dry-run'  => [null, InputOption::VALUE_NONE, 'Dump the SQL queries without executing'],
        'seed'     => [null, InputOption::VALUE_NONE, 'Seed the database after migrating'],
        'batch'    => [null, InputOption::VALUE_OPTIONAL, 'The batch number to rollback'],
        'take'     => [null, InputOption::VALUE_OPTIONAL, 'Limit the number of migrations to run']
    ]
)]
final class MigrateRollbackCommand extends AbstractMigration
{
    /**
     * Roll back one or more batches of migrations.
     *
     * @return int Exit code indicating the result of the rollback operation:
     *             0 on success, 1 if required options are missing or invalid.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function __invoke(): int
    {
        $batch = $this->getOption('batch');

        if ($batch === null) {
            $this->io->error('batch is required.');
            return self::FAILURE;
        }

        $take = (int) $this->getOption('take');
        $message = "Rolling {$take} back migrations.";
        if ($take < 0) {
            $take    = 0;
            $message = 'Rolling back migrations.';
        }

        $this->io->info($message);

        return $this->rollbacks((int) $batch, $take);
    }
}
