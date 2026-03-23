<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // bronze, silver, gold
            $table->string('label');
            $table->decimal('price', 10, 2);
            $table->string('currency', 10)->default('MYR');
            $table->boolean('allows_custom_domain')->default(false);
            $table->unsignedInteger('max_themes')->default(0);
            $table->string('resource_profile')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};