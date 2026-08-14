<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        $name = fake('id_ID')->name();

        return [
            'name' => $name,
            'username' => Str::slug(Str::of($name)->explode(' ')->take(2)->implode(' '), '.').fake()->numberBetween(1, 99),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '08'.fake()->numerify('##########'),
            'office' => fake()->randomElement(['Pamanukan', 'Subang', 'Bandung', 'Jakarta', 'Cirebon']),
            'is_active' => fake()->boolean(80),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'last_login_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-30 days') : null,
        ];
    }
}
