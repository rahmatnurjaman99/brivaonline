<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Repositories;

use Illuminate\Support\Facades\DB;

class TokenRepository
{
    public function create(string $clientId, int $ttlSeconds): array
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = time() + $ttlSeconds;

        DB::table('tokens')->where('token', $token)->delete();
        DB::table('tokens')->insert([
            'token' => $token,
            'client_id' => $clientId,
            'expires_at' => $expiresAt,
        ]);

        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    public function validate(string $token): ?array
    {
        $row = DB::table('tokens')->where('token', $token)->first();
        if (!$row) {
            return null;
        }
        if (time() >= (int) $row->expires_at) {
            DB::table('tokens')->where('token', $token)->delete();
            return null;
        }
        return ['token' => $row->token, 'client_id' => $row->client_id];
    }
}
