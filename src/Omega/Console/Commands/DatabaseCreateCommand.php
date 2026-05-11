<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Exception;
use Omega\Console\Attribute\AsCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\Facades\Schema;
use PDOException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

use function str_contains;

#[AsCommand(
    name: 'db:create',
    description: 'Create the specified database',
    options: [
        'database'    => ['d', InputOption::VALUE_OPTIONAL, 'The database connection to use'],
        'force'       => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        'no-interact' => [null, InputOption::VALUE_NONE, '']
    ]
)]
final class DatabaseCreateCommand extends AbstractMigration
{
    /**
     * Create the target database and initialize the migration table if needed.
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
     * @throws Throwable
     */
    public function __invoke(): int
    {
        $interact = $this->getOption('no-interact');
        $force    = $this->getOption('force');
        $dbName   = $this->getDatabaseName();

        if (!$interact) {
            if (!$this->runInDev() && !$force) {
                $this->io->warning("The application is in PRODUCTION.");
                if (!$this->io->confirm("Are you sure you want to create the database `$dbName`?", false)) {
                    $this->io->note("Operation aborted.");
                    return self::INVALID;
                }
            }
        }

        $this->io->writeln("<info>Creating database `{$dbName}`...</info>");

        try {
            $success = Schema::create()->database($dbName)->execute();
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'database exists')) {
                $this->io->error("Database `{$dbName}` already exists.");
                return self::FAILURE;
            }
            throw $e;
        }


        $success = Schema::create()->database($dbName)->ifNotExists()->execute();

        if ($success) {
            $this->io->info("Successfully created database `{$dbName}`");

            $this->call('migrate:init', ['--database' => $dbName]);

            return self::SUCCESS;
        }

        $this->io->error("Cannot create database `{$dbName}`");

        return self::FAILURE;
    }
}
