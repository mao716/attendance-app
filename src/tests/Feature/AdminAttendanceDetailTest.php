<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
	use RefreshDatabase;

	private function createAdminUser(): User
	{
		$roleAdmin = \defined(User::class . '::ROLE_ADMIN') ? User::ROLE_ADMIN : 2;

		return User::factory()->create([
			'role' => $roleAdmin,
		]);
	}

	private function createGeneralUser(string $name = '山田 太郎'): User
	{
		return User::factory()->create([
			'name' => $name,
		]);
	}

	#[Test]
	public function admin_attendance_detail_displays_selected_data_correctly(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->createAdminUser();
		$user  = $this->createGeneralUser('山田 太郎');

		$attendance = Attendance::factory()->create([
			'user_id'     => $user->id,
			'work_date'   => Carbon::today()->toDateString(), // 2026-01-10
			'clock_in_at' => Carbon::today()->copy()->setTime(9, 0),
			'clock_out_at' => Carbon::today()->copy()->setTime(18, 0),
			'status'      => Attendance::STATUS_FINISHED,
		]);

		AttendanceBreak::factory()->create([
			'attendance_id'   => $attendance->id,
			'break_start_at'  => Carbon::today()->copy()->setTime(12, 0),
			'break_end_at'    => Carbon::today()->copy()->setTime(13, 0),
		]);

		$response = $this->actingAs($admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));
		$response->assertOk();

		$response->assertSee('山田 太郎');

		$response->assertSee('2026年');
		$response->assertSee('1月10日');

		$response->assertSee('09:00');
		$response->assertSee('18:00');

		$response->assertSee('2026年');
		$response->assertSee('1月10日');

		Carbon::setTestNow();
	}

	#[Test]
	public function shows_error_when_clock_in_is_after_clock_out(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->createAdminUser();
		$user  = $this->createGeneralUser();

		$attendance = Attendance::factory()->create([
			'user_id'   => $user->id,
			'work_date' => Carbon::today()->toDateString(),
		]);

		$payload = [
			'clock_in_at'  => '18:00',
			'clock_out_at' => '09:00',
			'breaks'       => [],
			'note'   => 'テスト備考',
			'reason' => 'テスト備考',
		];

		$response = $this->actingAs($admin)
			->from(route('admin.attendance.detail', ['id' => $attendance->id]))
			->put(route('admin.attendance.update', ['id' => $attendance->id]), $payload);

		$response->assertStatus(302);

		$errors = session('errors')?->all() ?? [];
		$this->assertTrue(
			\in_array('出勤時間もしくは退勤時間が不適切な値です', $errors, true),
			'Expected validation message not found.'
		);

		Carbon::setTestNow();
	}

	#[Test]
	public function shows_error_when_break_start_is_after_clock_out(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->createAdminUser();
		$user  = $this->createGeneralUser();

		$attendance = Attendance::factory()->create([
			'user_id'   => $user->id,
			'work_date' => Carbon::today()->toDateString(),
		]);

		$payload = [
			'clock_in_at'  => '09:00',
			'clock_out_at' => '18:00',
			'breaks'       => [
				['start' => '19:00', 'end' => '19:30'],
			],
			'note'   => 'テスト備考',
			'reason' => 'テスト備考',
		];

		$response = $this->actingAs($admin)
			->from(route('admin.attendance.detail', ['id' => $attendance->id]))
			->put(route('admin.attendance.update', ['id' => $attendance->id]), $payload);

		$response->assertStatus(302);

		$errors = session('errors')?->all() ?? [];
		$this->assertTrue(
			\in_array('休憩時間が不適切な値です', $errors, true),
			'Expected validation message not found.'
		);

		Carbon::setTestNow();
	}

	#[Test]
	public function shows_error_when_break_end_is_after_clock_out(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->createAdminUser();
		$user  = $this->createGeneralUser();

		$attendance = Attendance::factory()->create([
			'user_id'   => $user->id,
			'work_date' => Carbon::today()->toDateString(),
		]);

		$payload = [
			'clock_in_at'  => '09:00',
			'clock_out_at' => '18:00',
			'breaks'       => [
				['start' => '17:30', 'end' => '19:00'],
			],
			'note'   => 'テスト備考',
			'reason' => 'テスト備考',
		];

		$response = $this->actingAs($admin)
			->from(route('admin.attendance.detail', ['id' => $attendance->id]))
			->put(route('admin.attendance.update', ['id' => $attendance->id]), $payload);

		$response->assertStatus(302);

		$errors = session('errors')?->all() ?? [];
		$this->assertTrue(
			\in_array('休憩時間もしくは退勤時間が不適切な値です', $errors, true),
			'Expected validation message not found.'
		);

		Carbon::setTestNow();
	}

	#[Test]
	public function shows_error_when_note_is_empty(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->createAdminUser();
		$user  = $this->createGeneralUser();

		$attendance = Attendance::factory()->create([
			'user_id'   => $user->id,
			'work_date' => Carbon::today()->toDateString(),
		]);

		$payload = [
			'clock_in_at'  => '09:00',
			'clock_out_at' => '18:00',
			'breaks'       => [
				['start' => '12:00', 'end' => '13:00'],
			],
			'note'   => '',
			'reason' => '',
		];

		$response = $this->actingAs($admin)
			->from(route('admin.attendance.detail', ['id' => $attendance->id]))
			->put(route('admin.attendance.update', ['id' => $attendance->id]), $payload);

		$response->assertStatus(302);

		$errors = session('errors')?->all() ?? [];
		$this->assertTrue(
			\in_array('備考を記入してください', $errors, true),
			'Expected validation message not found.'
		);

		Carbon::setTestNow();
	}
}
