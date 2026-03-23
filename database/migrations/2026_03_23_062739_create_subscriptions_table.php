<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();

            $table->string('provider')->default('hitpay');
            $table->string('provider_plan_id')->nullable()->index();
            $table->string('provider_subscription_id')->nullable()->index();
            $table->string('provider_customer_reference')->nullable()->index();

            $table->string('status')->default('pending');
            $table->string('currency', 10)->default('MYR');
            $table->decimal('amount', 10, 2);
            $table->string('billing_cycle')->default('monthly');

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('next_billing_at')->nullable();
            $table->timestamp('last_paid_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};