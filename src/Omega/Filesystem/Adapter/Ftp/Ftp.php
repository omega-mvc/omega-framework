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

namespace Omega\Filesystem\Adapter\Ftp;

use RuntimeException;
use FTP\Connection;
use Omega\Filesystem\Adapter\FilesystemAdapterInterface;
use Omega\Filesystem\Contracts\FileFactoryInterface;
use Omega\Filesystem\Contracts\ListKeysAwareInterface;
use Omega\Filesystem\Contracts\SizeCalculatorInterface;
use Omega\Filesystem\File;
use Omega\Filesystem\Filesystem;
use Omega\Filesystem\Util\Path;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_merge_recursive;
use function basename;
use function count;
use function extension_loaded;
use function fopen;
use function ftp_chdir;
use function ftp_connect;
use function ftp_delete;
use function ftp_fget;
use function ftp_login;
use function ftp_mdtm;
use function ftp_mkdir;
use function ftp_pasv;
use function ftp_raw;
use function ftp_rawlist;
use function ftp_rename;
use function ftp_rmdir;
use function ftp_size;
use function ftp_ssl_connect;
use function ftp_fput;
use function function_exists;
use function fwrite;
use function ltrim;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function preg_split;
use function rewind;
use function rtrim;
use function sprintf;
use function strtotime;
use function strrpos;
use function stream_get_contents;
use function str_starts_with;
use function trim;

