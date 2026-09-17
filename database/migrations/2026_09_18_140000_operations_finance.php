<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality', 80)->nullable();
            $table->string('id_number', 60)->nullable();
            $table->string('id_type', 30)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_vip')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'last_name']);
            $table->index(['branch_id', 'email']);
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_number', 30)->unique();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->foreignId('guest_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('room_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);
            $table->string('status', 20)->default('confirmed');
            $table->string('rate_plan', 40)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->text('special_requests')->nullable();
            $table->date('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'check_in']);
            $table->index(['guest_id']);
            $table->index(['room_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 30)->unique();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('guest_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('method', 20);
            $table->string('status', 20)->default('completed');
            $table->decimal('amount', 15, 2);
            $table->string('reference', 80)->nullable();
            $table->date('paid_on');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('received_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'paid_on']);
            $table->index(['guest_id']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('guest_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('issued_on');
            $table->date('due_on')->nullable();
            $table->string('status', 20)->default('unpaid');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->text('line_items')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'issued_on']);
            $table->index(['guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('guests');
    }
};
