<?php

namespace Database\Factories;

use App\Models\HotelBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomType>
 */
class RoomTypeFactory extends Factory
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
            'name' => $this->faker->unique()->randomElement([
                'Single Room', 'Double Room', 'Deluxe Room', 'Executive Suite', 'Presidential Suite',
            ]),
            'description' => $this->faker->paragraph(),
            'base_price' => $this->faker->randomElement([25000, 40000, 65000, 100000, 180000]),
            'capacity' => $this->faker->numberBetween(1, 4),
            'number_of_beds' => $this->faker->numberBetween(1, 2),
            'bed_configuration' => $this->faker->randomElement(['1 King Bed', '1 Queen Bed', '2 Twin Beds', '1 Double Bed']),
            'is_active' => true,
        ];
    }
}