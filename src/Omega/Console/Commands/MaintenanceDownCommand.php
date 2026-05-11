<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;

use function Omega\Application\slash;

#[AsCommand(
    name: 'down',
    description: 'Put the application into maintenance mode'
)]
final class MaintenanceDownCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        if ($this->app->isDownMaintenanceMode()) {
            $this->io->warning('Application is already under maintenance mode.');
            return self::FAILURE;
        }

        $storagePath = $this->app->get('path.storage') . 'app/';

        // Creazione file 'down' dallo stub
        $downFile = $storagePath . 'down';
        if (!file_exists($downFile)) {
            file_put_contents(
                $downFile,
                file_get_contents(slash(dirname(__DIR__) . '/stubs/down.stub'))
            );
        }

        // Creazione file 'maintenance.php' dallo stub
        file_put_contents(
            $storagePath . 'maintenance.php',
            file_get_contents(slash(dirname(__DIR__) . '/stubs/maintenance.stub'))
        );

        $this->io->info('Application is now in maintenance mode.');

        return self::SUCCESS;
    }
}
