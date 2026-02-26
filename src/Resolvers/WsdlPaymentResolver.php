<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Resolvers;

use RahmatNurjaman99\BrivaOnline\Clients\WsdlClient;
use RahmatNurjaman99\BrivaOnline\Contracts\PaymentResolver;

class WsdlPaymentResolver implements PaymentResolver
{
    public function __construct(private readonly WsdlClient $wsdl)
    {
    }

    public function resolve(array $body): array
    {
        $customerNo = (string) ($body['customerNo'] ?? '');
        $billCode = (string) ($body['paymentRequestId'] ?? '');
        $billAmount = $body['paidAmount']['value'] ?? '';

        return $this->wsdl->payment($customerNo, $billCode, $billAmount);
    }
}
