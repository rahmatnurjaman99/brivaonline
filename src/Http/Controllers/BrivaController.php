<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RahmatNurjaman99\BrivaOnline\Contracts\InquiryResolver;
use RahmatNurjaman99\BrivaOnline\Contracts\PaymentResolver;
use RahmatNurjaman99\BrivaOnline\Http\Requests\InquiryRequest;
use RahmatNurjaman99\BrivaOnline\Http\Requests\PaymentRequest;
use RahmatNurjaman99\BrivaOnline\Repositories\InquiryRepository;
use RahmatNurjaman99\BrivaOnline\Repositories\TokenRepository;
use RahmatNurjaman99\BrivaOnline\Support\Env;
use RahmatNurjaman99\BrivaOnline\Support\Formatter;
use RahmatNurjaman99\BrivaOnline\Support\Signature;
use RahmatNurjaman99\BrivaOnline\Support\Timestamp;

class BrivaController
{
    public function accessToken(Request $request, TokenRepository $tokens): JsonResponse
    {
        $clientId = $this->getHeader($request, 'X-CLIENT-KEY');
        $timestamp = $this->getHeader($request, 'X-TIMESTAMP');
        $signature = $this->getHeader($request, 'X-SIGNATURE');

        if (!$clientId || !$timestamp || !$signature) {
            return $this->errorResponse(400, '4007300', 'Bad Request');
        }

        $publicKeys = $this->loadClientPublicKeys();
        $publicKey = $publicKeys[$clientId] ?? null;
        if (!$publicKey) {
            return $this->errorResponse(401, '4017300', 'Unauthorized Client');
        }

        $parsed = Timestamp::parseBri($timestamp);
        if (!$parsed || !Timestamp::withinSkew($parsed, 3600)) {
            return $this->errorResponse(401, '4017300', 'Unauthorized stringToSign');
        }
        if (!Signature::verifyAccessToken($clientId, $timestamp, $signature, $publicKey)) {
            return $this->errorResponse(401, '4017300', 'Unauthorized Signature');
        }

        $clientSecrets = $this->loadClientSecrets();
        if (!isset($clientSecrets[$clientId])) {
            return $this->errorResponse(401, '4017300', 'Unauthorized Client');
        }

        $ttl = (int) config('briva.token_ttl_seconds', 3600);
        $tokenData = $tokens->create($clientId, $ttl);

        return response()->json([
            'accessToken' => $tokenData['token'],
            'tokenType' => 'BearerToken',
            'expiresIn' => (string) $ttl,
        ]);
    }

    public function inquiry(Request $request, TokenRepository $tokens, InquiryResolver $resolver, InquiryRepository $inquiries): JsonResponse
    {
        $tokenData = $this->requireToken($request, $tokens);
        if ($tokenData instanceof JsonResponse) {
            return $tokenData;
        }

        $body = $request->json()->all();
        $validation = InquiryRequest::validate($body);
        if (!$validation['ok']) {
            return $this->inquiryErrorResponse(400, '4002402', $validation['message']);
        }

        $partnerError = $this->validatePartnerId($request, '4042416');
        if ($partnerError) {
            return $partnerError;
        }

        $headersValid = $this->validateTransactionSignature($request, $body, $tokenData['token'], $tokenData['client_id']);
        if ($headersValid instanceof JsonResponse) {
            return $headersValid;
        }

        try {
            $payload = $resolver->resolve($body);
        } catch (\Throwable $ex) {
            return $this->inquiryErrorResponse(502, '5022400', 'Inquiry service unavailable');
        }
        if (!is_array($payload)) {
            return $this->inquiryErrorResponse(502, '5022400', 'Inquiry service unavailable');
        }

        $inquiryRequestId = (string) ($body['inquiryRequestId'] ?? '');
        $virtualAccountData = $payload['virtualAccountData'] ?? [];
        $billShortName = is_array($virtualAccountData) ? (string) ($virtualAccountData['additionalInfo']['billShortName'] ?? '') : '';
        $billCode = is_array($virtualAccountData) ? (string) ($virtualAccountData['additionalInfo']['billCode'] ?? '') : '';
        $billInfo1 = is_array($virtualAccountData) ? (string) ($virtualAccountData['additionalInfo']['billInfo1'] ?? '') : '';
        $billInfo4 = is_array($virtualAccountData) ? (string) ($virtualAccountData['additionalInfo']['billInfo4'] ?? '') : '';
        $slug = Formatter::slugCompact($billShortName)
            . Formatter::slugCompact($billCode)
            . Formatter::slugCompact($billInfo1)
            . Formatter::slugCompact($billInfo4);

        $totalAmount = is_array($virtualAccountData) ? ($virtualAccountData['totalAmount'] ?? []) : [];
        $totalAmountValue = is_array($totalAmount) ? ($totalAmount['value'] ?? null) : null;
        $totalAmountCurrency = is_array($totalAmount) ? ($totalAmount['currency'] ?? null) : null;

        if ($slug !== '' && $inquiryRequestId !== '' && is_array($virtualAccountData)) {
            $inquiries->upsert(
                $inquiryRequestId,
                $inquiryRequestId,
                (string) ($virtualAccountData['customerNo'] ?? $body['customerNo'] ?? ''),
                $slug,
                $billShortName,
                $billCode,
                $billInfo1,
                $billInfo4,
                $totalAmountValue !== null ? (string) $totalAmountValue : null,
                $totalAmountCurrency !== null ? (string) $totalAmountCurrency : null
            );
        }

        return response()->json($payload);
    }

