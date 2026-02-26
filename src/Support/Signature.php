<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Support;

use RuntimeException;

final class Signature
{
    public static function signAccessToken(string $clientId, string $timestamp, string $privateKeyPem): string
    {
        $message = $clientId . '|' . $timestamp;
        $signature = '';
        $ok = openssl_sign($message, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            throw new RuntimeException('Failed to sign access token request.');
        }
        return base64_encode($signature);
    }

    public static function verifyAccessToken(string $clientId, string $timestamp, string $signature, string $publicKeyPem): bool
    {
        $message = $clientId . '|' . $timestamp;
        $decoded = base64_decode($signature, true);
        if ($decoded === false) {
            return false;
        }
        return openssl_verify($message, $decoded, $publicKeyPem, OPENSSL_ALGO_SHA256) === 1;
    }

    public static function signTransaction(
        string $method,
        string $path,
        string $accessToken,
        array $body,
        string $timestamp,
        string $clientSecret
    ): string {
        $payload = json_encode($body, JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            $payload = '{}';
        }
        $bodyHash = hash('sha256', $payload);
        $stringToSign = strtoupper($method) . ':' . $path . ':' . $accessToken . ':' . $bodyHash . ':' . $timestamp;
        $signature = hash_hmac('sha512', $stringToSign, $clientSecret, true);
        return base64_encode($signature);
    }
}
