<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => $this->generateNigerianPhone(),
            'email' => fake()->optional(0.7)->unique()->safeEmail(), // 70% chance of having email
            'phone_verified_at' => now(),
            'email_verified_at' => fake()->optional(0.5)->dateTime(), // 50% chance if email exists
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Generate a Nigerian phone number for testing
     */
    private function generateNigerianPhone(): string
    {
        $prefixes = [
            '0701',
            '0702',
            '0703',
            '0704',
            '0705',
            '0706',
            '0707',
            '0708',
            '0709',
            '0801',
            '0802',
            '0803',
            '0804',
            '0805',
            '0806',
            '0807',
            '0808',
            '0809',
            '0901',
            '0902',
            '0903',
            '0904',
            '0905',
            '0906',
            '0907',
            '0908',
            '0909'
        ];

        $prefix = fake()->randomElement($prefixes);
        $suffix = fake()->numerify('#######');

        return $prefix . $suffix;
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model's phone number should be unverified.
     */
    public function phoneUnverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }

    /**
     * Create user without email address.
     */
    public function withoutEmail(): static
    {
        return $this->state(fn(array $attributes) => [
            'email' => null,
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create user with specific phone number.
     */
    public function withPhone(string $phone): static
    {
        return $this->state(fn(array $attributes) => [
            'phone' => $phone,
        ]);
    }
}
