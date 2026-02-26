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
            $table->string('payment_request_id');
            $table->string('customer_no');
            $table->string('slug');
            $table->string('bill_short_name')->nullable();
            $table->string('bill_code')->nullable();
            $table->string('bill_info1')->nullable();
            $table->string('bill_info4')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_records');
    }
};
