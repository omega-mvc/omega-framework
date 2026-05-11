<?php

declare(strict_types=1);

namespace Omega\Database;

use Omega\Database\Exceptions\InvalidConfigurationException;

final class PgsqlConnection extends AbstractConnection
{
    protected function buildDsn(): string
    {
        $host = $this->configs['host'];
        $db   = $this->configs['database'];
        $port = $this->configs['port'] ?? 5432;
        $char = $this->configs['charset'] ?? 'utf8';

        if (!$host || !$db) {
            throw new InvalidConfigurationException('PostgreSQL requires host and database.');
        }

        return "pgsql:host={$host};port={$port};dbname={$db};options='--client_encoding={$char}'";
    }
}
