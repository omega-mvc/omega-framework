<?php

declare(strict_types=1);

namespace Omega\Template\VarExport\Compiler;

abstract class AbstractCompiler
{
    /**
     * @return string[]
     */
    abstract public function compile(mixed $data): array;
}
