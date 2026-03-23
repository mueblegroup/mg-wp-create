<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_domains', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')->constrained()->cascadeOnDelete();

            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->string('verification_status')->default('pending');
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['site_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_domains');
    }
};