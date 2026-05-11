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

namespace Omega\Filesystem\Stream;

use function str_contains;
use function substr;
use function trim;

/**
 * Class StreamMode.
 *
 * This class represents a mode for file streams, encapsulating the logic
 * required to interpret and validate different stream modes. It allows
 * you to check if the mode permits reading, writing, and file operations
 * like opening existing files or creating new ones.
 *
 * @category    Omega
 * @package     Filesystem
 * @subpackage  Stream
 * @link        https://omegamvc.github.io
 * @author      Adriano Giovannini <agisoftt@gmail.com>
 * @copyright   Copyright (c) 2024 - 2025 Adriano Giovannini (https://omegamvc.github.io)
 * @license     https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version     1.0.0
 */
class StreamMode
{
    /**
     * The base mode character (r, w, a, etc.).
     *
     * @var string Holds the base mode character (r, w, a, etc.).
     */
    private string $base;

    /**
     * Indicates if the mode includes a '+' character for reading and writing.
     *
     * @var bool Indicates if the mode includes a '+' character for reading and writing.
     */
    private bool $plus;

    /**
     * Additional flags from the mode string (e.g., b for binary).
     *
     * @var string Holds the additional flags from the mode string (e.g., b for binary).
     */
    private string $flag;

    /**
     * StreamMode constructor.
     *
     * @param string $mode A stream mode string compatible with fopen().
     * @return void
     */
    public function __construct(
        private readonly string $mode
    ) {
        $mode = substr($mode, 0, 3);
        $rest = substr($mode, 1);

        $this->base = substr($mode, 0, 1);
        $this->plus = str_contains($rest, '+');
        $this->flag = trim($rest, '+');
    }

    /**
     * Returns the underlying mode.
     *
     * @return string The mode string as provided during construction.
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Indicates whether the mode allows reading.
     *
     * @return bool TRUE if the mode permits reading; otherwise, FALSE.
     */
    public function allowsRead(): bool
    {
        if ($this->plus) {
            return true;
        }

        return 'r' === $this->base;
    }

    /**
     * Indicates whether the mode allows writing.
     *
     * @return bool TRUE if the mode permits writing; otherwise, FALSE.
     */
    public function allowsWrite(): bool
    {
        if ($this->plus) {
            return true;
        }

        return 'r' !== $this->base;
    }

    /**
     * Indicates whether the mode allows opening an existing file.
     *
     * @return bool TRUE if the mode permits opening an existing file; otherwise, FALSE.
     */
    public function allowsExistingFileOpening(): bool
    {
        return 'x' !== $this->base;
    }

    /**
     * Indicates whether the mode allows creating a new file.
     *
     * @return bool TRUE if the mode permits creating a new file; otherwise, FALSE.
     */
    public function allowsNewFileOpening(): bool
    {
        return 'r' !== $this->base;
    }

    /**
     * Indicates whether the mode implies deleting the existing content
     * of the file when it already exists.
     *
     * @return bool TRUE if the mode implies content deletion; otherwise, FALSE.
     */
    public function impliesExistingContentDeletion(): bool
    {
        return 'w' === $this->base;
    }

    /**
     * Indicates whether the mode implies positioning the cursor at
     * the beginning of the file.
     *
     * @return bool TRUE if the cursor is positioned at the beginning; otherwise, FALSE.
     */
    public function impliesPositioningCursorAtTheBeginning(): bool
    {
        return 'a' !== $this->base;
    }

    /**
     * Indicates whether the mode implies positioning the cursor at
     * the end of the file.
     *
     * @return bool TRUE if the cursor is positioned at the end; otherwise, FALSE.
     */
    public function impliesPositioningCursorAtTheEnd(): bool
    {
        return 'a' === $this->base;
    }

    /**
     * Indicates whether the stream is in binary mode.
     *
     * @return bool TRUE if the mode is binary; otherwise, FALSE.
     */
    public function isBinary(): bool
    {
        return 'b' === $this->flag;
    }

    /**
     * Indicates whether the stream is in text mode.
     *
     * @return bool TRUE if the mode is text; otherwise, FALSE.
     */
    public function isText(): bool
    {
        return false === $this->isBinary();
    }
}
