<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Cron\Schedule;
use Omega\Cron\Facade\Schedule as Scheduler;
use Omega\Time\Now;

use function microtime;
use function round;
use function sleep;
use function sprintf;

#[AsCommand(
    name: 'cron:work',
    description: 'Simulate the cron scheduler in the terminal'
)]
final class CronWorkCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(): int
    {
        $this->io->title('Omega Cron Worker');
        $this->io->info('Watching and executing scheduled jobs every minute...');
        $this->io->note('Press CTRL+C to stop the worker.');

        $schedule = Scheduler::add(new Schedule());

        while (true) {
            $now = new Now();
            $timestamp = sprintf(
                '%s-%s-%s %02d:%02d:%02d',
                $now->getYear(), $now->getMonth(), $now->getDay(),
                $now->getHour(), $now->getMinute(), $now->getSecond()
            );

            $this->io->write("<fg=gray>[{$timestamp}]</> Running scheduled tasks... ");

            $start = microtime(true);
            $schedule->execute();
            $executionTime = round((microtime(true) - $start) * 1000, 2);

            $this->io->writeln("Done! ({$executionTime}ms)");

            sleep(60);
        }
    }
}
