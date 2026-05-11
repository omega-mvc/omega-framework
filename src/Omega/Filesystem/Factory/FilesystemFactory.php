<?php

/**
 * Part of Omega - Filesystem Package.
 * php version 8.3
 *
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */

declare(strict_types=1);

namespace Omega\Filesystem\Factory;

use Omega\Filesystem\Adapter\FilesystemAdapterInterface;
use Omega\Filesystem\Adapter\Amazon\AwsS3;
use Omega\Filesystem\Adapter\Amazon\AsyncAwsS3;
use Omega\Filesystem\Adapter\Ftp\Ftp;
use Omega\Filesystem\Adapter\Local\Local;
use Omega\Filesystem\Exception\UnsupportedAdapterException;

/**
 * Filesystem factory class.
 *
 * The `FilesystemFactory` class is responsible for registering and creating session
 * drivers based on configurations. It acts as a factory for different session
 * drivers and provides a flexible way to connect to various session systems.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Factory
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class FilesystemFactory implements FilesystemFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(?array $config = null): FilesystemAdapterInterface
    {
        if (!isset($config['type'])) {
            throw new UnsupportedAdapterException(
                'Type is not defined.'
            );
        }

        return match ($config['type']) {
            's3'      => new AwsS3($config),
            'asyncs3' => new AsyncAwsS3($config),
            'ftp'     => new Ftp($config),
            'local'   => new Local($config['path']),
            default   => throw new UnsupportedAdapterException('Unrecognised type.')
        };
    }
}
