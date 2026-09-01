<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inquiry_records', function (Blueprint $table): void {
            $table->string('partner_service_id')->nullable()->after('inquiry_request_id');
            $table->string('virtual_account_no')->nullable()->index()->after('customer_no');
            $table->string('virtual_account_name')->nullable()->after('virtual_account_no');
            $table->string('inquiry_status', 2)->nullable()->after('total_amount_currency');
            $table->json('inquiry_reason')->nullable()->after('inquiry_status');
            $table->timestamp('expired_at')->nullable()->index()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('inquiry_records', function (Blueprint $table): void {
            $table->dropColumn([
                'partner_service_id',
                'virtual_account_no',
                'virtual_account_name',
                'inquiry_status',
                'inquiry_reason',
                'expired_at',
            ]);
        });
    }
};
