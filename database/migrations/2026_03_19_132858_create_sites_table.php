<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subdomain')->unique();
            $table->string('fqdn')->unique();

            $table->string('primary_domain')->nullable();
            $table->boolean('custom_domain_enabled')->default(false);

            $table->string('hestia_username')->nullable()->unique();
            $table->string('hestia_domain')->nullable();
            $table->string('wordpress_admin_url')->nullable();
            $table->string('wordpress_admin_username')->nullable();
            $table->string('wordpress_admin_email')->nullable();

            $table->string('status')->default('pending_payment');
            $table->text('provisioning_error')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('suspended_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};