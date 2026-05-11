<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

use function Omega\Application\slash;
use function Omega\Time\now;

#[AsCommand(
    name: 'make:migration',
    description: 'Generate a new database migration file',
    arguments: [
        'name'   => [InputArgument::REQUIRED, 'The name of the migration']
    ],
    options: [
        'update' => ['u', InputOption::VALUE_NONE, 'Generate migration file with alter (update)']
    ]
)]
final class MakeMigrationCommand extends AbstractMakeCommand
{
    public function __invoke(): int
    {
        $this->io->info('Making migration...');

        $name = $this->getArgument('name');

        if (!$name) {
            $question = new Question('Please fill the table name');

            $question->setValidator(function ($answer) {
                if (empty($answer) || trim($answer) === '') {
                    throw new \RuntimeException('The table name is required.');
                }
                return $answer;
            });

            $question->setMaxAttempts(3);

            $name = $this->io->askQuestion($question);
        }

        $name = strtolower(trim((string)$name));

        // 2. Definizione percorsi e nomi file
        $pathToFile = $this->app->get('path.migration');
        $timestamp  = now()->format('Y_m_d_His');
        $fileName   = "{$pathToFile}{$timestamp}_{$name}.php";

        // 3. Scelta dello Stub
        $stubName = $this->getOption('update') ? 'migration_update.stub' : 'migration.stub';
        $stubPath = slash(dirname(__DIR__) . '/stubs/') . $stubName;

        if (!file_exists($stubPath)) {
            $this->io->error("Stub not found at: {$stubPath}");
            return self::FAILURE;
        }

        // 4. Lettura e rimpiazzo
        $template = file_get_contents($stubPath);
        $template = str_replace('__table__', $name, $template);

        // 5. Scrittura file
        if (!is_dir($pathToFile)) {
            mkdir($pathToFile, 0755, true);
        }

        if (file_put_contents($fileName, $template) === false) {
            $this->io->error("Can't create migration file in: {$pathToFile}");
            return self::FAILURE;
        }

        $this->io->success("Success! Migration file created: " . basename($fileName));

        return self::SUCCESS;
    }
}
