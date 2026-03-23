<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('hitpay');
            $table->string('event_type')->nullable()->index();
            $table->string('event_object')->nullable();
            $table->string('signature')->nullable();
            $table->string('payload_hash')->index();
            $table->boolean('is_valid')->default(false);
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->longText('raw_payload');
            $table->json('headers')->nullable();
            $table->text('processing_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};