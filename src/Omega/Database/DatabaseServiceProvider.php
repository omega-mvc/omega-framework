<?php

declare(strict_types=1);

namespace Omega\Database;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Database\Query\Query;
use Omega\Database\Schema\Schema;
use Omega\Database\Schema\SchemaConnection;
use Omega\Container\AbstractServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    /**
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function boot(): void
    {
        $configs     = $this->app->get('config');
        $default     = $configs['db.default'];
        $connections = $configs['db.connections'];

        $dsn         = $connections[$default];

        $this->app->set('dsn.default', $default);
        $this->app->set('dsn.connections', $connections);
        $this->app->set('dsn.sql', $dsn);

        $this->app->set(
            'database',
            fn () => ConnectionFactory::make($this->app->get('dsn.sql'))
        );

        $this->app->set(
            SchemaConnection::class,
            fn () => new SchemaConnection($this->app->get('dsn.sql'))
        );

        $this->app->set(
            'Query',
            fn () => new Query($this->app->get('database'))
        );

        $this->app->set(
            'Schema',
            fn () => new Schema($this->app->get(SchemaConnection::class), $this->app->get('dsn.sql')['database'])
        );

        $this->app->set(
            DatabaseManager::class,
            fn () => new DatabaseManager($this->app->get('dsn.connections'))
                ->setDefaultConnection($this->app->get('database'))
        );
    }
}
