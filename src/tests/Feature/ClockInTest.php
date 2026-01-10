<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClockInTest extends TestCase
{
	use RefreshDatabase;

	#[Test]
	public function clock_in_button_works_and_creates_attendance_for_today(): void
	{
		Carbon::setTestNow(Carbon::parse('2026-01-09 09:00:00'));

		$user = User::factory()->create([
			'role' => 1,
			'email_verified_at' => now(),
		]);

		$response = $this->actingAs($user)->post(route('attendance.clock_in'));

		$response->assertStatus(302);

		$attendance = \App\Models\Attendance::where('user_id', $user->id)->latest('id')->first();

		$this->assertNotNull($attendance);

		$this->assertSame(now()->toDateString(), $attendance->work_date->toDateString());

		$this->assertNotNull($attendance->clock_in_at);
	}

	#[Test]
	public function clock_in_is_allowed_only_once_per_day_and_button_is_not_visible_after_clock_out(): void
	{
		Carbon::setTestNow(Carbon::parse('2026-01-09 18:00:00'));

		$user = User::factory()->create([
			'role' => 1,
			'email_verified_at' => now(),
		]);

		Attendance::factory()->create([
			'user_id' => $user->id,
			'work_date' => now()->toDateString(),
			'clock_in_at' => now()->copy()->setTime(9, 0),
			'clock_out_at' => now()->copy()->setTime(18, 0),
			'status' => Attendance::STATUS_FINISHED,
		]);

		$response = $this->actingAs($user)->get(route('attendance.index'));

		$response->assertViewHas('status', Attendance::STATUS_FINISHED);
		$response->assertSee('お疲れ様でした。');
		$response->assertStatus(200);
		$response->assertDontSee(route('attendance.clock_in'), false);
	}

	#[Test]
	public function clock_in_time_is_visible_in_attendance_list(): void
	{
		Carbon::setTestNow(Carbon::parse('2026-01-09 09:10:00'));

		$user = User::factory()->create([
			'role' => 1,
			'email_verified_at' => now(),
		]);

		Attendance::factory()->create([
			'user_id' => $user->id,
			'work_date' => now()->toDateString(),
			'clock_in_at' => now()->copy()->setTime(9, 10),
		]);

		$response = $this->actingAs($user)->get(route('attendance.list'));
		$response->assertStatus(200);

		$response->assertSee('09:10');
	}
}
