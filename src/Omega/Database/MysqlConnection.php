<?php

declare(strict_types=1);

namespace Omega\Database;

use Omega\Database\Exceptions\InvalidConfigurationException;

final class MysqlConnection extends AbstractConnection
{
    protected function buildDsn(): string
    {
        $host = $this->configs['host'];
        $db   = $this->configs['database'];
        $port = $this->configs['port'] ?? 3306;
        $char = $this->configs['charset'] ?? 'utf8mb4';

        if (!$host || !$db) {
            throw new InvalidConfigurationException('MySQL requires host and database.');
        }

        return "mysql:host={$host};port={$port};dbname={$db};charset={$char}";
    }
}
