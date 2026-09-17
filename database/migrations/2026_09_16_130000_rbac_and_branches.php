<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename existing hotels table to hotel_organizations.
        // Must be done BEFORE the permission tables create their indexes.
        Schema::rename('hotels', 'hotel_organizations');

        Schema::create('hotel_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('hotel_organizations')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('country', 100)->default('Nigeria');
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->unsignedTinyInteger('star_rating')->default(1);
            $table->string('check_in_time', 5)->default('14:00');
            $table->string('check_out_time', 5)->default('12:00');
            $table->string('status', 20)->default('active');
            $table->boolean('is_default')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('user_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_price', 15, 2)->default(0);
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedTinyInteger('number_of_beds')->default(1);
            $table->string('bed_configuration', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('hotel_branches')
                ->cascadeOnDelete();
            $table->foreignId('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete();
            $table->string('room_number', 20);
            $table->string('floor', 20)->nullable();
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->decimal('price', 15, 2)->nullable();
            $table->string('status', 30)->default('available');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['branch_id', 'room_number']);
            $table->index(['branch_id', 'status']);
            $table->index(['room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('user_branches');
        Schema::dropIfExists('hotel_branches');
        Schema::rename('hotel_organizations', 'hotels');
    }
};