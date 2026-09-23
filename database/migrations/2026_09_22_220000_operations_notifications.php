<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_number', 30)->unique();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->string('type', 50)->default('info');
            $table->string('status', 20)->default('unread');
            $table->string('notifiable_type', 100)->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
