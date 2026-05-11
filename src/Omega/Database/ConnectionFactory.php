<?php

declare(strict_types=1);

namespace Omega\Database;

use function ucfirst;

final class ConnectionFactory
{
    public static function make(array $config): ConnectionInterface
    {
        $driver = ucfirst($config['driver']);
        $class  = "\\Omega\\Database\\{$driver}Connection";

        return new $class($config);
    }
}
