<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\=Peer>
 */
class PeerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(), 
            'amount' => $this->faker->randomFloat(8, 10, 1000),
            'private' => $this->faker->boolean(),
            'limit' => $this->faker->optional()->numberBetween(1, 100),
            'sharing_ratio' => $this->faker->numberBetween(1, 2),
        ];
    }
}
