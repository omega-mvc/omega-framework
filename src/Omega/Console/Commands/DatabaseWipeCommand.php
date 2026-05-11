<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Exception;
use Omega\Console\Attribute\AsCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\Facades\Schema;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'db:wipe',
    description: 'Drop all tables, views, and types',
    options: [
        'database'    => ['d', InputOption::VALUE_OPTIONAL, 'The database connection to use'],
        'force'       => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        'no-interact' => [null, InputOption::VALUE_NONE, '']
    ]
)]
final class DatabaseWipeCommand extends AbstractMigration
{
    /**
     * Drop the target database after confirmation and environment validation.
     *
     * @return int Exit code indicating the result of the operation:
     *             0 on success, 1 on failure, 2 if aborted due to environment or user confirmation.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown if reading input from STDIN fails during confirmation prompts.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function __invoke(): int
    {
        $interact = $this->getOption('no-interact');
        $force    = $this->getOption('force');
        $dbName   = $this->getDatabaseName();
        $message  = "Do you want to drop database `{$dbName}`?";

        if (!$interact) {
            if (!$this->runInDev() && !$force) {
                $this->io->warning("The application is in PRODUCTION.");
                if (!$this->io->confirm("Are you sure you want to drop the database `{$dbName}`?", false)) {
                    $this->io->note("Operation aborted.");
                    return self::INVALID;
                }
            }
        }

        $this->io->writeln("<comment>Trying to drop database `{$dbName}`...</comment>");

        $success = Schema::drop()->database($dbName)->ifExists()->execute();

        if ($success) {
            $this->io->info("Successfully dropped database `{$dbName}`");
            return self::SUCCESS;
        }

        $this->io->error("Cannot drop database `{$dbName}`");
        return self::FAILURE;
    }
}
