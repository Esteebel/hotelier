<?php

namespace Database\Factories;

use App\Enums\HotelStatus;
use App\Models\HotelOrganization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HotelBranch>
 */
class HotelBranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => HotelOrganization::factory(),
            'name' => $this->faker->unique()->city().' Branch',
            'slug' => null,
            'code' => strtoupper($this->faker->unique()->bothify('??###')),
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
            'is_default' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => HotelStatus::Inactive]);
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}