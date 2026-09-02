<?php

declare(strict_types=1);

namespace RahmatNurjaman99\BrivaOnline\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InquiryRepository
{
    public function findByInquiryRequestId(string $inquiryRequestId): ?array
    {
        $row = DB::table('inquiry_records')->where('inquiry_request_id', $inquiryRequestId)->first();
        return $row ? (array) $row : null;
    }

    public function isExpired(array $record): bool
    {
        if (empty($record['expired_at'])) {
            return false;
        }
        return now()->greaterThan(Carbon::parse($record['expired_at']));
    }

    public function upsertVirtualAccount(array $data, int $expiryMinutes): void
    {
        $existing = $this->findByInquiryRequestId($data['inquiry_request_id']);
        $expiredAt = $existing['expired_at'] ?? null;
        if (!$expiredAt) {
            $expiredAt = now()->addMinutes($expiryMinutes);
        }

        $values = [
            'payment_request_id' => $data['inquiry_request_id'],
            'partner_service_id' => $data['partner_service_id'] ?? null,
            'customer_no' => $data['customer_no'],
            'virtual_account_no' => $data['virtual_account_no'] ?? null,
            'virtual_account_name' => $data['virtual_account_name'] ?? null,
            'slug' => $data['slug'],
            'bill_short_name' => $data['bill_short_name'],
            'bill_code' => $data['bill_code'],
            'bill_info1' => $data['bill_info1'],
            'bill_info4' => $data['bill_info4'],
            'total_amount_value' => $data['total_amount_value'],
            'total_amount_currency' => $data['total_amount_currency'],
            'inquiry_status' => $data['inquiry_status'] ?? null,
            'inquiry_reason' => isset($data['inquiry_reason']) ? json_encode($data['inquiry_reason']) : null,
            'status' => $existing['status'] ?? 'pending',
            'expired_at' => $expiredAt,
            'updated_at' => now(),
        ];
        if (!$existing) {
            $values['created_at'] = now();
        }

        DB::table('inquiry_records')->updateOrInsert(
            ['inquiry_request_id' => $data['inquiry_request_id']],
            $values
        );
    }

    public function markPaidByPaymentRequestId(string $paymentRequestId): void
    {
        DB::table('inquiry_records')
            ->where('payment_request_id', $paymentRequestId)
            ->update(['status' => 'paid', 'updated_at' => now()]);
    }

    public function markFailedByPaymentRequestId(string $paymentRequestId): void
    {
        DB::table('inquiry_records')
            ->where('payment_request_id', $paymentRequestId)
            ->update(['status' => 'failed', 'updated_at' => now()]);
    }
}
