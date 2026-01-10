<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
	use RefreshDatabase;

	private function createVerifiedUser(): User
	{
		return User::factory()->create([
			'name' => '山田 太郎',
			'email_verified_at' => now(),
		]);
	}

	#[Test]
	public function attendance_detail_displays_user_name_and_date_correctly(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 9, 0, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();

		$attendance = Attendance::factory()
			->for($user)
			->state([
				'work_date' => '2026-01-10',
				'status' => Attendance::STATUS_FINISHED,
			])
			->create();

		$response = $this->actingAs($user)->get(
			route('attendance.detail', ['id' => $attendance->id])
		);

		$response->assertStatus(200);

		$response->assertSee('山田 太郎');

		$response->assertSee('2026年');
		$response->assertSee('1月10日');

		Carbon::setTestNow();
	}

	#[Test]
	public function attendance_detail_displays_correct_work_and_break_times(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 9, 0, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();

		$attendance = Attendance::factory()
			->for($user)
			->state([
				'work_date' => '2026-01-10',
				'clock_in_at' => Carbon::parse('2026-01-10 09:00'),
				'clock_out_at' => Carbon::parse('2026-01-10 18:00'),
				'status' => Attendance::STATUS_FINISHED,
			])
			->create();

		AttendanceBreak::query()->create([
			'attendance_id' => $attendance->id,
			'break_start_at' => Carbon::parse('2026-01-10 12:00'),
			'break_end_at' => Carbon::parse('2026-01-10 13:00'),
		]);

		$response = $this->actingAs($user)->get(
			route('attendance.detail', ['id' => $attendance->id])
		);

		$response->assertStatus(200);

		$response->assertSee('value="09:00"', false);
		$response->assertSee('value="18:00"', false);

		$response->assertSee('name="breaks[0][start]"', false);
		$response->assertSee('value="12:00"', false);

		$response->assertSee('name="breaks[0][end]"', false);
		$response->assertSee('value="13:00"', false);

		Carbon::setTestNow();
	}
}
