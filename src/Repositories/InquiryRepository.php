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
        string $billInfo4,
        ?string $totalAmountValue = null,
        ?string $totalAmountCurrency = null
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
            'total_amount_value' => $totalAmountValue,
            'total_amount_currency' => $totalAmountCurrency,
        ]);
    }

    public function findByPaymentRequestId(string $paymentRequestId): ?array
    {
        $row = DB::table('inquiry_records')->where('payment_request_id', $paymentRequestId)->first();
        if (!$row) {
            return null;
        }
        return [
            'inquiry_request_id' => $row->inquiry_request_id,
            'payment_request_id' => $row->payment_request_id,
            'customer_no' => $row->customer_no,
            'slug' => $row->slug,
            'bill_short_name' => $row->bill_short_name,
            'bill_code' => $row->bill_code,
            'bill_info1' => $row->bill_info1,
            'bill_info4' => $row->bill_info4,
            'total_amount_value' => $row->total_amount_value,
            'total_amount_currency' => $row->total_amount_currency,
        ];
    }
}
