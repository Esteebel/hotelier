<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('checkins');

        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->string('checkin_number', 30)->unique();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('room_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('guest_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('checked_in_at');
            $table->string('status', 20)->default('checked_in');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['reservation_id']);
            $table->index(['room_id']);
            $table->index(['guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};
