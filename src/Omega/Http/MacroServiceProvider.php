<?php

declare(strict_types=1);

namespace Omega\Http;

use Closure;
use Omega\Container\AbstractServiceProvider;
use Omega\Http\Upload\UploadFile;
use Omega\Validator\Validator;

class MacroServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Request::macro(
            'validate',
            fn (?Closure $rule = null, ?Closure $filter = null) => Validator::make(
                $this->{'all'}(),
                $rule,
                $filter
            )
        );

        Request::macro(
            'upload',
            function ($fileName) {
                $files = $this->{'getFile'}();

                return new UploadFile($files[$fileName]);
            }
        );
    }
}
