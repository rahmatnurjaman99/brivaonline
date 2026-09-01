<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Repositories;

use Illuminate\Support\Facades\DB;

class ExternalIdRepository
{
    public function isDuplicate(string $partnerId, string $externalId, int $windowMinutes): bool
    {
        DB::table('external_ids')
            ->where('created_at', '<', now()->subMinutes($windowMinutes))
            ->delete();

        return DB::table('external_ids')
            ->where('partner_id', $partnerId)
            ->where('external_id', $externalId)
            ->exists();
    }

    public function record(string $partnerId, string $externalId): void
    {
        DB::table('external_ids')->insert([
            'partner_id' => $partnerId,
            'external_id' => $externalId,
            'created_at' => now(),
        ]);
    }
}
