<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Config\ConfigRepository;
use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Throwable;

use function file_exists;
use function file_put_contents;
use function unlink;
use function var_export;

#[AsCommand(
    name: 'config:cache',
    description: 'Create a cache file for faster configuration loading'
)]
final class ConfigCacheCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(): int
    {
        try {
            new ConfigBootstrapper()->bootstrap($this->app);

            $cachePath = $this->app->getApplicationCachePath() . 'config.php';
            if (file_exists($cachePath)) {
                @unlink($cachePath);
            }

            $config = $this->app->get(ConfigRepository::class)->getAll();
            $exported = '<?php return ' . var_export($config, true) . ';' . PHP_EOL;

            if (file_put_contents($cachePath, $exported) === false) {
                $this->io->error('Failed to write the configuration cache file.');
                return self::FAILURE;
            }

            $this->io->info('Configuration cached successfully.');
            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->io->error('An error occurred while caching configuration: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
