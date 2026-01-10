<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
	use RefreshDatabase;

	private function validPayload(array $overrides = []): array
	{
		return array_merge([
			'email' => 'admin@example.com',
			'password' => 'password12',
		], $overrides);
	}

	private function adminRoleValue(): int
	{
		if (defined(User::class . '::ROLE_ADMIN')) {
			/** @phpstan-ignore-next-line */
			return User::ROLE_ADMIN;
		}

		return 2;
	}

	#[Test]
	public function email_is_required(): void
	{
		User::factory()->create([
			'email' => 'admin@example.com',
			'password' => Hash::make('password12'),
			'role' => $this->adminRoleValue(),
		]);

		$response = $this->post(route('login.store'), $this->validPayload([
			'email' => '',
		]));

		$response->assertSessionHasErrors(['email']);
		$this->assertContains('メールアドレスを入力してください', session('errors')->get('email'));
		$this->assertGuest();
	}

	#[Test]
	public function password_is_required(): void
	{
		User::factory()->create([
			'email' => 'admin@example.com',
			'password' => Hash::make('password12'),
			'role' => $this->adminRoleValue(),
		]);

		$response = $this->post(route('login.store'), $this->validPayload([
			'password' => '',
		]));

		$response->assertSessionHasErrors(['password']);
		$this->assertContains('パスワードを入力してください', session('errors')->get('password'));
		$this->assertGuest();
	}

	#[Test]
	public function login_fails_with_invalid_credentials(): void
	{
		User::factory()->create([
			'email' => 'admin@example.com',
			'password' => Hash::make('password12'),
			'role' => $this->adminRoleValue(),
		]);

		$response = $this->post(route('login.store'), $this->validPayload([
			'email' => 'wrong-admin@example.com',
		]));

		$response->assertSessionHasErrors(['email']);
		$this->assertContains('ログイン情報が登録されていません', session('errors')->get('email'));
		$this->assertGuest();
	}
}
