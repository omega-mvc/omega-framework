<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Database\Facades\DB;
use Omega\Template\Generate;
use Omega\Template\Property;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;
use function file_exists;
use function file_put_contents;
use function Omega\Application\path;
use function ucfirst;

#[AsCommand(
    name: 'make:model',
    description: 'Generates a new model class representing a database table',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the model']
    ],
    options: [
        'table-name' => ['t', InputOption::VALUE_REQUIRED, 'Set table column when creating model'],
        'force'      => ['f', InputOption::VALUE_NONE, 'Force to create template even if it exists']
    ]
)]
final class MakeModelCommand extends AbstractMakeCommand
{
    public function __invoke(): int
    {
        $this->io->info('Making model file...');

        $this->isPath('path.model');

        $name = ucfirst($this->getArgument('name'));
        $modelLocation = $this->app->get('path.model') . $name . '.php';

        if (file_exists($modelLocation) && !$this->getOption('force')) {
            $this->io->warning('File already exists.');
            $this->io->error('Failed to create model file. Use --force to overwrite.');
            return self::FAILURE;
        }

        $this->io->info("Creating Model class in {$modelLocation}");

        $class = new Generate($name);
        $class->customizeTemplate(
            "<?php\n\ndeclare(strict_types=1);\n{{before}}{{comment}}\n{{rule}}class\40{{head}}\n{\n{{body}}}{{end}}"
        );
        $class->tabSize(4);
        $class->tabIndent(' ');
        $class->setEndWithNewLine();
        $class->namespace('App\\Models');
        $class->uses(['Omega\Database\Model\Model']);
        $class->extend('Model');

        $primaryKey = 'id';
        $tableName  = strtolower($this->getArgument('name')); // Default: nome modello minuscolo

        if ($this->getOption('table-name')) {
            $tableName = $this->getOption('table-name');
            $this->io->info("Getting information from table [{$tableName}]...");

            try {
                $tableInfo = DB::table($tableName)->info();

                foreach ($tableInfo as $column) {
                    $class->addComment('@property mixed $' . $column['COLUMN_NAME']);

                    if ('PRI' === ($column['COLUMN_KEY'] ?? '')) {
                        $primaryKey = $column['COLUMN_NAME'];
                    }
                }
            } catch (Throwable $th) {
                $this->io->warning("Database warning: " . $th->getMessage());
            }
        }

        $class->addProperty('tableName')
            ->visibility(Property::PROTECTED_)
            ->dataType('string')
            ->expecting(" = '{$tableName}'");

        $class->addProperty('primaryKey')
            ->visibility(Property::PROTECTED_)
            ->dataType('string')
            ->expecting(" = '{$primaryKey}'");

        if (file_put_contents($modelLocation, $class->generate()) === false) {
            $this->io->error('Failed to write model file to disk.');
            return self::FAILURE;
        }

        $displayPath = path('app.Models') . $name;
        $this->io->success("Model [{$displayPath}] created successfully.");

        return self::SUCCESS;
    }
}
