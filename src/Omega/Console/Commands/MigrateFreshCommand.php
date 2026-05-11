<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Collection\Collection;
use Omega\Console\Attribute\AsCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

#[AsCommand(
    name: 'migrate:fresh',
    description: 'Drop database and run migrations from scratch',
    options: [
        'force'    => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        'dry-run'  => [null, InputOption::VALUE_NONE, 'Dump the SQL queries without executing'],
        'seed'     => [null, InputOption::VALUE_NONE, 'Seed the database after migrating'],
        'yes'      => ['y', InputOption::VALUE_NONE, 'Do not ask for confirmation (Assume "yes")'],
        'database' => ['d', InputOption::VALUE_OPTIONAL, 'The database connection to use']
    ]
)]
final class MigrateFreshCommand extends AbstractMigration
{
    /**
     * Drops and recreates the database, then runs all migrations from scratch.
     *
     * This method is typically used to reset the database to a clean state
     * and apply all migrations in order. It respects the `$silent` flag
     * to suppress prompts and output, and the `--dry-run` option to
     * preview SQL queries without executing them.
     *
     * @return int Exit code indicating the result of running migrations:
     *             0 on success, 2 if aborted due to environment or user confirmation failure,
     *             1 on general failure.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     */
    public function __invoke(): int
    {
        if (!$this->runInDev('<fg=red;options=bold>Running migration/database in production?</> Continue?')) {
            return self::INVALID;
        }

        if (($drop = $this->call('db:wipe', ['--no-interact' => true])) > 0) {
            return $drop;
        }

        if (($create = $this->call('db:create', ['--no-interact' => true])) > 0) {
            return $create;
        }

        // run migration
        $batch   = false;
        $migrate = $this->baseMigrate($batch)->sort();
        $width = min($this->terminal->getWidth() - 20, 60); //

        $this->io->title('Running migration');

        foreach ($migrate as $key => $val) {
            $schema = require_once $val['file_name'];
            $up     = new Collection($schema['up'] ?? []);

            if ($this->getOption('dry-run')) {
                $up->each(function ($item) {
                    $this->io->writeln("<fg=gray>{$item->__toString()}</>");
                    $this->io->newLine();
                    return true;
                });
                continue;
            }

            // output allineato
            $this->io->write("<fg=gray>{$key}</>");
            $dotCount = max(0, $width - strlen($key));
            if ($dotCount > 0) {
                $this->io->write("<fg=gray>" . str_repeat('.', $dotCount) . "</>");
            }

            try {
                $success = $up->every(fn ($item) => $item->execute());
            } catch (Throwable $th) {
                $this->io->newLine();
                $this->io->error($th->getMessage());
                $success = false;
            }

            if ($success) {
                $this->io->writeln(' <info>DONE</info>');
            } else {
                $this->io->writeln(' <error>FAIL</error>');
            }
        }

        $this->io->newLine();

        return $this->seed();
    }
}
