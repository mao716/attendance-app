<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterTest extends TestCase
{
	use RefreshDatabase;

	private function validPayload(array $overrides = []): array
	{
		return array_merge([
			'name' => 'テスト太郎',
			'email' => 'test@example.com',
			'password' => 'password12',
			'password_confirmation' => 'password12',
		], $overrides);
	}

	#[Test]
	public function name_is_required(): void
	{
		$response = $this->post('/register', $this->validPayload([
			'name' => '',
		]));

		$response->assertSessionHasErrors(['name']);
		$this->assertContains('お名前を入力してください', session('errors')->get('name'));
	}

	#[Test]
	public function email_is_required(): void
	{
		$response = $this->post('/register', $this->validPayload([
			'email' => '',
		]));

		$response->assertSessionHasErrors(['email']);
		$this->assertContains('メールアドレスを入力してください', session('errors')->get('email'));
	}

	#[Test]
	public function password_must_be_at_least_8_characters(): void
	{
		$response = $this->post('/register', $this->validPayload([
			'password' => 'pass123',
			'password_confirmation' => 'pass123',
		]));

		$response->assertSessionHasErrors(['password']);
		$this->assertContains('パスワードは8文字以上で入力してください', session('errors')->get('password'));
	}

	#[Test]
	public function password_confirmation_must_match(): void
	{
		$response = $this->post('/register', $this->validPayload([
			'password' => 'password12',
			'password_confirmation' => 'password34',
		]));

		$response->assertSessionHasErrors(['password_confirmation']);
		$this->assertContains('パスワードと一致しません', session('errors')->get('password_confirmation'));
	}

	#[Test]
	public function password_is_required(): void
	{
		$response = $this->post('/register', $this->validPayload([
			'password' => '',
			'password_confirmation' => '',
		]));

		$response->assertSessionHasErrors(['password']);
		$this->assertContains('パスワードを入力してください', session('errors')->get('password'));
	}

	#[Test]
	public function user_can_register_with_valid_input(): void
	{
		$payload = $this->validPayload([
			'email' => 'newuser@example.com',
		]);

		$response = $this->post('/register', $payload);

		$this->assertDatabaseHas('users', [
			'email' => 'newuser@example.com',
			'name' => 'テスト太郎',
		]);

		$response->assertStatus(302);
	}
}
