<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Support;

final class FieldValidator
{
    public static function isNumeric(string $value): bool
    {
        return $value !== '' && ctype_digit($value);
    }

    public static function isNumericWithSpaces(string $value): bool
    {
        return trim($value) !== '' && ctype_digit(str_replace(' ', '', $value));
    }

    public static function isAmount(string $value): bool
    {
        return (bool) preg_match('/^\d{1,16}(\.\d{1,2})?$/', $value);
    }

    public static function isAlphabet(string $value): bool
    {
        return $value !== '' && (bool) preg_match('/^[A-Za-z]+$/', $value);
    }

    public static function isAlphanumericId(string $value): bool
    {
        return $value !== '' && (bool) preg_match('/^[A-Za-z0-9-]+$/', $value);
    }

    public static function isIso8601(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}([+-]\d{2}:\d{2}|Z)$/', $value);
    }

    public static function maxLength(string $value, int $max): bool
    {
        return strlen($value) <= $max;
    }
}
