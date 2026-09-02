<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Repositories;

use Illuminate\Support\Facades\DB;

class WsdlLogRepository
{
    public function log(string $service, ?string $customerNo, ?string $referenceId, array $clientRequest, ?array $wsdlResponse): void
    {
        DB::table('wsdl_logs')->insert([
            'service' => $service,
            'customer_no' => $customerNo,
            'reference_id' => $referenceId,
            'client_request' => json_encode($clientRequest),
            'wsdl_response' => $wsdlResponse !== null ? json_encode($wsdlResponse) : null,
            'created_at' => now(),
        ]);
    }
}
