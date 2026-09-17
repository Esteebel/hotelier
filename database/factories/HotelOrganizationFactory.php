<?php

namespace Database\Factories;

use App\Enums\HotelStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HotelOrganization>
 */
class HotelOrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company().' Hotels',
            'slug' => null,
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->paragraph(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => 'Nigeria',
            'postal_code' => $this->faker->postcode(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'currency' => 'NGN',
            'star_rating' => $this->faker->numberBetween(1, 5),
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'status' => HotelStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => HotelStatus::Inactive]);
    }
}