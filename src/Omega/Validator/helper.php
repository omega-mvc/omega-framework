<?php

declare(strict_types=1);

if (!function_exists('vr')) {
    /**
     * Alias for validation rule,
     * return string validation rule.
     */
    function vr(): Omega\Validator\Rule\Valid
    {
        return new Omega\Validator\Rule\Valid();
    }
}

if (!function_exists('fr')) {
    /**
     * Alias for filter rule,
     * return string filter rule.
     */
    function fr(): Omega\Validator\Rule\Filter
    {
        return new Omega\Validator\Rule\Filter();
    }
}

if (!function_exists('validate')) {
    /**
     * Alias for validator.
     *
     * @param array<string, mixed> $field Field input
     */
    function validate($field): Omega\Validator\Validator
    {
        return new Omega\Validator\Validator($field);
    }
}
