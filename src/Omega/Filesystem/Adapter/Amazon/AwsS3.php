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

namespace Omega\Filesystem\Adapter\Amazon;

use Countable;
use Exception;
use RuntimeException;
use Aws\S3\S3Client;
use Omega\Filesystem\Util\Size;

use function array_key_exists;
use function count;
use function is_array;
use function is_resource;
use function rtrim;
use function sprintf;
use function strtotime;

/**
 * Amazon S3 adapter.
 *
 * This class implements the necessary methods to interact with
 * Amazon S3.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Adapter\Amazon
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class AwsS3 extends AbstractAmazonS3
{
    /**
     * Initializes the AsyncAwsS3 adapter with the provided configuration.
     *
     * @param array $config Configuration options for connecting to S3.
     *                      Must include 'bucket', 'key', and 'secret'.
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    protected function createClient(array $config): object
    {
        return new S3Client([
            'version'     => 'latest',
            'region'      => $config['region'] ?? 'us-west-2',
            'credentials' => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
                'token'  => $config['token'] ?? null,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|bool
    {
        $this->ensureBucketExists();
        $options = $this->getOptions($key);

        try {
            $object = $this->service->getObject($options);
            if (!array_key_exists($key, $this->content) || !is_array($this->content[$key])) {
                $this->content[$key] = [];
            }

            $this->content[$key]['ContentType'] = $object->get('ContentType');

            return (string) $object->get('Body');
        } catch (Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, string $content): int|bool
    {
        $this->ensureBucketExists();
        $options = $this->getOptions($key, ['Body' => $content]);

        /*
         * If the ContentType was not already set in the metadata, then we autodetect
         * it to prevent everything being served up as binary/octet-stream.
         */
        if (!isset($options['ContentType']) && $this->detectContentType) {
            $options['ContentType'] = $this->guessContentType($content);
        }

        try {
            $this->service->putObject($options);

            if (is_resource($content)) {
                return Size::fromResource($content);
            }

            return Size::fromContent($content);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return $this->service->doesObjectExist($this->bucket, $this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|bool
    {
        try {
            $result = $this->service->headObject($this->getOptions($key));

            return strtotime($result['LastModified']);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function size(string $key): int|false
    {
        try {
            $result = $this->service->headObject($this->getOptions($key));

            return $result['ContentLength'];
        } catch (Exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(string $prefix = ''): array
    {
        $this->ensureBucketExists();

        $options = ['Bucket' => $this->bucket];
        if ($prefix != '') {
            $options['Prefix'] = $this->computePath($prefix);
        } elseif (!empty($this->options['directory'])) {
            $options['Prefix'] = $this->options['directory'];
        }

        $keys = [];
        $iter = $this->service->getIterator('ListObjects', $options);
        foreach ($iter as $file) {
            $keys[] = $this->computeKey($file['Key']);
        }

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        $result = $this->service->listObjects([
            'Bucket'  => $this->bucket,
            'Prefix'  => rtrim($this->computePath($key), '/') . '/',
            'MaxKeys' => 1,
        ]);
        if (isset($result['Contents'])) {
            if (is_array($result['Contents']) || $result['Contents'] instanceof Countable) {
                return count($result['Contents']) > 0;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function ensureBucketExists(): bool
    {
        if ($this->bucketExists) {
            return true;
        }

        if ($this->bucketExists = $this->service->doesBucketExist($this->bucket)) {
            return true;
        }

        if (!$this->options['create']) {
            throw new RuntimeException(
                sprintf(
                    'The configured bucket "%s" does not exist.',
                    $this->bucket
                )
            );
        }

        $this->service->createBucket([
            'Bucket'             => $this->bucket,
            'LocationConstraint' => $this->service->getRegion(),
        ]);
        $this->bucketExists = true;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(string $key): string|false
    {
        try {
            $result = $this->service->headObject($this->getOptions($key));

            return $result['ContentType'];
        } catch (Exception) {
            return false;
        }
    }
}
