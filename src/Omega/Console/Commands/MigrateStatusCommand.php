<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'migrate:status',
    description: 'Show migration status.'
)]
final class MigrateStatusCommand extends AbstractMigration
{
    /**
     * Display the current migration status and batch numbers.
     *
     * @return int Exit code indicating the result:
     *             always returns 0 after printing migration statuses.
     */
    public function __invoke(): int
    {
        $this->io->note('show migration status');

        $width = min($this->terminal->getWidth() - 20, 60);

        foreach ($this->getMigrationTable() as $migrationName => $batch) {
            $length = strlen($migrationName) + strlen((string) $batch);

            $line = $migrationName
                . ' '
                . str_repeat('.', max($width - $length, 0))
                . ' '
                . $batch;

            $this->io->text("<fg=default;options=bold>$line</>");
        }

        return self::SUCCESS; // Symfony-style return
    }
}
