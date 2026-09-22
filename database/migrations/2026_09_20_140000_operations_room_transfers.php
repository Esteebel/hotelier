<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 30)->unique();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
             $table->foreignId('from_room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();
            $table->foreignId('to_room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();
            $table->date('transfer_date');
            $table->string('reason', 60);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('handled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'transfer_date']);
            $table->index(['from_room_id']);
            $table->index(['to_room_id']);
            $table->index(['reservation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_transfers');
    }
};
