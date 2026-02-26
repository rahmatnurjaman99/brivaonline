<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Clients;

use RuntimeException;

class WsdlClient
{
    public function inquiry(string $customerNo, string $language = 'id'): array
    {
        $trxDateTime = now()->format('mdHis');
        $attributes = [
            'trxDateTime' => $trxDateTime,
            'transmissionDateTime' => $trxDateTime,
            'billKey1' => $customerNo,
            'language' => $language,
        ];
        return $this->call((string) config('briva.wsdl.inquiry_method'), ['request' => $attributes]);
    }

    public function payment(string $customerNo, string $billCode, $billAmount, string $language = 'id'): array
    {
        $trxDateTime = now()->format('mdHis');
        $transactionId = $trxDateTime . time();
        $attributes = [
            'trxDateTime' => $trxDateTime,
            'transmissionDateTime' => $trxDateTime,
            'billKey1' => $customerNo,
            'paidBills' => [$billCode],
            'paymentAmount' => (string) $billAmount,
            'transactionID' => $transactionId,
            'language' => $language,
        ];
        return $this->call((string) config('briva.wsdl.payment_method'), ['request' => $attributes]);
    }

    public function call(string $method, array $params): array
    {
        if (!class_exists('SoapClient')) {
            throw new RuntimeException('SoapClient extension not available.');
        }
        $client = new \SoapClient((string) config('briva.wsdl.endpoint'), [
            'exceptions' => true,
            'trace' => false,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ]);
        $result = $client->__soapCall($method, [$params]);
        return json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
