<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Support;

final class Formatter
{
    public static function formatAmountValue($value): string
    {
        if ($value === null) {
            return '';
        }
        $valueStr = trim((string) $value);
        if ($valueStr === '') {
            return '';
        }
        if (str_contains($valueStr, '.')) {
            [$intPart, $decimalPart] = explode('.', $valueStr, 2);
            if (strlen($decimalPart) === 2) {
                return $intPart . '.' . $decimalPart;
            }
            if (strlen($decimalPart) === 1) {
                return $intPart . '.' . $decimalPart . '0';
            }
            return $intPart . '.' . substr($decimalPart, 0, 2);
        }
        return $valueStr . '.00';
    }

    public static function mapCurrency($code): string
    {
        $codeStr = trim((string) $code);
        if ($codeStr === '') {
            return '';
        }
        return $codeStr === '360' ? 'IDR' : $codeStr;
    }

    public static function slugCompact($value): string
    {
        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return '';
        }
        $slug = preg_replace('/[^a-z0-9]+/', '-', $raw) ?: '';
        $slug = trim($slug, '-');
        return str_replace('-', '', $slug);
    }
}
