<?php /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Console\Commands;

use DirectoryIterator;
use Exception;
use Omega\Collection\Collection;
use Omega\Console\AbstractCommand;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\Schema\SchemaConnection;
use Omega\Database\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Throwable;

/**
 * BaseMigrationCommand
 * Fornisce i mattoncini per costruire i comandi di database e migrazione.yes
 */
abstract class AbstractMigration extends AbstractCommand
{
    protected static array $vendorPaths = [];

    /**
     * Retrieve the target database name for migration operations.
     *
     * This method returns the database name specified via the command-line option
     * `--database`. If no option is provided, it retrieves the default database
     * name from the application's schema connection.
     *
     * @return string The name of the database to be used for migration commands.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function getDatabaseName(): string
    {
        $database = $this->getOption('database');

        return $database ?? $this->app->get(SchemaConnection::class)->getDatabase();
    }

    /**
     * Determine whether migration commands are running in a development environment.
     *
     * This method checks if the application is in development mode (`app()->isDev()`)
     * or if the `--force` option is provided. If not, it prompts the user to confirm
     * running migrations in production.
     *
     * @return bool Returns `true` if running in a development environment or if the user
     *              confirms running in production; otherwise, `false`.
     * @throws Exception Thrown if reading input from STDIN fails during the prompt.
     */
    protected function runInDev(?string $message = null): bool
    {
        if ($this->app->isDev()) {
            return true;
        }

        if ($message === null) {
            return false;
        }

        return $this->io->confirm($message, false);
    }

    /**
     * Retrieve the list of migrations to be executed.
     *
     * This method collects migration files from the default migration path and any
     * registered vendor paths, compares them with the migration table, and determines
     * which migrations need to be run for the given batch.
     *
     * @param false|int $batch Optional batch number to limit the migrations. If `false`,
     *                         the next batch number will be used automatically.
     * @return Collection<string, array<string, string>> Returns a collection mapping
     *         migration names to arrays containing `file_name` and `batch`.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function baseMigrate(false|int &$batch = false, bool $register = true): Collection
    {
        $migrationBatch = $this->getMigrationTable();

        $higher = $migrationBatch->length() > 0
            ? $migrationBatch->max() + 1
            : 0;

        $batch = false === $batch ? $higher : $batch;

        $paths   = [$this->app->get('path.migration'), ...static::$vendorPaths];
        $migrate = new Collection([]);

        foreach ($paths as $dir) {
            foreach (new DirectoryIterator($dir) as $file) {
                if ($file->isDot() || $file->isDir()) {
                    continue;
                }

                $migrationName = pathinfo($file->getBasename(), PATHINFO_FILENAME);
                $hasMigration  = $migrationBatch->has($migrationName);

                $filePath = rtrim($dir, '/') . '/' . $file->getFilename();

                // Caso: migrate (up) → nuova migration
                if (false === $hasMigration) {
                    $migrate->set($migrationName, [
                        'file_name' => $filePath,
                        'batch'     => $higher,
                    ]);

                    if ($register) {
                        $this->insertMigrationTable([
                            'migration' => $migrationName,
                            'batch'     => $higher,
                        ]);
                    }

                    continue;
                }

                // Caso: rollback / refresh / status
                if ($migrationBatch->get($migrationName) <= $batch) {
                    $migrate->set($migrationName, [
                        'file_name' => $filePath,
                        'batch'     => $migrationBatch->get($migrationName),
                    ]);
                }
            }
        }

        return $migrate;
    }

    /**
     * Execute all pending migrations for the current batch.
     *
     * This method retrieves migration files, compares them with the migration table,
     * and runs their `up` scripts. If the `--dry-run` option is provided, the SQL
     * queries will only be displayed without executing them. Execution can be
     * suppressed using the `$silent` flag.
     *
     * @param bool $silent If `true`, suppresses prompts and outputs; otherwise prompts may be shown.
     * @return int Exit code indicating the result of running migrations:
     *             0 on success, 2 if aborted due to environment or user confirmation failure,
     *             1 on general failure.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception Thrown if an unexpected error occurs during migration execution.
     * @throws ExceptionInterface
     */
    protected function migration(): int
    {
        // 1. Controllo Ambiente
        if (!$this->runInDev('<fg=red;options=bold>Running migration/database in production?</> Continue?')) {
            return self::INVALID;
        }

        // 2. Calcolo Larghezza Terminale (Standard Symfony 8)
        // Se non riesce a rilevarla, il default è 80
        $width = min($this->terminal->getWidth() - 20, 60);

        $batch = false;
        $migrate = $this->baseMigrate($batch);

        $migrate = $migrate
            ->filter(static fn ($value): bool => (int) $value['batch'] === (int) $batch)
            ->sort();

        if ($migrate->isEmpty()) {
            $this->io->info('Nothing to migrate.');
            return self::SUCCESS;
        }

        $this->io->title('Running migrations');

        foreach ($migrate as $key => $val) {
            $schema = require_once $val['file_name'];
            $up = new Collection($schema['up'] ?? []);

            if ($this->getOption('dry-run')) {
                $up->each(function ($item) {
                    $this->io->writeln("<fg=gray>{$item->__toString()}</>");
                    $this->io->newLine();
                    return true;
                });
                continue;
            }

            // 3. Output Allineato
            // Usiamo write() per restare sulla stessa riga
            $this->io->write("<fg=gray>" . $key . "</>");

            $dotCount = max(0, $width - strlen($key));
            if ($dotCount > 0) {
                $this->io->write("<fg=gray>" . str_repeat('.', $dotCount) . "</>");
            }

            try {
                $success = $up->every(fn ($item) => $item->execute());

                if ($success) {
                    $this->io->writeln(' <info>DONE</info>');
                } else {
                    $this->io->writeln(' <error>FAIL</error>');
                }
            } catch (Throwable $th) {
                $this->io->newLine();
                $this->io->error($th->getMessage());
                return self::FAILURE;
            }
        }

        $this->io->newLine();

        return $this->seed();
    }

