<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Support;

use DateTimeImmutable;
use DateTimeZone;

final class Timestamp
{
    public static function briNow(): string
    {
        $tz = new DateTimeZone('Asia/Jakarta');
        $now = new DateTimeImmutable('now', $tz);
        $base = $now->format('Y-m-d\TH:i:s.000');
        $offset = $now->format('P');
        return $base . $offset;
    }

    public static function isoNow(): string
    {
        $now = new DateTimeImmutable('now');
        return $now->format(DateTimeImmutable::ATOM);
    }

    public static function parseBri(string $timestamp): ?DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.000[+-]\d{2}:\d{2}$/', $timestamp)) {
            return null;
        }
        $normalized = substr($timestamp, 0, -3) . substr($timestamp, -2);
        $dt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.000O', $normalized);
        return $dt ?: null;
    }

    public static function withinSkew(DateTimeImmutable $timestamp, int $maxSkewSeconds): bool
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $delta = abs($now->getTimestamp() - $timestamp->setTimezone(new DateTimeZone('UTC'))->getTimestamp());
        return $delta <= $maxSkewSeconds;
    }
}
