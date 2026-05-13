<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (! Schema::hasColumn('sites', 'wordpress_sso_secret')) {
                $table->string('wordpress_sso_secret', 128)
                    ->nullable()
                    ->after('wordpress_admin_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (Schema::hasColumn('sites', 'wordpress_sso_secret')) {
                $table->dropColumn('wordpress_sso_secret');
            }
        });
    }
};