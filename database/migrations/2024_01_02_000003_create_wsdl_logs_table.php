<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wsdl_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('service')->index();
            $table->string('customer_no')->nullable()->index();
            $table->string('reference_id')->nullable()->index();
            $table->json('client_request');
            $table->json('wsdl_response')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wsdl_logs');
    }
};
