<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RahmatNurjaman99\BrivaOnline\Contracts\InquiryResolver;
use RahmatNurjaman99\BrivaOnline\Contracts\PaymentResolver;
use RahmatNurjaman99\BrivaOnline\Http\Requests\InquiryRequest;
use RahmatNurjaman99\BrivaOnline\Http\Requests\PaymentRequest;
use RahmatNurjaman99\BrivaOnline\Repositories\ExternalIdRepository;
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

    public function testSignAccessToken(Request $request): JsonResponse
    {
        $privateKey = Env::loadPem((string) config('briva.private_key_pem'));
        if ($privateKey === '') {
            return response()->json(['detail' => 'CLIENT_PRIVATE_KEY_PEM not configured'], 400);
        }

        $payload = $request->json()->all();
        $clientId = (string) ($payload['client_id'] ?? '');
        if ($clientId === '') {
            $clientId = (string) config('briva.client_public_key_id');
        }
        if ($clientId === '') {
            $clientId = (string) config('briva.client_id');
        }
        if ($clientId === '') {
            return response()->json(['detail' => 'Missing client_id'], 400);
        }

        $timestamp = Timestamp::briNow();
        try {
            $signature = Signature::signAccessToken($clientId, $timestamp, $privateKey);
        } catch (\Throwable $ex) {
            return response()->json(['detail' => $ex->getMessage()], 500);
        }

        return response()->json([
            'client_id' => $clientId,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);
    }

    public function testSignTransaction(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $path = $payload['path'] ?? null;
        $method = $payload['method'] ?? 'POST';
        $accessToken = $payload['access_token'] ?? null;
        $body = $payload['body'] ?? [];

        if (!$path || !$accessToken) {
            return response()->json(['detail' => 'Missing path or access_token'], 400);
        }

        $clientId = (string) ($payload['client_id'] ?? '');
        if ($clientId === '') {
            $clientId = (string) config('briva.client_secret_id');
        }

        $secrets = $this->loadClientSecrets();
        $clientSecret = $clientId !== '' ? ($secrets[$clientId] ?? '') : '';
        if ($clientSecret === '') {
            return response()->json(['detail' => 'Unauthorized Client'], 401);
        }

        $timestamp = Timestamp::briNow();
        $signature = Signature::signTransaction(
            (string) $method,
            (string) $path,
            (string) $accessToken,
            is_array($body) ? $body : [],
            $timestamp,
            $clientSecret
        );

        return response()->json([
            'client_id' => $clientId,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);
    }

    public function inquiry(Request $request, TokenRepository $tokens, InquiryResolver $resolver, InquiryRepository $inquiries, ExternalIdRepository $externalIds): JsonResponse
    {
        $defaultHeadersValid = $this->validateDefaultHeaders($request, '24', $externalIds);
        if ($defaultHeadersValid instanceof JsonResponse) {
            return $defaultHeadersValid;
        }

        $tokenData = $this->requireToken($request, $tokens, '24');
        if ($tokenData instanceof JsonResponse) {
            return $tokenData;
        }

        $body = $request->json()->all();
        $validation = InquiryRequest::validate($body);
        if (!$validation['ok']) {
            $code = str_starts_with($validation['message'], 'Invalid Field Format')
                ? '4002401'
                : '4002402';
            return $this->inquiryErrorResponse(400, $code, $validation['message']);
        }

        if (!$this->virtualAccountNoMatches($body)) {
            return $this->inquiryErrorResponse(404, '4042412', 'Invalid Bill/Virtual Account Not Match');
        }

        // $partnerError = $this->validatePartnerId($request, '4042416');
        // if ($partnerError) {
        //     return $partnerError;
        // }

        $headersValid = $this->validateTransactionSignature($request, $body, $tokenData['token'], $tokenData['client_id'], '24');
        if ($headersValid instanceof JsonResponse) {
            return $headersValid;
        }

        $inquiryRequestId = (string) ($body['inquiryRequestId'] ?? '');
        $existing = $inquiries->findByInquiryRequestId($inquiryRequestId);
        if ($existing) {
            if (($existing['status'] ?? null) === 'paid') {
                return $this->inquiryErrorResponse(404, '4042414', 'Bill has been paid');
            }
            if ($inquiries->isExpired($existing)) {
                return $this->inquiryErrorResponse(404, '4042419', 'Bill expired');
            }
        }

        try {
            $payload = $resolver->resolve($body);
        } catch (\Throwable $ex) {
            \Illuminate\Support\Facades\Log::debug('ex', ['ex' => $ex->getMessage()]);
            return $this->inquiryErrorResponse(502, '5022400', 'Inquiry service unavailable');
        }

        if (!is_array($payload)) {
            return $this->inquiryErrorResponse(502, '5022400', 'Inquiry service unavailable');
        }

        $virtualAccountData = $payload['virtualAccountData'] ?? [];
        $additionalInfo = $payload['additionalInfo'] ?? [];
        $billShortName = is_array($additionalInfo) ? (string) ($additionalInfo['info1'] ?? '') : '';
        $billCode = is_array($additionalInfo) ? (string) ($additionalInfo['info2'] ?? '') : '';
        $billInfo1 = is_array($additionalInfo) ? (string) ($additionalInfo['info3'] ?? '') : '';
        $billInfo4 = is_array($additionalInfo) ? (string) ($additionalInfo['info4'] ?? '') : '';
        $slug = Formatter::slugCompact($billShortName)
            . Formatter::slugCompact($billCode)
            . Formatter::slugCompact($billInfo1)
            . Formatter::slugCompact($billInfo4);

        $totalAmount = is_array($virtualAccountData) ? ($virtualAccountData['totalAmount'] ?? []) : [];
        $totalAmountValue = is_array($totalAmount) ? ($totalAmount['value'] ?? null) : null;
        $totalAmountCurrency = is_array($totalAmount) ? ($totalAmount['currency'] ?? null) : null;

        if (($payload['responseCode'] ?? '') === '2002400' && $inquiryRequestId !== '' && is_array($virtualAccountData)) {
            $expiryMinutes = (int) config('briva.virtual_account.expiry_minutes', 1440);
            $inquiries->upsertVirtualAccount([
                'inquiry_request_id' => $inquiryRequestId,
                'partner_service_id' => (string) ($virtualAccountData['partnerServiceId'] ?? $body['partnerServiceId'] ?? ''),
                'customer_no' => (string) ($virtualAccountData['customerNo'] ?? $body['customerNo'] ?? ''),
                'virtual_account_no' => (string) ($virtualAccountData['virtualAccountNo'] ?? $body['virtualAccountNo'] ?? ''),
                'virtual_account_name' => (string) ($virtualAccountData['virtualAccountName'] ?? ''),
                'slug' => $slug,
                'bill_short_name' => $billShortName,
                'bill_code' => $billCode,
                'bill_info1' => $billInfo1,
                'bill_info4' => $billInfo4,
                'total_amount_value' => $totalAmountValue !== null ? (string) $totalAmountValue : null,
                'total_amount_currency' => $totalAmountCurrency !== null ? (string) $totalAmountCurrency : null,
                'inquiry_status' => (string) ($virtualAccountData['inquiryStatus'] ?? ''),
                'inquiry_reason' => $virtualAccountData['inquiryReason'] ?? null,
            ], $expiryMinutes);
        }

        return $this->payloadResponse($payload);
    }

    public function payment(Request $request, TokenRepository $tokens, InquiryRepository $inquiries, PaymentResolver $resolver, ExternalIdRepository $externalIds): JsonResponse
    {
        $defaultHeadersValid = $this->validateDefaultHeaders($request, '25', $externalIds);
        if ($defaultHeadersValid instanceof JsonResponse) {
            return $defaultHeadersValid;
        }

        $tokenData = $this->requireToken($request, $tokens, '25');
        if ($tokenData instanceof JsonResponse) {
            return $tokenData;
        }

        $body = $request->json()->all();
        $validation = PaymentRequest::validate($body);
        if (!$validation['ok']) {
            $code = str_starts_with($validation['message'], 'Invalid Field Format')
                ? '4002501'
                : '4002502';
            return $this->paymentErrorResponse(400, $code, $validation['message']);
        }

        if (!$this->virtualAccountNoMatches($body)) {
            return $this->paymentErrorResponse(404, '4042512', 'Invalid Bill/Virtual Account Not Match');
        }

        // $partnerError = $this->validatePartnerId($request, '4042516');
        // if ($partnerError) {
        //     return $partnerError;
        // }

        $headersValid = $this->validateTransactionSignature($request, $body, $tokenData['token'], $tokenData['client_id'], '25');
        if ($headersValid instanceof JsonResponse) {
            return $headersValid;
        }

        // paymentRequestId must reference a still-valid inquiryRequestId (paymentRequestId == inquiryRequestId).
        $paymentRequestId = (string) ($body['paymentRequestId'] ?? '');
        $record = $inquiries->findByInquiryRequestId($paymentRequestId);
        if (!$record) {
            return $this->paymentErrorResponse(404, '4042512', 'Bill not found');
        }

        $virtualAccountNo = (string) ($body['virtualAccountNo'] ?? '');
        $customerNo = (string) ($body['customerNo'] ?? '');
        if ($virtualAccountNo !== (string) ($record['virtual_account_no'] ?? '') || $customerNo !== (string) ($record['customer_no'] ?? '')) {
            return $this->paymentErrorResponse(404, '4042512', 'Bill not found');
        }

        if (($record['status'] ?? null) === 'paid') {
            return $this->paymentErrorResponse(404, '4042514', 'Bill has been paid');
        }
        if ($inquiries->isExpired($record)) {
            return $this->paymentErrorResponse(404, '4042519', 'Bill expired');
        }

        $expectedAmount = [
            'value' => $record['total_amount_value'] ?? null,
            'currency' => $record['total_amount_currency'] ?? null,
        ];
        if (!PaymentRequest::amountMatches($body, $expectedAmount)) {
            return $this->paymentErrorResponse(404, '4042513', 'Invalid Amount');
        }

        try {
            $payload = $resolver->resolve($body);
        } catch (\Throwable $ex) {
            return $this->paymentErrorResponse(502, '5022500', 'Payment service unavailable');
        }

        if (!is_array($payload)) {
            return $this->paymentErrorResponse(502, '5022500', 'Payment service unavailable');
        }

        $payloadResponseCode = (string) ($payload['responseCode'] ?? '');
        if ($payloadResponseCode === '2002500' && $paymentRequestId !== '') {
            $inquiries->markPaidByPaymentRequestId($paymentRequestId);
        } elseif ($payloadResponseCode === '4042514' && $paymentRequestId !== '') {
            // WSDL says the bill was already settled via another channel — our own payment attempt failed, not succeeded.
            $inquiries->markFailedByPaymentRequestId($paymentRequestId);
        }

        return $this->payloadResponse($payload);
    }

    private function getHeader(Request $request, string $name): ?string
    {
        $value = $request->headers->get($name);
        return $value !== null ? (string) $value : null;
    }

    private function requireToken(Request $request, TokenRepository $tokens, string $serviceCode)
    {
        $auth = $request->header('Authorization');
        if (!$auth || stripos($auth, 'Bearer ') !== 0) {
            return $this->errorResponse(401, "401{$serviceCode}01", 'Access Token Invalid');
        }
        $token = trim(substr($auth, 7));
        $data = $tokens->validate($token);
        if (!$data) {
            return $this->errorResponse(401, "401{$serviceCode}01", 'Access Token Invalid');
        }
        return $data + ['token' => $token];
    }

    private function virtualAccountNoMatches(array $body): bool
    {
        $partnerServiceId = Formatter::formatPartnerServiceId((string) ($body['partnerServiceId'] ?? ''));
        $customerNo = (string) ($body['customerNo'] ?? '');
        $virtualAccountNo = (string) ($body['virtualAccountNo'] ?? '');

        return $virtualAccountNo === $partnerServiceId . $customerNo;
    }

    private function validatePartnerId(Request $request, string $notFoundCode): ?JsonResponse
    {
        $partnerServiceId = trim((string) config('briva.partner_service_id'));
        $partnerId = trim((string) config('briva.partner_id'));
        // if ($partnerId === '') {
        //     return null;
        // }

        $xPartnerID = $this->getHeader($request, 'X-PARTNER-ID');

        if (!$xPartnerID || $xPartnerID !== $partnerId) {
            return $this->errorResponse(404, $notFoundCode, 'Partner Not Found');
        }

        $xPartnerServiceId = $request->partnerServiceId;

        if (!$xPartnerServiceId || $xPartnerServiceId !== $partnerServiceId) {
            return $this->errorResponse(404, $notFoundCode, 'Partner Service Id Not Found');
        }
        return null;
    }

    private function validateTransactionSignature(Request $request, array $body, string $accessToken, string $clientId, string $serviceCode): ?JsonResponse
    {
        $timestamp = $this->getHeader($request, 'X-TIMESTAMP');
        $signature = $this->getHeader($request, 'X-SIGNATURE');
        if (!$timestamp || !$signature) {
            return $this->errorResponse(400, "400{$serviceCode}00", 'Bad Request');
        }
        $parsed = Timestamp::parseBri($timestamp);
        if (!$parsed || !Timestamp::withinSkew($parsed, 3600)) {
            return $this->errorResponse(401, "401{$serviceCode}00", 'Unauthorized stringToSign');
        }

        $clientSecrets = $this->loadClientSecrets();
        $clientSecret = $clientSecrets[$clientId] ?? null;
        if (!$clientSecret) {
            return $this->errorResponse(401, "401{$serviceCode}00", 'Unauthorized Client');
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
            return $this->errorResponse(401, "401{$serviceCode}00", 'Unauthorized Signature');
        }

        return null;
    }

    //akan diupdate menggunakan data dari db jika digunakan multibank
    private function loadClientPublicKeys(): array
    {
        $keys = Env::jsonMap((string) config('briva.client_public_keys_json'));
        if ($keys) {
            return array_map([Env::class, 'loadPem'], $keys);
        }

        $fallbackKey = Env::loadPem((string) config('briva.client_public_key_pem'));
        $fallbackId = (string) config('briva.client_public_key_id');
        if ($fallbackKey !== '' && $fallbackId !== '') {
            return [$fallbackId => $fallbackKey];
        }

        return [];
    }

    //jika multiple ini akan diupdate ngambil dari database
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

    private function payloadResponse(array $payload): JsonResponse
    {
        $responseCode = (string) ($payload['responseCode'] ?? '');
        $status = $this->httpStatusFromResponseCode($responseCode);
        return response()->json($payload, $status ?? 200);
    }

    private function httpStatusFromResponseCode(string $responseCode): ?int
    {
        if (!preg_match('/^(?<status>\\d{3})\\d+$/', $responseCode, $matches)) {
            return null;
        }

        $status = (int) $matches['status'];
        if ($status < 100 || $status > 599) {
            return null;
        }

        return $status;
    }

    public function validateDefaultHeaders(Request $request, string $serviceCode, ExternalIdRepository $externalIds): ?JsonResponse
    {
        $timestamp = $this->getHeader($request, 'X-TIMESTAMP');
        $contentType = $this->getHeader($request, 'Content-Type');
        $xPartnerID = $this->getHeader($request, 'X-PARTNER-ID');        
        $channelID = $this->getHeader($request, 'CHANNEL-ID');        
        $xExternalID = $this->getHeader($request, 'X-EXTERNAL-ID');        
        
        $mandatoryFields = [$timestamp, $contentType, $xPartnerID, $channelID, $xExternalID];
        $fieldNames = ['X-TIMESTAMP', 'Content-Type', 'X-PARTNER-ID', 'CHANNEL-ID', 'X-EXTERNAL-ID'];
        
        foreach($mandatoryFields as $index => $field){
            if(!$field){
                return $this->errorResponse(400, '4002402', 'Invalid Mandatory Field '.$fieldNames[$index]);   
            }
        }

        $partnerId = (string) config('briva.partner_id');
        
        if ($contentType !== 'application/json') {
            return $this->errorResponse(400, '400402', 'Invalid Mandatory Field Content-Type');
        }

        if ($xPartnerID !== $partnerId) {
            return $this->errorResponse(401, '4012400', 'Unauthorized Partner ID Not Match');
        }

        $windowMinutes = (int) config('briva.external_id.window_minutes', 1440);
        if ($externalIds->isDuplicate($xPartnerID, $xExternalID, $windowMinutes)) {
            return $this->errorResponse(409, "409{$serviceCode}00", 'Conflict');
        }
        $externalIds->record($xPartnerID, $xExternalID);

        return null;
    }
}
