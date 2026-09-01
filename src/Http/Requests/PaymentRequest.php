<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Http\Requests;

use RahmatNurjaman99\BrivaOnline\Support\FieldValidator;
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
        if (!FieldValidator::isNumericWithSpaces($partnerServiceId)) {
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

        $customerNo = (string) $body['customerNo'];
        if (!FieldValidator::isNumeric($customerNo) || !FieldValidator::maxLength($customerNo, 20)) {
            return ['ok' => false, 'message' => 'Invalid Field Format customerNo'];
        }

        $virtualAccountNo = (string) $body['virtualAccountNo'];
        if (!FieldValidator::isNumericWithSpaces($virtualAccountNo) || !FieldValidator::maxLength($virtualAccountNo, 28)) {
            return ['ok' => false, 'message' => 'Invalid Field Format virtualAccountNo'];
        }

        $paymentRequestId = (string) $body['paymentRequestId'];
        if (!FieldValidator::isAlphanumericId($paymentRequestId) || !FieldValidator::maxLength($paymentRequestId, 128)) {
            return ['ok' => false, 'message' => 'Invalid Field Format paymentRequestId'];
        }

        if (!isset($body['paidAmount']) || !is_array($body['paidAmount'])) {
            return ['ok' => false, 'message' => 'Invalid Mandatory Field paidAmount'];
        }
        if (!isset($body['paidAmount']['value'], $body['paidAmount']['currency'])) {
            return ['ok' => false, 'message' => 'Invalid Mandatory Field paidAmount'];
        }
        if (!FieldValidator::isAmount((string) $body['paidAmount']['value'])) {
            return ['ok' => false, 'message' => 'Invalid Field Format paidAmount.value'];
        }
        $paidCurrency = (string) $body['paidAmount']['currency'];
        if (!FieldValidator::isAlphabet($paidCurrency) || strlen($paidCurrency) !== 3) {
            return ['ok' => false, 'message' => 'Invalid Field Format paidAmount.currency'];
        }

        // virtualAccountName is optional — only validate its format when present.
        if (isset($body['virtualAccountName']) && $body['virtualAccountName'] !== '') {
            if (!is_string($body['virtualAccountName']) || !FieldValidator::maxLength($body['virtualAccountName'], 255)) {
                return ['ok' => false, 'message' => 'Invalid Field Format virtualAccountName'];
            }
        }

        // trxDateTime is optional — only validate its format when present.
        if (isset($body['trxDateTime']) && $body['trxDateTime'] !== '') {
            if (!is_string($body['trxDateTime']) || !FieldValidator::isIso8601($body['trxDateTime'])) {
                return ['ok' => false, 'message' => 'Invalid Field Format trxDateTime'];
            }
        }

        // channelCode is optional — only validate its format when present.
        if (isset($body['channelCode']) && $body['channelCode'] !== '') {
            $channelCode = (string) $body['channelCode'];
            if (!FieldValidator::isNumeric($channelCode) || !FieldValidator::maxLength($channelCode, 4)) {
                return ['ok' => false, 'message' => 'Invalid Field Format channelCode'];
            }
        }

        // sourceBankCode is optional — only validate its format when present.
        if (isset($body['sourceBankCode']) && $body['sourceBankCode'] !== '') {
            $sourceBankCode = (string) $body['sourceBankCode'];
            if (!FieldValidator::isNumeric($sourceBankCode) || !FieldValidator::maxLength($sourceBankCode, 3)) {
                return ['ok' => false, 'message' => 'Invalid Field Format sourceBankCode'];
            }
        }

        // trxId is conditional — only validate its format when present.
        if (isset($body['trxId']) && $body['trxId'] !== '') {
            $trxId = (string) $body['trxId'];
            if (!FieldValidator::isNumeric($trxId) || !FieldValidator::maxLength($trxId, 64)) {
                return ['ok' => false, 'message' => 'Invalid Field Format trxId'];
            }
        }

        // hashedSourceAccountNo is optional — only validate its format when present.
        if (isset($body['hashedSourceAccountNo']) && $body['hashedSourceAccountNo'] !== '') {
            if (!is_string($body['hashedSourceAccountNo']) || !FieldValidator::maxLength($body['hashedSourceAccountNo'], 32)) {
                return ['ok' => false, 'message' => 'Invalid Field Format hashedSourceAccountNo'];
            }
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
}
