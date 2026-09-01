<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Http\Requests;

use RahmatNurjaman99\BrivaOnline\Support\Formatter;

class PaymentRequest
{
    public static function validate(array $body): array
    {
        $required = ['partnerServiceId', 'customerNo', 'virtualAccountNo', 'paymentRequestId'];
        foreach ($required as $field) {
            if (!isset($body[$field]) || $body[$field] === '') {
                return ['ok' => false, 'message' => "Invalid Mandatory Field {$field}"];
            }
            if (!is_string($body[$field])) {
                return ['ok' => false, 'message' => "Invalid Field Format {$field}"];
            }
        }

        $partnerServiceId = (string) $body['partnerServiceId'];
        if (!self::isNumericWithSpaces($partnerServiceId)) {
            return ['ok' => false, 'message' => 'Invalid Field Format partnerServiceId'];
        }
        if (strlen($partnerServiceId) !== 8) {
            return ['ok' => false, 'message' => 'Invalid Field Format partnerServiceId'];
        }
        $formattedPartnerServiceId = Formatter::formatPartnerServiceId($partnerServiceId);
        if ($partnerServiceId !== $formattedPartnerServiceId) {
            return ['ok' => false, 'message' => 'Invalid Field Format partnerServiceId'];
        }
        $configPartnerServiceId = (string) config('briva.partner_service_id');
        if ($configPartnerServiceId !== '') {
            $expected = Formatter::formatPartnerServiceId($configPartnerServiceId);
            if ($partnerServiceId !== $expected) {
                return ['ok' => false, 'message' => 'Invalid Field Format partnerServiceId'];
            }
        }

        if (!isset($body['paidAmount']) || !is_array($body['paidAmount'])) {
            return ['ok' => false, 'message' => 'Invalid Mandatory Field paidAmount'];
        }
        if (!isset($body['paidAmount']['value'], $body['paidAmount']['currency'])) {
            return ['ok' => false, 'message' => 'Invalid Mandatory Field paidAmount'];
        }

        return ['ok' => true, 'message' => 'OK'];
    }

    public static function amountMatches(array $body, array $expectedAmount): bool
    {
        $expectedValue = Formatter::formatAmountValue($expectedAmount['value'] ?? null);
        $expectedCurrency = (string) ($expectedAmount['currency'] ?? '');
        $actualValue = Formatter::formatAmountValue($body['paidAmount']['value'] ?? null);
        $actualCurrency = (string) ($body['paidAmount']['currency'] ?? '');

        if ($expectedValue !== '' && $expectedValue !== $actualValue) {
            return false;
        }
        if ($expectedCurrency !== '' && $expectedCurrency !== $actualCurrency) {
            return false;
        }

        return true;
    }

    private static function isNumericWithSpaces(string $value): bool
    {
        return trim($value) !== '' && ctype_digit(str_replace(' ', '', $value));
    }
}
