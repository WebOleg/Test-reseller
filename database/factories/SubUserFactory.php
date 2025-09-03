<?php

namespace Database\Factories;

use App\Models\SubUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubUserFactory extends Factory
{
    protected $model = SubUser::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'username' => $this->faker->unique()->userName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password123'),
            'balance' => $this->faker->randomFloat(2, 0, 1000),
            'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'api_user_id' => $this->faker->optional()->randomNumber(5),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function withBalance($min = 10, $max = 500): static
    {
        return $this->state(fn () => ['balance' => $this->faker->randomFloat(2, $min, $max)]);
    }
}
