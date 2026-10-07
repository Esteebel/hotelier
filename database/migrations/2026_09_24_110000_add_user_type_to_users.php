<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table
                ->string('user_type', 20)
                ->default('staff')
                ->after('status');
            $table
                ->foreignId('guest_id')
                ->nullable()
                ->after('user_type')
                ->constrained('guests')
                ->nullOnDelete();
            $table->index('user_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_id');
            $table->dropIndex(['user_type']);
            $table->dropColumn('user_type');
        });
    }
};