    /**
     * Execute seeders after migrations based on the provided options.
     *
     * @return int Exit code indicating the result:
     *             0 if no seeding is performed or on success,
     *             otherwise the exit code returned by the seeder command.
     */
    protected function seed(): int
    {
        if ($this->getOption('dry-run')) {
            return self::SUCCESS;
        }

        // Recuperiamo il valore dell'opzione --seed
        // In Symfony, se l'opzione è InputOption::VALUE_NONE, torna bool
        $shouldSeed = $this->getOption('seed');

        if (!$shouldSeed) {
            return self::SUCCESS;
        }

        $parameters = [];

        // Gestione namespace se presente
        $namespace = $this->getOption('seed-namespace');
        if ($namespace) {
            $parameters['--name-space'] = $namespace;
        }

        // Usiamo il metodo call() che abbiamo aggiunto alla base per invocare il seeder
        // Assumendo che il comando si chiami 'seed' o 'db:seed'
        try {
            return $this->call('seed', $parameters);
        } catch (Throwable $e) {
            $this->io->error("Seeding failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Retrieve the list of executed migrations and their batch numbers.
     *
     * @return Collection<string, int> A collection mapping migration names to their batch numbers.
     */
    protected function getMigrationTable(): Collection
    {
        /** @var Collection<string, int> $pair */
        $pair = DB::table('migration')
            ->select()
            ->get()
            ->assocBy(static fn ($item) => [$item['migration'] => (int) $item['batch']]);

        return $pair;
    }

    /**
     * Insert a migration record into the migration table.
     *
     * @param array<string, string|int> $migration The migration name and its associated batch number.
     * @return bool Returns true on successful insertion, false otherwise.
     */
    private function insertMigrationTable(array $migration): bool
    {
        return DB::table('migration')
            ->insert()
            ->values($migration)
            ->execute()
            ;
    }

    /**
     * Delete migration records for the specified batch number.
     *
     * @param int $batchNumber The batch number whose migrations should be removed.
     * @return bool Returns true on successful deletion, false otherwise.
     */
    private function deleteMigrationTable(int $batchNumber): bool
    {
        return DB::table('migration')
            ->delete()
            ->equal('batch', $batchNumber)
            ->execute()
            ;
    }

    /**
     * Roll back executed migrations based on batch number and limit.
     *
     * @param false|int $batch The batch number to roll back, or `false` to determine it automatically.
     * @param int $take The number of batches to roll back starting from the given batch.
     * @return int Exit code indicating the result of the rollback process:
     *             always returns 0 after processing the selected migrations.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    protected function rollbacks(false|int $batch, int $take): int
    {
        $width = min($this->terminal->getWidth() - 20, 60);

        // ❗ IMPORTANTE: register = false
        $migrate = false === $batch
            ? $this->baseMigrate($batch, false)
            : $this->baseMigrate($batch, false)
                ->filter(static fn ($value): bool => $value['batch'] >= $batch - $take);

        foreach ($migrate->sortDesc() as $key => $val) {
            $schema = require_once $val['file_name'];
            $down   = new Collection($schema['down'] ?? []);

            if ($this->getOption('dry-run')) {
                $down->each(function ($item) {
                    $this->io->writeln("<fg=gray>{$item->__toString()}</>");
                    $this->io->newLine(2);
                    return true;
                });
                continue;
            }

            $this->io->write("<fg=gray>{$key}</>");

            $dotCount = max(0, $width - strlen($key));
            if ($dotCount > 0) {
                $this->io->write("<fg=gray>" . str_repeat('.', $dotCount) . "</>");
            }

            try {
                $success = $down->every(fn ($item) => $item->execute());

                if ($success) {
                    $success = $this->deleteMigrationTable((int) $val['batch']);
                }
            } catch (Throwable $th) {
                // 👉 qui puoi decidere se essere tollerante
                if (str_contains($th->getMessage(), 'Base table or view not found')) {
                    $success = true;
                } else {
                    $success = false;
                    $this->io->error($th->getMessage());
                }
            }

            if ($success) {
                $this->io->writeln(' <info>DONE</info>');
                continue;
            }

            $this->io->writeln(' <error>FAIL</error>');
        }

        $this->io->newLine();

        return self::SUCCESS;
    }
}
