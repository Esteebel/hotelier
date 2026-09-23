<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('expenses');

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 30)->unique();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 12, 2);
            $table->string('category', 80);
            $table->date('expense_date');
            $table->string('reference', 60)->nullable();
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'expense_date']);
            $table->index(['category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
