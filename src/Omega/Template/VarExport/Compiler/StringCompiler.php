<?php

declare(strict_types=1);

namespace Omega\Template\VarExport\Compiler;

use function var_export;

class StringCompiler extends AbstractCompiler
{
    public function compile(mixed $data): array
    {
        return [var_export($data, true)];
    }
}
