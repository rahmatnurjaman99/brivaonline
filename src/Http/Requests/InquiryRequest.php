<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Http\Requests;

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

        if (!isset($body['additionalInfo']['idApp']) || $body['additionalInfo']['idApp'] === '') {
            return ['ok' => false, 'message' => 'Invalid Mandatory Field additionalInfo.idApp'];
        }

        return ['ok' => true, 'message' => 'OK'];
    }
}
