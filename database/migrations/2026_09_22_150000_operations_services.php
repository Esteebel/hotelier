<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('service_requests');

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('service_number', 30)->unique();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->foreignId('room_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('guest_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('service_type', 50);
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
