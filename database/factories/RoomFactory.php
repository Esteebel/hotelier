<?php

namespace Database\Factories;

use App\Enums\RoomStatus;
use App\Models\HotelBranch;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => HotelBranch::factory(),
            'room_type_id' => RoomType::factory(),
            'room_number' => strtoupper($this->faker->unique()->bothify('##?')),
            'floor' => (string) $this->faker->numberBetween(1, 6),
            'capacity' => $this->faker->numberBetween(1, 4),
            'price' => null,
            'status' => RoomStatus::Available,
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function occupied(): static
    {
        return $this->state(fn () => ['status' => RoomStatus::Occupied]);
    }

    public function dirty(): static
    {
        return $this->state(fn () => ['status' => RoomStatus::Dirty]);
    }
}