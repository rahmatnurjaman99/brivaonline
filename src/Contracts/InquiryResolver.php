<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Contracts;

interface InquiryResolver
{
    /**
     * Return the final inquiry response payload.
     */
    public function resolve(array $body): array;
}
