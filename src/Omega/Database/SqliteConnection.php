<?php

declare(strict_types=1);

namespace Omega\Database;

use Omega\Database\Exceptions\InvalidConfigurationException;

final class SqliteConnection extends AbstractConnection
{
    protected function buildDsn(): string
    {
        $path = $this->configs['path'] ?? $this->configs['database'];

        if (!$path) {
            throw new InvalidConfigurationException('SQLite requires path.');
        }

        if ($path === ':memory:') {
            return 'sqlite::memory:';
        }

        if (!realpath($path)) {
            throw new InvalidConfigurationException('SQLite requires valid file path.');
        }

        return 'sqlite:' . realpath($path);
    }
}
