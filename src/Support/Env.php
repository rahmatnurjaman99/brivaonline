<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Support;

final class Env
{
    public static function normalizePem(string $value): string
    {
        $cleaned = trim($value);
        if ($cleaned === '') {
            return '';
        }
        if (str_contains($cleaned, '\\n')) {
            return str_replace('\\n', "\n", $cleaned);
        }
        if (str_contains($cleaned, '-----BEGIN') && str_contains($cleaned, '-----END') && !str_contains($cleaned, "\n")) {
            if (preg_match('/(-----BEGIN [^-]+-----)(.+?)(-----END [^-]+-----)/', $cleaned, $matches)) {
                $header = $matches[1];
                $body = preg_replace('/\s+/', '', $matches[2] ?? '') ?: '';
                $footer = $matches[3];
                return $header . "\n" . $body . "\n" . $footer;
            }
        }
        return $cleaned;
    }

    public static function jsonMap(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $result = [];
        foreach ($decoded as $key => $value) {
            $result[(string) $key] = (string) $value;
        }
        return $result;
    }
}