/**
 * Ftp Adapter Class.
 *
 * This class implements the `FilesystemAdapterInterface`, `FileFactoryInterface`,
 * `ListKeysAwareInterface`, and `SizeCalculatorInterface` to provide an FTP
 * filesystem adapter. It enables operations such as connecting to an FTP server,
 * listing directories, retrieving file sizes, and managing file paths on the server.
 *
 * This adapter supports features such as passive mode, SSL connections, and UTF-8
 * encoding. It allows for creating directories on the remote server if needed and
 * handles the necessary configuration parameters for establishing the FTP connection.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Adapter\Ftp
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class Ftp implements
    FilesystemAdapterInterface,
    FileFactoryInterface,
    ListKeysAwareInterface,
    SizeCalculatorInterface
{
    /**
     * The FTP connection resource.
     *
     * @var Connection|null Holds the FTP connection resource.
     */
    protected ?Connection $connection = null;

    /**
     * The hostnemae of FTP server.
     *
     * @var string Holds the hostname of the FTP server.
     */
    protected string $host;

    /**
     * The username fot the FTP authentication.
     *
     * @var string Holds the username for FTP authentication.
     */
    protected string $username;

    /**
     * The password for FTP authentication.
     *
     * @var string Holds the password for FTP authentication.
     */
    protected string $password;

    /**
     * The port number for the FTP connection.
     *
     * @var int Holds the port number for the FTP connection (`default: 21`).
     */
    protected int $port;

    /**
     * Indicates whether to use passive mode for the FTP connection.
     *
     * @var bool Indicates whether to use passive mode for the FTP connection.
     */
    protected bool $passive;

    /**
     * Indicates whether to create directories if they do not exist.
     *
     * @var bool Indicates whether to create directories if they do not exist.
     */
    protected bool $create;

    /**
     * The mode for file transfers.
     *
     * @var int Holds the mode for file transfers (`default: FTP_BINARY`).
     */
    protected int $mode;

    /**
     * Indicates whether to use SSL for the FTP connection.
     *
     * @var bool Indicates whether to use SSL for the FTP connection.
     */
    protected bool $ssl;

    /**
     * The timeout in seconds for the FTP connection.
     *
     * @var int Holds the timeout in seconds for the FTP connection (`default: 90`).
     */
    protected int $timeout;

    /**
     * Indicates whether to enable UTF-8 encoding for file names.
     *
     * @var bool Indicates whether to enable UTF-8 encoding for file names.
     */
    protected bool $utf8;

    /**
     * The default directory on the FTP server.
     *
     * @var string Holds the default directory on the FTP server.
     */
    protected string $directory;

    /**
     * Stores the file metadata.
     *
     * @var array Stores the file metadata.
     */
    protected array $fileData = [];

    /**
     * Ftp constructor.
     *
     * Initializes the FTP adapter with the specified configuration options.
     *
     * @param array $config Configuration options for the FTP connection.
     *                      Supported options include:
     *                      - 'host': The FTP server host (default: 'localhost').
     *                      - 'username': The FTP username (default: 'anonymous').
     *                      - 'password': The FTP password (default: '').
     *                      - 'port': The FTP port (default: 21).
     *                      - 'passive': Use passive mode (default: false).
     *                      - 'create': Create directories if not exists (default: false).
     *                      - 'mode': File transfer mode (default: FTP_BINARY).
     *                      - 'ssl': Use SSL (default: false).
     *                      - 'timeout': Connection timeout in seconds (default: 90).
     *                      - 'utf8': Enable UTF-8 encoding for file names (default: false).
     *
     * @throws RuntimeException if the FTP extension is not loaded.
     */
    public function __construct(array $config)
    {
        if (!extension_loaded('ftp')) {
            throw new RuntimeException(
                'Unable to use Omega\Filesystem\Adapter\Ftp as the FTP extension is not available.'
            );
        }

        $this->host      = $config['host']      ?? 'localhost';
        $this->username  = $config['username']  ?? 'anonymous';
        $this->password  = $config['password']  ?? '';
        $this->port      = $config['port']      ?? 21;
        $this->passive   = $config['passive']   ?? false;
        $this->create    = $config['create']    ?? false;
        $this->mode      = $config['mode']      ?? FTP_BINARY;
        $this->ssl       = $config['ssl']       ?? false;
        $this->timeout   = $config['timeout']   ?? 90;
        $this->utf8      = $config['utf8']      ?? false;
        $this->directory = $config['directory'] ?? '/';
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|bool
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $temp = fopen('php://temp', 'r+');

        if (!ftp_fget($this->getConnection(), $temp, $this->computePath($key), $this->mode)) {
            return false;
        }

        rewind($temp);
        $contents = stream_get_contents($temp);
        fclose($temp);

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, string $content): int|bool
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $path      = $this->computePath($key);
        $directory = Path::dirname($path);

        $this->ensureDirectoryExists($directory, true);

        $temp = fopen('php://temp', 'r+');
        $size = fwrite($temp, $content);
        rewind($temp);

        if (!ftp_fput($this->getConnection(), $path, $temp, $this->mode)) {
            fclose($temp);

            return false;
        }

        fclose($temp);

        return $size;
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $sourcePath = $this->computePath($sourceKey);
        $targetPath = $this->computePath($targetKey);

        $this->ensureDirectoryExists(Path::dirname($targetPath), true);

        return ftp_rename($this->getConnection(), $sourcePath, $targetPath);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $file  = $this->computePath($key);
        $lines = ftp_rawlist($this->getConnection(), '-al ' . Path::dirname($file));

        if (false === $lines) {
            return false;
        }

        $pattern = '{(?<!->) ' . preg_quote(basename($file)) . '( -> |$)}m';
        foreach ($lines as $line) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $keys = $this->fetchKeys();

        return $keys['keys'];
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(string $prefix = ''): array
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        preg_match('/(.*?)[^\/]*$/', $prefix, $match);
        $directory = rtrim($match[1], '/');

        $keys = $this->fetchKeys($directory, false);

        if ($directory === $prefix) {
            return $keys;
        }

        $filteredKeys = [];
        foreach (['keys', 'dirs'] as $hash) {
            $filteredKeys[$hash] = [];
            foreach ($keys[$hash] as $key) {
                if (str_starts_with($key, $prefix)) {
                    $filteredKeys[$hash][] = $key;
                }
            }
        }

        return $filteredKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|bool
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $mtime = ftp_mdtm($this->getConnection(), $this->computePath($key));

        // the server does not support this function
        if (-1 === $mtime) {
            throw new RuntimeException(
                'Server does not support ftp_mdtm function.'
            );
        }

        return $mtime;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        if ($this->isDirectory($key)) {
            return ftp_rmdir($this->getConnection(), $this->computePath($key));
        }

        return ftp_delete($this->getConnection(), $this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        return $this->isDir($this->computePath($key));
    }

    /**
     * Lists the contents of the specified directory.
     *
     * @param string $directory The directory to list. If empty, uses the default directory.
     * @return array An array containing 'keys' (file paths) and 'dirs' (subdirectory paths).
     * @throws RuntimeException if the directory does not exist and cannot be created.
     */
    public function listDirectory(string $directory = ''): array
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $directory = preg_replace('/^[\/]*([^\/].*)$/', '/$1', $directory);

        $items = $this->parseRawlist(
            ftp_rawlist($this->getConnection(), '-al ' . $this->directory . $directory) ?: []
        );

        $fileData = $dirs = [];
        foreach ($items as $itemData) {
            if ('..' === $itemData['name'] || '.' === $itemData['name']) {
                continue;
            }

            $item = [
                'name' => $itemData['name'],
                'path' => trim(($directory ? $directory . '/' : '') . $itemData['name'], '/'),
                'time' => $itemData['time'],
                'size' => $itemData['size'],
            ];

            if (str_starts_with($itemData['perms'], '-')) {
                $fileData[$item['path']] = $item;
            } elseif (str_starts_with($itemData['perms'], 'd')) {
                $dirs[] = $item['path'];
            }
        }

        $this->fileData = array_merge($fileData, $this->fileData);

        return [
            'keys' => array_keys($fileData),
            'dirs' => $dirs,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createFile(string $key, Filesystem $filesystem): File
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $file = new File($key, $filesystem);

        if (!array_key_exists($key, $this->fileData)) {
            $dirname   = Path::dirname($key);
            $directory = $dirname == '.' ? '' : $dirname;
            $this->listDirectory($directory);
        }

        if (isset($this->fileData[$key])) {
            $fileData = $this->fileData[$key];

            $file->setName($fileData['name']);
            $file->setSize($fileData['size']);
        }

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function size(string $key): int
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        if (-1 === $size = ftp_size($this->connection, $key)) {
            throw new RuntimeException(
                sprintf(
                    'Unable to fetch the size of "%s".',
                    $key
                )
            );
        }

        return $size;
    }

    /**
     * Ensures that the specified directory exists.
     *
     * @param string $directory The directory to check.
     * @param bool   $create    Whether to create the directory if it does not exist.
     * @return void
     * @throws RuntimeException if the directory does not exist and cannot be created.
     */
    protected function ensureDirectoryExists(string $directory, bool $create = false): void
    {
        if (!$this->isDir($directory)) {
            if (!$create) {
                throw new RuntimeException(
                    sprintf(
                        'The directory \'%s\' does not exist.',
                        $directory
                    )
                );
            }

            $this->createDirectory($directory);
        }
    }

    /**
     * Creates the specified directory on the FTP server.
     *
     * @param string $directory The directory to create.
     * @return void
     * @throws RuntimeException if the directory cannot be created.
     */
    protected function createDirectory(string $directory): void
    {
        // create parent directory if needed
        $parent = Path::dirname($directory);
        if (!$this->isDir($parent)) {
            $this->createDirectory($parent);
        }

        // create the specified directory
        $created = ftp_mkdir($this->getConnection(), $directory);
        if (false === $created) {
            throw new RuntimeException(
                sprintf(
                    'Could not create the \'%s\' directory.',
                    $directory
                )
            );
        }
    }

    /**
     * Checks if the specified path is a directory on the FTP server.
     *
     * @param string $directory The directory path to check.
     * @return bool True if the path is a directory, false otherwise.
     */
    private function isDir(string $directory): bool
    {
        if ('/' === $directory) {
            return true;
        }

        if (!@ftp_chdir($this->getConnection(), $directory)) {
            return false;
        }

        // change directory again to return in the base directory
        ftp_chdir($this->getConnection(), $this->directory);

        return true;
    }

    /**
     * Fetches all keys (file and directory paths) from the specified directory.
     *
     * @param string $directory The directory to fetch keys from.
     * @param bool   $onlyKeys  Whether to return only file keys.
     * @return array An array containing 'keys' (file paths) and 'dirs' (subdirectory paths).
     */
    private function fetchKeys(string $directory = '', $onlyKeys = true): array
    {
        $directory = preg_replace('/^[\/]*([^\/].*)$/', '/$1', $directory);

        $lines = ftp_rawlist($this->getConnection(), '-alR ' . $this->directory . $directory);

        if (false === $lines) {
            return ['keys' => [], 'dirs' => []];
        }

        $regexDir  = '/' . preg_quote($this->directory . $directory, '/') . '\/?(.+):$/u';
        $regexItem = '/^(?:([d\-\d])\S+)\s+\S+(?:(?:\s+\S+){5})?\s+(\S+)\s+(.+?)$/';

        $prevLine    = null;
        $directories = [];
        $keys        = ['keys' => [], 'dirs' => []];

        foreach ($lines as $line) {
            if ('' === $prevLine && preg_match($regexDir, $line, $match)) {
                $directory = $match[1];
                unset($directories[$directory]);
                if ($onlyKeys) {
                    $keys = [
                        'keys' => array_merge($keys['keys'], $keys['dirs']),
                        'dirs' => [],
                    ];
                }
            } elseif (preg_match($regexItem, $line, $tokens)) {
                $name = $tokens[3];

                if ('.' === $name || '..' === $name) {
                    continue;
                }

                $path = ltrim($directory . '/' . $name, '/');

                if ('d' === $tokens[1] || '<dir>' === $tokens[2]) {
                    $keys['dirs'][]     = $path;
                    $directories[$path] = true;
                } else {
                    $keys['keys'][] = $path;
                }
            }
            $prevLine = $line;
        }

        if ($onlyKeys) {
            $keys = [
                'keys' => array_merge($keys['keys'], $keys['dirs']),
                'dirs' => [],
            ];
        }

        foreach (array_keys($directories) as $directory) {
            $keys = array_merge_recursive($keys, $this->fetchKeys($directory, $onlyKeys));
        }

        return $keys;
    }

    /**
     * Parses the raw listing of files and directories from the FTP server.
     *
     * @param array $rawlist The raw list of files and directories.
     * @return array An array of parsed file and directory information.
     */
    private function parseRawlist(array $rawlist): array
    {
        $parsed = [];
        foreach ($rawlist as $line) {
            $infos = preg_split("/[\s]+/", $line, 9);

            if ($this->isLinuxListing($infos)) {
                $infos[7] = (strrpos($infos[7], ':') != 2) ? ($infos[7] . ' 00:00') : (date('Y') . ' ' . $infos[7]);
                if ('total' !== $infos[0]) {
                    $parsed[] = [
                        'perms' => $infos[0],
                        'num'   => $infos[1],
                        'size'  => $infos[4],
                        'time'  => strtotime($infos[5] . ' ' . $infos[6] . '. ' . $infos[7]),
                        'name'  => $infos[8],
                    ];
                }
            } elseif (count($infos) >= 4) {
                $isDir    = '<dir>' === $infos[2];
                $parsed[] = [
                    'perms' => $isDir ? 'd' : '-',
                    'num'   => '',
                    'size'  => $isDir ? '' : $infos[2],
                    'time'  => strtotime($infos[0] . ' ' . $infos[1]),
                    'name'  => $infos[3],
                ];
            }
        }

        return $parsed;
    }

    /**
     * Computes the full path for the specified key.
     *
     * @param string $key The key (file name) to compute the path for.
     * @return string The computed full path.
     */
    private function computePath(string $key): string
    {
        return rtrim($this->directory, '/') . '/' . $key;
    }

    /**
     * Checks if the adapter is connected to the FTP server.
     *
     * @return bool True if connected, false otherwise.
     */
    private function isConnected(): bool
    {
        if ($this->connection instanceof Connection) {
            $pwd = @ftp_pwd($this->connection);

            return $pwd !== false;
        }

        return false;
    }

    /**
     * Retrieves the current FTP connection, establishing it if necessary.
     *
     * @return Connection|null The current FTP connection or null if not connected.
     * @throws RuntimeException if unable to establish a connection.
     */
    private function getConnection(): ?Connection
    {
        if (!$this->isConnected()) {
            try {
                $this->connect();
            } catch (RuntimeException $e) {
                throw new RuntimeException(
                    'Unable to establish FTP connection:'
                    . $e->getMessage()
                );
            }
        }

        return $this->connection;
    }

    /**
     * Establishes a connection to the FTP server.
     *
     * @return void
     * @throws RuntimeException if unable to connect or authenticate.
     */
    private function connect(): void
    {
        if ($this->ssl && !function_exists('ftp_ssl_connect')) {
            throw new RuntimeException(
                'This server has no SSL-FTP available.'
            );
        }

        if (!$this->ssl) {
            $this->connection = ftp_connect($this->host, $this->port, $this->timeout);
        } else {
            $this->connection = ftp_ssl_connect($this->host, $this->port, $this->timeout);
        }

        if (!$this->connection) {
            throw new RuntimeException(
                sprintf('Could not connect to %s (port: %s).', $this->host, $this->port)
            );
        }

        if (!@ftp_login($this->connection, $this->username, $this->password)) {
            $this->close();

            throw new RuntimeException(sprintf('Could not login as %s.', $this->username));
        }

        if ($this->passive && !ftp_pasv($this->connection, true)) {
            $this->close();

            throw new RuntimeException('Could not turn passive mode on.');
        }

        if ($this->utf8) {
            ftp_raw($this->connection, 'OPTS UTF8 ON');
        }

        if ($this->directory !== '/') {
            try {
                $this->ensureDirectoryExists($this->directory, $this->create);
            } catch (RuntimeException $e) {
                $this->close();

                throw $e;
            }

            if (!ftp_chdir($this->connection, $this->directory)) {
                $this->close();

                throw new RuntimeException(sprintf('Could not change directory to %s.', $this->directory));
            }
        }
    }

    /**
     * Closes the FTP connection.
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->isConnected()) {
            ftp_close($this->connection);
        }
    }

    /**
     * Checks if the raw listing follows the Linux format.
     *
     * @param array $info The array of information from the raw listing.
     * @return bool True if the listing is in Linux format, false otherwise.
     */
    private function isLinuxListing(array $info): bool
    {
        return count($info) >= 9;
    }
}
