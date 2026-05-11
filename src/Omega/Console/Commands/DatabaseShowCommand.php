<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Console\Traits\InteractsWithConsoleOutputTrait;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\Facades\DB;
use Omega\Database\Facades\PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'db:show',
    description: 'Show database tables and sizes',
    options: [
        'database'   => ['d', InputOption::VALUE_OPTIONAL, 'The database connection to use'],
        'force'      => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        'table-name' => ['t', InputOption::VALUE_OPTIONAL, 'Display information about the given database table']
    ]
)]
final class DatabaseShowCommand extends AbstractMigration
{
    use InteractsWithConsoleOutputTrait;

    /**
     * Display information about the current database or a specific table.
     *
     * @return int Exit code indicating the result:
     *             0 on success, 2 if no tables are found or the database is empty.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws NotFoundExceptionInterface Thrown if the requested schema connection service is not in the container.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function __invoke(): int
    {
        if ($this->getOption('table-name')) {
            return $this->tableShow($this->getOption('table-name'));
        }

        $dbName = $this->getDatabaseName();

        // Messaggio iniziale pulito (senza info() di SymfonyStyle per coerenza)
        $this->io->newLine();
        $this->io->writeln("  <fg=gray>Showing database:</> <info>{$dbName}</info>");
        $this->io->newLine();

        $tables = PDO::query('SHOW DATABASES')
            ->query('
            SELECT table_name, create_time, ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024) AS `size`
            FROM information_schema.tables
            WHERE table_schema = :db_name')
            ->bind(':db_name', $dbName)
            ->resultset();

        if (empty($tables)) {
            $this->io->warning('Database is empty, try to run migration.');
            return self::INVALID;
        }

        foreach ($tables as $table) {
            $table = array_change_key_case($table);

            // Colonna Sinistra: Nome Tabella + Dimensione
            $name = "<fg=cyan;options=bold>{$table['table_name']}</>";
            $size = "<fg=gray>{$table['size']} MB</>";
            $leftSide = "{$name} {$size}";

            // Colonna Destra: Data di creazione
            $rightSide = "<fg=yellow>{$table['create_time']}</>";

            // Il trait si occupa di calcolare i puntini e allineare tutto
            $this->componentsTwoColumns($leftSide, $rightSide);
        }

        $this->io->newLine();

        return self::SUCCESS;
    }

    /**
     * Display detailed column information for a specific database table.
     *
     * @param string $tableName The name of the table to inspect.
     * @return int Exit code indicating the result:
     *             always returns 0 after printing the table structure.
     */
    private function tableShow(string $tableName): int
    {
        // Recuperiamo informazioni sulle colonne
        $columns = DB::table($tableName)->info();

        if (empty($columns)) {
            $this->io->error("Table `{$tableName}` does not exist or has no columns.");
            return self::FAILURE;
        }

        $this->io->newLine();
        $this->io->writeln("  <fg=gray>Columns of table:</> <info>{$tableName}</info>");
        $this->io->newLine();

        // 1. Calcoliamo la larghezza massima dei nomi delle colonne per l'allineamento
        $columnNames = array_column($columns, 'COLUMN_NAME');
        $maxColumnWidth = $this->getVisibleMaxWidth($columnNames);

        foreach ($columns as $column) {
            $name = $column['COLUMN_NAME'];

            // 2. Prepariamo gli attributi (Primary, Nullable)
            $attributes = [];
            if (($column['COLUMN_KEY'] ?? '') === 'PRI') {
                $attributes[] = '<fg=yellow;options=bold>primary</>';
            }
            if (($column['IS_NULLABLE'] ?? '') === 'YES') {
                $attributes[] = '<fg=gray>nullable</>';
            }

            $attrString = !empty($attributes) ? implode(', ', $attributes) : '';

            // 3. Calcoliamo il padding per allineare gli attributi subito dopo il nome
            $nameWidth = $this->getVisibleWidth($name);
            $padding = str_repeat(' ', $maxColumnWidth - $nameWidth + 2);

            // Costruiamo la parte sinistra: NOME + PADDING + ATTRIBUTI
            $leftSide = "<fg=cyan;options=bold>{$name}</>{$padding}{$attrString}";

            // Parte destra: il tipo di dato (es. varchar(255), int, timestamp)
            $rightSide = "<fg=magenta>{$column['COLUMN_TYPE']}</>";

            // Visualizzazione con puntini
            $this->componentsTwoColumns($leftSide, $rightSide);
        }

        $this->io->newLine();

        return self::SUCCESS;
    }
}
