<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
	public function definition(): array
	{
		return [
			'name' => $this->faker->name(),
			'email' => $this->faker->unique()->safeEmail(),
			'email_verified_at' => $this->faker->boolean(80) ? now() : null, // 80% の確率で認証済
			'password' => Hash::make('password123'),
			'role' => 1, // デフォルトは一般ユーザー
			'remember_token' => str()->random(10),
		];
	}

	/**
	 * 管理者ユーザー用（role=2）状態
	 */
	public function admin(): static
	{
		return $this->state(fn() => [
			'role' => 2,
		]);
	}
}
