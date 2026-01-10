<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BreakTest extends TestCase
{
	use RefreshDatabase;

	private function createVerifiedUser(): User
	{
		return User::factory()->create([
			'email_verified_at' => now(),
		]);
	}

	#[Test]
	public function break_in_creates_break_start_and_sets_status_break(): void
	{
		Carbon::setTestNow(Carbon::parse('2026-01-10 09:00:00'));

		$user = $this->createVerifiedUser();

		$attendance = Attendance::factory()
			->for($user)
			->working(now()->toDateString())
			->create();

		$response = $this->actingAs($user)->post(route('attendance.break_in'));

		$response->assertStatus(302);

		$this->assertDatabaseHas('attendance_breaks', [
			'attendance_id'   => $attendance->id,
			'break_start_at'  => now()->format('Y-m-d H:i:s'),
			'break_end_at'    => null,
		]);

		$this->assertDatabaseHas('attendances', [
			'id'     => $attendance->id,
			'status' => Attendance::STATUS_BREAK,
		]);
	}

	#[Test]
	public function break_out_sets_break_end_and_returns_to_working(): void
	{
		Carbon::setTestNow(Carbon::parse('2026-01-10 10:00:00'));

		$user = $this->createVerifiedUser();

		$attendance = Attendance::factory()
			->for($user)
			->onBreak(now()->toDateString())
			->create();

		$break = AttendanceBreak::query()->create([
			'attendance_id'  => $attendance->id,
			'break_start_at' => now(),
			'break_end_at'   => null,
		]);

		Carbon::setTestNow(Carbon::parse('2026-01-10 10:30:00'));

		$response = $this->actingAs($user)->post(route('attendance.break_out'));

		$response->assertStatus(302);

		$this->assertDatabaseHas('attendance_breaks', [
			'id'            => $break->id,
			'break_end_at'  => now()->format('Y-m-d H:i:s'),
		]);

		$this->assertDatabaseHas('attendances', [
			'id'     => $attendance->id,
			'status' => Attendance::STATUS_WORKING,
		]);
	}

	#[Test]
	public function break_buttons_are_toggled_by_status_on_attendance_page(): void
	{
		Carbon::setTestNow(Carbon::parse('2026-01-10 09:00:00'));

		$user = $this->createVerifiedUser();

		Attendance::factory()->for($user)->working(now()->toDateString())->create();
		$response = $this->actingAs($user)->get(route('attendance.index'));
		$response->assertStatus(200);
		$response->assertSee(route('attendance.break_in'), false);

		Attendance::query()->where('user_id', $user->id)->where('work_date', now()->toDateString())
			->update(['status' => Attendance::STATUS_BREAK]);

		$response2 = $this->actingAs($user)->get(route('attendance.index'));
		$response2->assertStatus(200);
		$response2->assertSee(route('attendance.break_out'), false);
	}
}
