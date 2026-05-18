<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->timestamp('suspension_requested_at')->nullable()->after('suspended_at');
            $table->timestamp('suspension_verified_at')->nullable()->after('suspension_requested_at');
            $table->timestamp('suspension_last_checked_at')->nullable()->after('suspension_verified_at');
            $table->timestamp('suspension_last_failed_at')->nullable()->after('suspension_last_checked_at');
            $table->unsignedInteger('suspension_attempts')->default(0)->after('suspension_last_failed_at');
            $table->text('suspension_last_error')->nullable()->after('suspension_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn([
                'suspension_requested_at',
                'suspension_verified_at',
                'suspension_last_checked_at',
                'suspension_last_failed_at',
                'suspension_attempts',
                'suspension_last_error',
            ]);
        });
    }
};