<?php

declare(strict_types=1);

namespace Omega\Security;

use Omega\Container\Exceptions\CircularAliasException;
use Omega\Security\Hashing\Argon2IdHasher;
use Omega\Security\Hashing\ArgonHasher;
use Omega\Security\Hashing\BcryptHasher;
use Omega\Security\Hashing\DefaultHasher;
use Omega\Security\Hashing\HashManager;
use Omega\Container\AbstractServiceProvider;
use Omega\Config\Facade\Config;

class HashServiceProvider extends AbstractServiceProvider
{
    /**
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     */
    public function boot(): void
    {
        $this->app->set('hash.bcrypt', function (): BcryptHasher {
            return new BcryptHasher()
                ->setRounds(
                    Config::get('BCRYPT_ROUNDS', 12)
                );
        });
        $this->app->set('hash.argon', value: function (): ArgonHasher {
            return new ArgonHasher()
                ->setMemory(1024)
                ->setTime(2)
                ->setThreads(2);
        });
        $this->app->set('hash.argon2id', fn (): Argon2IdHasher => new Argon2IdHasher());
        $this->app->set('hash.default', fn (): DefaultHasher => new DefaultHasher());

        $this->app->set('hash', function (): HashManager {
            $hash = new HashManager();
            $hash->setDefaultDriver($this->app->get('hash.bcrypt'));
            $hash->setDriver('bcrypt', $this->app->get('hash.bcrypt'));
            $hash->setDriver('argon', $this->app->get('hash.argon'));
            $hash->setDriver('argon2id', $this->app->get('hash.argon2id'));
            $hash->setDriver('default', $this->app->get('hash.default'));

            return $hash;
        });
    }
}
