<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Database\Schema\Table\Create;
use Omega\Database\Facades\PDO;
use Omega\Database\Facades\Schema;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'migrate:init',
    description: 'Initialize the migration table in the database',
    options: [
        'database' => ['d', InputOption::VALUE_OPTIONAL, 'The database connection to use'],
    ]
)]
final class MigrateInitCommand extends AbstractMigration
{
    /**
     * Initialize the migration system by creating the migration table if it does not exist.
     *
     * @return int Exit code indicating the result:
     *             0 if the migration table already exists or is successfully created,
     *             1 if the migration table creation fails.
     */
    public function __invoke(): int
    {
        $dbName = $this->getDatabaseName();

        if ($this->hasMigrationTable()) {
            $this->io->writeln('<comment>Migration table already exists in your database.</comment>');
            return self::SUCCESS;
        }

        if ($this->createMigrationTable()) {
            $this->io->info('Successfully created migration table.');
            return self::SUCCESS;
        }

        $this->io->error('Migration table cannot be created.');
        return self::FAILURE;
    }

    /**
     * Create the migration table schema in the current database.
     *
     * @return bool Returns true on successful creation, false on failure.
     */
    private function createMigrationTable(): bool
    {
        return Schema::table('migration', function (Create $column) {
            $column('migration')->varchar(100)->notNull();
            $column('batch')->int(4)->notNull();

            $column->unique('migration');
        })->execute();
    }

    /**
     * Determine whether the migration table exists in the current database.
     *
     * @return bool Returns true if the migration table exists, false otherwise.
     */
    private function hasMigrationTable(): bool
    {
        $result = PDO::query(
            "SELECT COUNT(table_name) as total
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = 'migration'")
            ->single();

        if ($result) {
            return $result['total'] > 0;
        }

        return false;
    }
}
