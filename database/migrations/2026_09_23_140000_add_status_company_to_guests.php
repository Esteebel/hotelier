<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            if (! Schema::hasColumn('guests', 'status')) {
                $table->string('status', 20)->default('active')->after('is_vip');
            }
            if (! Schema::hasColumn('guests', 'company')) {
                $table->string('company', 200)->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            if (Schema::hasColumn('guests', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('guests', 'company')) {
                $table->dropColumn('company');
            }
        });
    }
};
