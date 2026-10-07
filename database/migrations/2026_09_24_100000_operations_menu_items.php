<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('menu_items');

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('category', 50);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['branch_id', 'category']);
            $table->index(['branch_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
