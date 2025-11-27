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

		// 一般ユーザー5名
		$generalUsers = [
			['name' => '佐藤 花子',  'email' => 'user1@example.com'],
			['name' => '鈴木 次郎',  'email' => 'user2@example.com'],
			['name' => '高橋 美咲',  'email' => 'user3@example.com'],
			['name' => '田中 大輝',  'email' => 'user4@example.com'],
			['name' => '山本 莉子',  'email' => 'user5@example.com'],
		];

		foreach ($generalUsers as $user) {
			User::create([
				'name' => $user['name'],
				'email' => $user['email'],
				'password' => Hash::make('password123'),
				'role' => 1,
				'email_verified_at' => now(),
			]);
		}
	}
}
