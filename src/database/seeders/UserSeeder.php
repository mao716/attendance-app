<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
	public function run(): void
	{
		// 管理者ユーザー
		User::create([
			'name' => '管理者ユーザー',
			'email' => 'admin@example.com',
			'password' => Hash::make('password123'),
			'role' => 2, // 管理者
			'email_verified_at' => now(),
		]);

		// 一般ユーザー3名
		$generalUsers = [
			'user1@example.com',
			'user2@example.com',
			'user3@example.com',
		];

		foreach ($generalUsers as $index => $email) {
			User::create([
				'name' => '一般ユーザー' . ($index + 1),
				'email' => $email,
				'password' => Hash::make('password123'),
				'role' => 1, // 一般ユーザー
				'email_verified_at' => now(),
			]);
		}
	}
}
