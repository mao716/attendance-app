<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClockOutTest extends TestCase
{
	use RefreshDatabase;

	private function createVerifiedUser(): User
	{
		return User::factory()->create([
			'email_verified_at' => now(),
		]);
	}

	#[Test]
	public function clock_out_button_works_and_status_becomes_finished(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 18, 0, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();

		$attendance = Attendance::factory()
			->for($user)
			->working(now()->toDateString())
			->create();

		$response = $this->actingAs($user)->post(route('attendance.clock_out'));

		$response->assertStatus(302);

		$this->assertDatabaseHas('attendances', [
			'id'            => $attendance->id,
			'status'        => Attendance::STATUS_FINISHED,
			'clock_out_at'  => now()->format('Y-m-d H:i:s'),
		]);

		$page = $this->actingAs($user)->get(route('attendance.index'));
		$page->assertStatus(200);
		$page->assertSee('退勤済');
	}

	#[Test]
	public function clock_out_time_is_visible_in_attendance_list(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 18, 30, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();

		Attendance::factory()
			->for($user)
			->finished(now()->toDateString())
			->create([
				'clock_out_at' => now(),
			]);

		$response = $this->actingAs($user)->get(route('attendance.list'));

		$response->assertStatus(200);
		$response->assertSee('18:30');
	}
}
