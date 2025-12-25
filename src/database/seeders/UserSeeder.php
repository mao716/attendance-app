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
			'email' => 'admin@coachtech.com',
			'password' => Hash::make('password123'),
			'role' => 2, // 管理者
			'email_verified_at' => now(),
		]);

		// 一般ユーザー5名
		$generalUsers = [
			['name' => '西 伶奈',  'email' => 'reina.n@coachtech.com'],
			['name' => '山田 太郎',  'email' => 'taro.y@coachtech.com'],
			['name' => '増田 一世',  'email' => 'issei.m@coachtech.com'],
			['name' => '山本 敬吉',  'email' => 'keikichi.y@coachtech.com'],
			['name' => '秋田 朋美',  'email' => 'tomomi.a@coachtech.com'],
			['name' => '中西 教夫',  'email' => 'norio.n@coachtech.com'],
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
