<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Http\Requests;

use RahmatNurjaman99\BrivaOnline\Support\FieldValidator;
use RahmatNurjaman99\BrivaOnline\Support\Formatter;

class InquiryRequest
{
    public static function validate(array $body): array
    {
        $required = ['partnerServiceId', 'customerNo', 'virtualAccountNo', 'inquiryRequestId'];
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
        if ($virtualAccountNo !== $partnerServiceId . $customerNo) {
            return ['ok' => false, 'message' => 'Invalid Field Format virtualAccountNo'];
        }

        $inquiryRequestId = (string) $body['inquiryRequestId'];
        if (!FieldValidator::isAlphanumericId($inquiryRequestId) || !FieldValidator::maxLength($inquiryRequestId, 128)) {
            return ['ok' => false, 'message' => 'Invalid Field Format inquiryRequestId'];
        }

        // amount is optional — only validate its format when the client actually sends it.
        if (isset($body['amount']) && is_array($body['amount'])) {
            if (isset($body['amount']['value']) && $body['amount']['value'] !== '') {
                if (!FieldValidator::isAmount((string) $body['amount']['value'])) {
                    return ['ok' => false, 'message' => 'Invalid Field Format amount.value'];
                }
            }
            if (isset($body['amount']['currency']) && $body['amount']['currency'] !== '') {
                $currency = (string) $body['amount']['currency'];
                if (!FieldValidator::isAlphabet($currency) || strlen($currency) !== 3) {
                    return ['ok' => false, 'message' => 'Invalid Field Format amount.currency'];
                }
            }
        }

        // trxDateInit is optional — only validate its format when present.
        if (isset($body['trxDateInit']) && $body['trxDateInit'] !== '') {
            if (!is_string($body['trxDateInit']) || !FieldValidator::isIso8601($body['trxDateInit'])) {
                return ['ok' => false, 'message' => 'Invalid Field Format trxDateInit'];
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

        // passApp is optional — only validate its format when present.
        if (isset($body['passApp']) && $body['passApp'] !== '') {
            if (!is_string($body['passApp']) || !FieldValidator::maxLength($body['passApp'], 64)) {
                return ['ok' => false, 'message' => 'Invalid Field Format passApp'];
            }
        }

        // if (!isset($body['additionalInfo']['idApp']) || $body['additionalInfo']['idApp'] === '') {
        //     return ['ok' => false, 'message' => 'Invalid Mandatory Field additionalInfo.idApp'];
        // }

        return ['ok' => true, 'message' => 'OK'];
    }
}
