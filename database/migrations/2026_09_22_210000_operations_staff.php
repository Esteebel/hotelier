<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('staff');

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('staff_number', 30)->unique();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('position', 100);
            $table->string('department', 80)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'department']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
