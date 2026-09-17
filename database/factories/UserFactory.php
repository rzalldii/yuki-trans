<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\User\UserRole;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'password' => 'password',
            'role' => UserRole::User,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }
}