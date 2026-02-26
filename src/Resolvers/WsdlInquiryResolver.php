<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Resolvers;

use RahmatNurjaman99\BrivaOnline\Clients\WsdlClient;
use RahmatNurjaman99\BrivaOnline\Contracts\InquiryResolver;
use RahmatNurjaman99\BrivaOnline\Support\Formatter;

class WsdlInquiryResolver implements InquiryResolver
{
    public function __construct(private readonly WsdlClient $wsdl) {}

    public function resolve(array $body): array
    {
        $customerNo = (string) ($body['customerNo'] ?? '');
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
                $description = $status['statusDescription'] ?? 'Invalid customerNo';
                return [
                    'responseCode' => '4002401',
                    'responseMessage' => $description,
                ];
            }
        }

        $billDetails = $inquiryResult['billDetails']['BillDetail'] ?? [];
        $firstItem = is_array($billDetails) && $billDetails ? $billDetails[0] : [];
        $billAmount = is_array($firstItem) ? ($firstItem['billAmount'] ?? null) : null;

        $totalValue = Formatter::formatAmountValue($billAmount);
        $totalCurrency = Formatter::mapCurrency($inquiryResult['currency'] ?? null);
        $billShortName = is_array($firstItem) ? ($firstItem['billShortName'] ?? '') : '';
        $billCode = is_array($firstItem) ? ($firstItem['billCode'] ?? '') : '';
        $billInfo1 = (string) ($inquiryResult['billInfo1'] ?? '');
        $billInfo4 = (string) ($inquiryResult['billInfo4'] ?? '');

        $inquiryRequestId = (string) ($body['inquiryRequestId'] ?? '');

        return [
            'responseCode' => '2002400',
            'responseMessage' => 'Successful',
            'virtualAccountData' => [
                'partnerServiceId' => (string) ($body['partnerServiceId'] ?? ''),
                'customerNo' => $customerNo,
                'virtualAccountNo' => (string) ($body['virtualAccountNo'] ?? ''),
                'virtualAccountName' => (string) ($inquiryResult['billInfo2'] ?? $body['virtualAccountName'] ?? 'John Doe'),
                'inquiryRequestId' => $inquiryRequestId,
                'totalAmount' => ['value' => $totalValue, 'currency' => $totalCurrency],
                'inquiryStatus' => '00',
                'inquiryReason' => ['english' => 'Success', 'indonesia' => 'Sukses'],
            ],
            'additionalInfo' => $body['additionalInfo'] ? array_merge($body['additionalInfo'], [
                'billShortName' => $billShortName,
                'billCode' => $billCode,
                'billInfo1' => $billInfo1,
                'billInfo4' => $billInfo4,
            ]) : [
                'billShortName' => $billShortName,
                'billCode' => $billCode,
                'billInfo1' => $billInfo1,
                'billInfo4' => $billInfo4,
            ],
        ];
    }
}
