<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Clients;

use Illuminate\Support\Facades\Http;
use RahmatNurjaman99\BrivaOnline\Contracts\BrivaClient;
use RahmatNurjaman99\BrivaOnline\Support\Env;
use RahmatNurjaman99\BrivaOnline\Support\Signature;
use RahmatNurjaman99\BrivaOnline\Support\Timestamp;

class SnapClient implements BrivaClient
{
    public function accessToken(): array
    {
        $timestamp = Timestamp::briNow();
        $clientId = (string) config('briva.client_id');
        $privateKey = Env::normalizePem((string) config('briva.private_key_pem'));
        $signature = Signature::signAccessToken($clientId, $timestamp, $privateKey);

        $response = Http::withHeaders([
            'X-TIMESTAMP' => $timestamp,
            'X-CLIENT-KEY' => $clientId,
            'X-SIGNATURE' => $signature,
        ])->post(rtrim((string) config('briva.base_url'), '/') . '/snap/v1.0/access-token/b2b', [
            'grantType' => 'client_credentials',
        ]);

        return [
            'status' => $response->status(),
            'body' => $response->json(),
            'raw' => $response->body(),
        ];
    }

    public function buildSignedHeaders(string $method, string $path, string $accessToken, array $body): array
    {
        $timestamp = Timestamp::isoNow();
        $signature = Signature::signTransaction(
            $method,
            $path,
            $accessToken,
            $body,
            $timestamp,
            (string) config('briva.client_secret')
        );

        return [
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signature,
            'CHANNEL-ID' => (string) config('briva.channel_id'),
            'X-PARTNER-ID' => (string) config('briva.partner_id'),
            'X-EXTERNAL-ID' => (string) random_int(100000000, 999999999),
        ];
    }
}
