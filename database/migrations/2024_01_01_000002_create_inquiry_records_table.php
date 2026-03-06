<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inquiry_records', function (Blueprint $table): void {
            $table->string('inquiry_request_id')->primary();
            $table->string('payment_request_id')->index();
            $table->string('customer_no')->index();
            $table->string('slug')->index();
            $table->string('bill_short_name')->nullable()->index();
            $table->string('bill_code')->nullable()->index();
            $table->string('bill_info1')->nullable()->index();
            $table->string('bill_info4')->nullable()->index();
            $table->string('total_amount_value')->nullable()->index();
            $table->string('total_amount_currency')->nullable()->index();
            $table->timestamps();
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_records');
    }
};
