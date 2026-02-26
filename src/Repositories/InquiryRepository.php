<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Repositories;

use Illuminate\Support\Facades\DB;

class InquiryRepository
{
    public function upsert(
        string $inquiryRequestId,
        string $paymentRequestId,
        string $customerNo,
        string $slug,
        string $billShortName,
        string $billCode,
        string $billInfo1,
        string $billInfo4
    ): void {
        DB::table('inquiry_records')->where('inquiry_request_id', $inquiryRequestId)->delete();
        DB::table('inquiry_records')->insert([
            'inquiry_request_id' => $inquiryRequestId,
            'payment_request_id' => $paymentRequestId,
            'customer_no' => $customerNo,
            'slug' => $slug,
            'bill_short_name' => $billShortName,
            'bill_code' => $billCode,
            'bill_info1' => $billInfo1,
            'bill_info4' => $billInfo4,
        ]);
    }
}
