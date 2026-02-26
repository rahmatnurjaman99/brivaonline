<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Http\Requests;

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

        if (!isset($body['paidAmount']) || !is_array($body['paidAmount'])) {
            return ['ok' => false, 'message' => 'Invalid Mandatory Field paidAmount'];
        }
        if (!isset($body['paidAmount']['value'], $body['paidAmount']['currency'])) {
            return ['ok' => false, 'message' => 'Invalid Mandatory Field paidAmount'];
        }

        return ['ok' => true, 'message' => 'OK'];
    }
}
