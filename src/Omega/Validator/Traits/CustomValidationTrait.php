<?php

declare(strict_types=1);

namespace Omega\Validator\Traits;

/**
 * Trait contain Custom validation.
 */
trait CustomValidationTrait
{
    /**
     * Check the field is contain in input fileds.
     * Custom rule to prevent runtime error when validation is empty.
     *
     * @param string                $field
     * @param array<string, string> $input
     * @param array<string, string> $params
     * @param mixed                 $value
     *
     * @return bool
     */
    protected function validate_($field, array $input, array $params, $value)
    {
        return array_key_exists($field, $input);
    }
}