    public function payment(Request $request, TokenRepository $tokens, InquiryRepository $inquiries, PaymentResolver $resolver): JsonResponse
    {
        $tokenData = $this->requireToken($request, $tokens);
        if ($tokenData instanceof JsonResponse) {
            return $tokenData;
        }

        $body = $request->json()->all();
        $expectedAmount = null;
        $paymentRequestId = (string) ($body['paymentRequestId'] ?? '');
        if ($paymentRequestId !== '') {
            $record = $inquiries->findByPaymentRequestId($paymentRequestId);
            if ($record) {
                $expectedAmount = [
                    'value' => $record['total_amount_value'] ?? null,
                    'currency' => $record['total_amount_currency'] ?? null,
                ];
            }
        }
        $validation = PaymentRequest::validate($body, $expectedAmount);
        if (!$validation['ok']) {
            $code = str_starts_with($validation['message'], 'Invalid Field Format')
                ? '4002501'
                : '4002502';
            return $this->paymentErrorResponse(400, $code, $validation['message']);
        }

        $partnerError = $this->validatePartnerId($request, '4042516');
        if ($partnerError) {
            return $partnerError;
        }

        $headersValid = $this->validateTransactionSignature($request, $body, $tokenData['token'], $tokenData['client_id']);
        if ($headersValid instanceof JsonResponse) {
            return $headersValid;
        }

        try {
            $payload = $resolver->resolve($body);
        } catch (\Throwable $ex) {
            return $this->paymentErrorResponse(502, '5022500', 'Payment service unavailable');
        }

        if (!is_array($payload)) {
            return $this->paymentErrorResponse(502, '5022500', 'Payment service unavailable');
        }

        return response()->json($payload);
    }

    private function getHeader(Request $request, string $name): ?string
    {
        $value = $request->headers->get($name);
        return $value !== null ? (string) $value : null;
    }

    private function requireToken(Request $request, TokenRepository $tokens)
    {
        $auth = $request->header('Authorization');
        if (!$auth || stripos($auth, 'Bearer ') !== 0) {
            return $this->errorResponse(401, '4017301', 'Invalid Token (B2B)');
        }
        $token = trim(substr($auth, 7));
        $data = $tokens->validate($token);
        if (!$data) {
            return $this->errorResponse(401, '4017301', 'Invalid Token (B2B)');
        }
        return $data + ['token' => $token];
    }

    private function validatePartnerId(Request $request, string $notFoundCode): ?JsonResponse
    {
        $partnerServiceId = trim((string) config('briva.partner_service_id'));
        if ($partnerServiceId === '') {
            return null;
        }
        $header = $this->getHeader($request, 'X-PARTNER-ID');
        if (!$header || $header !== $partnerServiceId) {
            return $this->errorResponse(404, $notFoundCode, 'Partner Not Found');
        }
        return null;
    }

    private function validateTransactionSignature(Request $request, array $body, string $accessToken, string $clientId): ?JsonResponse
    {
        $timestamp = $this->getHeader($request, 'X-TIMESTAMP');
        $signature = $this->getHeader($request, 'X-SIGNATURE');
        if (!$timestamp || !$signature) {
            return $this->errorResponse(400, '4007300', 'Bad Request');
        }
        $parsed = Timestamp::parseBri($timestamp);
        if (!$parsed || !Timestamp::withinSkew($parsed, 3600)) {
            return $this->errorResponse(401, '4017300', 'Unauthorized stringToSign');
        }

        $clientSecrets = $this->loadClientSecrets();
        $clientSecret = $clientSecrets[$clientId] ?? null;
        if (!$clientSecret) {
            return $this->errorResponse(401, '4017300', 'Unauthorized Client');
        }

        $expected = Signature::signTransaction(
            $request->method(),
            '/' . ltrim($request->path(), '/'),
            $accessToken,
            $body,
            $timestamp,
            $clientSecret
        );

        if (!hash_equals($expected, $signature)) {
            return $this->errorResponse(401, '4017300', 'Unauthorized Signature');
        }

        return null;
    }

    private function loadClientPublicKeys(): array
    {
        $keys = Env::jsonMap((string) config('briva.client_public_keys_json'));
        if ($keys) {
            return array_map([Env::class, 'normalizePem'], $keys);
        }

        $fallbackKey = Env::normalizePem((string) config('briva.client_public_key_pem'));
        $fallbackId = (string) config('briva.client_public_key_id');
        if ($fallbackKey !== '' && $fallbackId !== '') {
            return [$fallbackId => $fallbackKey];
        }

        return [];
    }

    private function loadClientSecrets(): array
    {
        $secrets = Env::jsonMap((string) config('briva.client_secrets_json'));
        if ($secrets) {
            return $secrets;
        }

        $fallbackSecret = (string) config('briva.client_secret');
        $fallbackId = (string) config('briva.client_secret_id');
        if ($fallbackSecret !== '' && $fallbackId !== '') {
            return [$fallbackId => $fallbackSecret];
        }

        return [];
    }

    private function errorResponse(int $status, string $code, string $message): JsonResponse
    {
        return response()->json(['responseCode' => $code, 'responseMessage' => $message], $status);
    }

    private function inquiryErrorResponse(int $status, string $code, string $message): JsonResponse
    {
        return response()->json(['responseCode' => $code, 'responseMessage' => $message], $status);
    }

    private function paymentErrorResponse(int $status, string $code, string $message): JsonResponse
    {
        return response()->json(['responseCode' => $code, 'responseMessage' => $message], $status);
    }
}
