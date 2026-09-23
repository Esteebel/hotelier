<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('laundry_orders');

        Schema::create('laundry_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
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
            $table->json('items')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->date('pickup_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laundry_orders');
    }
};
