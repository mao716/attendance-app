<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
	use RefreshDatabase;

	private function makeUnverifiedUser(): User
	{
		return User::factory()->create([
			'role' => 1,
			'email_verified_at' => null,
		]);
	}

	private function makeVerificationUrl(User $user): string
	{
		return URL::temporarySignedRoute(
			'verification.verify',
			now()->addMinutes(60),
			[
				'id' => $user->getKey(),
				'hash' => sha1($user->getEmailForVerification()),
			]
		);
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function verification_email_is_sent_after_register(): void
	{
		Notification::fake();

		$payload = [
			'name' => 'テスト太郎',
			'email' => 'verify@example.com',
			'password' => 'password123',
			'password_confirmation' => 'password123',
		];

		$response = $this->post(route('register'), $payload);
		$response->assertStatus(302);

		$user = User::query()->where('email', 'verify@example.com')->firstOrFail();

		Notification::assertSentTo(
			$user,
			VerifyEmail::class
		);
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function unverified_user_is_redirected_to_verification_notice_when_accessing_attendance(): void
	{
		$user = $this->makeUnverifiedUser();

		$this->actingAs($user)
			->get(route('attendance.index'))
			->assertStatus(302)
			->assertRedirect(route('verification.notice'));
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function user_can_verify_email_via_signed_url_and_then_access_attendance(): void
	{
		$user = $this->makeUnverifiedUser();

		$verificationUrl = $this->makeVerificationUrl($user);

		$this->actingAs($user)
			->get($verificationUrl)
			->assertStatus(302);

		$user->refresh();
		$this->assertNotNull($user->email_verified_at);

		$this->actingAs($user)
			->get(route('attendance.index'))
			->assertOk();
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function verified_user_can_access_attendance_without_notice(): void
	{
		$user = User::factory()->create([
			'role' => 1,
			'email_verified_at' => now(),
		]);

		$this->actingAs($user)
			->get(route('attendance.index'))
			->assertOk();
	}
}
