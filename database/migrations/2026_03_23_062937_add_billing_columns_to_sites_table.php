<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (! Schema::hasColumn('sites', 'billing_status')) {
                $table->string('billing_status')->nullable()->after('status');
            }

            if (! Schema::hasColumn('sites', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('billing_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (Schema::hasColumn('sites', 'billing_status')) {
                $table->dropColumn('billing_status');
            }
            if (Schema::hasColumn('sites', 'suspension_reason')) {
                $table->dropColumn('suspension_reason');
            }
        });
    }
};