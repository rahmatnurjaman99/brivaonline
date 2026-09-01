<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('external_ids', function (Blueprint $table): void {
            $table->id();
            $table->string('partner_id')->index();
            $table->string('external_id')->index();
            $table->timestamp('created_at')->nullable();
            $table->unique(['partner_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_ids');
    }
};
