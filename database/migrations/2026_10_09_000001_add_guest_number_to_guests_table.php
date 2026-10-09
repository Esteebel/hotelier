<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('guest_number', 20)->nullable()->after('id');
        });

        // Backfill existing guests (portable across MySQL and SQLite).
        DB::table('guests')->orderBy('id')->pluck('id')->each(function ($id) {
            DB::table('guests')
                ->where('id', $id)
                ->update(['guest_number' => 'GST-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT)]);
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->unique('guest_number');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropUnique(['guest_number']);
            $table->dropIndex(['phone']);
            $table->dropColumn('guest_number');
        });
    }
};
