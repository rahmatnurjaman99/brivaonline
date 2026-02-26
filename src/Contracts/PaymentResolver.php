<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Contracts;

interface PaymentResolver
{
    /**
     * Return the final payment response payload.
     */
    public function resolve(array $body): array;
}
