<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Resolvers;

use RahmatNurjaman99\BrivaOnline\Clients\WsdlClient;
use RahmatNurjaman99\BrivaOnline\Contracts\PaymentResolver;
use RahmatNurjaman99\BrivaOnline\Support\Formatter;

class WsdlPaymentResolver implements PaymentResolver
{
    public function __construct(private readonly WsdlClient $wsdl) {}

    public function resolve(array $body): array
    {
        $customerNo = (string) ($body['customerNo'] ?? '');
        $paymentRequestId = (string) ($body['paymentRequestId'] ?? '');
        $paidAmount = $body['paidAmount']['value'] ?? null;
        $paidCurrency = (string) ($body['paidAmount']['currency'] ?? '');

        $wsdlResponse = $this->wsdl->inquiry($customerNo);
        $inquiryResult = $wsdlResponse['inquiryResult'] ?? null;
        if (!is_array($inquiryResult)) {
            throw new \RuntimeException('Inquiry service unavailable');
        }

        $status = $inquiryResult['status'] ?? [];
        if (is_array($status)) {
            $isError = ($status['isError'] ?? false) === true;
            $errorCode = $status['errorCode'] ?? null;
            if ($isError || ($errorCode && $errorCode !== '00')) {
                $description = $status['statusDescription'] ?? 'Payment failed';
                return [
                    'responseCode' => '4002501',
                    'responseMessage' => $description,
                ];
            }
        }

        $billDetail = $inquiryResult['billDetails']['BillDetail'] ?? [];
        $billAmount = is_array($billDetail) ? ($billDetail['billAmount'] ?? null) : null;
        $billCode = is_array($billDetail) ? ($billDetail['billCode'] ?? '') : '';

        $paidValue = Formatter::formatAmountValue($paidAmount);
        $wsdlValue = Formatter::formatAmountValue($billAmount);
        $wsdlCurrency = Formatter::mapCurrency($inquiryResult['currency'] ?? null);
        if ($paidValue !== $wsdlValue || $paidCurrency !== $wsdlCurrency) {
            return [
                'responseCode' => '4002501',
                'responseMessage' => 'Invalid Field Format paidAmount',
            ];
        }

        $paymentResponse = $this->wsdl->payment($customerNo, $billCode, $billAmount);
        $paymentResult = $paymentResponse['paymentResult'] ?? null;
        if (is_array($paymentResult)) {
            $payStatus = $paymentResult['status'] ?? [];
            if (is_array($payStatus)) {
                $isError = ($payStatus['isError'] ?? false) === true;
                $errorCode = $payStatus['errorCode'] ?? null;
                if ($isError || ($errorCode && $errorCode !== '00')) {
                    $description = $payStatus['statusDescription'] ?? 'Payment failed';
                    return [
                        'responseCode' => '4002501',
                        'responseMessage' => $description,
                    ];
                }
            }
        }

        $paidValue = Formatter::formatAmountValue($paidAmount);

        return [
            'responseCode' => '2002500',
            'responseMessage' => 'Successful',
            'virtualAccountData' => [
                'partnerServiceId' => (string) ($body['partnerServiceId'] ?? ''),
                'customerNo' => $customerNo,
                'virtualAccountNo' => (string) ($body['virtualAccountNo'] ?? ''),
                'virtualAccountName' => (string) ($inquiryResult['billInfo2'] ?? $body['virtualAccountName'] ?? 'John Doe'),
                'paymentRequestId' => $paymentRequestId,
                'paidAmount' => ['value' => $paidValue, 'currency' => $paidCurrency],
                'paymentFlagStatus' => '00',
                'paymentFlagReason' => ['english' => 'Success', 'indonesia' => 'Sukses'],
            ],
            'additionalInfo' => $body['additionalInfo'] ?? [],
        ];
    }
}
