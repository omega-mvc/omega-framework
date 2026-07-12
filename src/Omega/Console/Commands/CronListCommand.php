<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Console\Traits\InteractsWithConsoleOutputTrait;
use Omega\Cron\Schedule;
use Omega\Cron\Facade\Schedule as Scheduler;
use function array_map;
use function count;
use function str_repeat;

#[AsCommand(
    name: 'cron:list',
    description: 'List all scheduled cron jobs'
)]
final class CronListCommand extends AbstractCommand
{
    use InteractsWithConsoleOutputTrait;

    /**
     * {@inheritdoc}
     */
    public function __invoke(): int
    {
        $schedule = Scheduler::add(new Schedule());
        $pools = $schedule->getPools();

        if (empty($pools)) {
            $this->io->warning('No scheduled jobs found.');
            return self::SUCCESS;
        }

        $formattedJobs = array_map(function($cron) {
            return [
                'schedule'  => "<fg=green>$cron->timeName</>",
                'event'     => $cron->eventName,
                'anonymous' => $cron->anonymously ? '<fg=gray>(Anonymous)</>' : ''
            ];
        }, $pools);

        $maxScheduleWidth = $this->getVisibleMaxWidth(array_column($formattedJobs, 'schedule'));

        foreach ($formattedJobs as $job) {
            $scheduleVisibleWidth = $this->getVisibleWidth($job['schedule']);
            $padding = str_repeat(' ', $maxScheduleWidth - $scheduleVisibleWidth + 2);

            $leftSide = "{$job['schedule']}$padding<fg=cyan>{$job['event']}</>";

            $rightSide = $job['anonymous'];

            $this->componentsTwoColumns($leftSide, $rightSide);
        }

        $count = count($pools);
        $summary = "<fg=blue;options=bold>Showing [$count] scheduled jobs.</>";

        $this->io->newLine();
        $this->writeRight($summary, 2);

        return self::SUCCESS;
    }
}
