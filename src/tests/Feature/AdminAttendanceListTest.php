<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
	use RefreshDatabase;

	private function adminRoleValue(): int
	{
		if (defined(User::class . '::ROLE_ADMIN')) {
			return constant(User::class . '::ROLE_ADMIN');
		}

		return 2;
	}

	private function createAdminUser(): User
	{
		return User::factory()->create([
			'name' => '管理者 太郎',
			'email' => 'admin@example.com',
			'role' => $this->adminRoleValue(),
		]);
	}

	private function createNormalUser(string $name, string $email): User
	{
		$user = User::factory()->create([
			'name' => $name,
			'email' => $email,
		]);

		$user->forceFill(['email_verified_at' => now()])->save();

		return $user;
	}

	private function createFinishedAttendance(User $user, string $workDate, string $clockIn, string $clockOut): Attendance
	{
		return Attendance::factory()
			->for($user)
			->finished($workDate, 60)
			->create([
				'work_date' => $workDate,
				'clock_in_at' => Carbon::parse($workDate . ' ' . $clockIn),
				'clock_out_at' => Carbon::parse($workDate . ' ' . $clockOut),
				'status' => Attendance::STATUS_FINISHED,
				'total_break_minutes' => 60,
				'working_minutes' => 7 * 60,
			]);
	}

	#[Test]
	public function admin_can_see_all_users_attendances_for_the_selected_day(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->createAdminUser();

		$u1 = $this->createNormalUser('ユーザーA', 'a@example.com');
		$u2 = $this->createNormalUser('ユーザーB', 'b@example.com');

		$targetDate = now()->toDateString();

		$this->createFinishedAttendance($u1, $targetDate, '09:00', '18:00');
		$this->createFinishedAttendance($u2, $targetDate, '10:00', '19:00');

		$this->createFinishedAttendance($u1, now()->copy()->subDay()->toDateString(), '09:00', '18:00');

		$response = $this->actingAs($admin)->get(route('admin.attendance.list', ['date' => $targetDate]));
		$response->assertOk();

		$response->assertSee('ユーザーA');
		$response->assertSee('ユーザーB');

		$response->assertSee('09:00');
		$response->assertSee('18:00');
		$response->assertSee('10:00');
		$response->assertSee('19:00');

		Carbon::setTestNow();
	}

	#[Test]
	public function when_opening_admin_attendance_list_current_date_is_displayed(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->createAdminUser();

		$response = $this->actingAs($admin)->get(route('admin.attendance.list'));
		$response->assertOk();

		$content = $response->getContent();
		$this->assertMatchesRegularExpression(
			'/2026(\/|-|年)\s*0?1(\/|-|月)\s*0?10(日)?/u',
			$content
		);

		Carbon::setTestNow();
	}

	#[Test]
	public function admin_can_navigate_to_previous_day_from_admin_attendance_list(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->createAdminUser();

		$targetDate = now()->toDateString();
		$prevDate = now()->copy()->subDay()->toDateString();

		$response = $this->actingAs($admin)->get(route('admin.attendance.list', ['date' => $targetDate]));
		$response->assertOk();

		$prevUrl = route('admin.attendance.list', ['date' => $prevDate]);
		$response->assertSee($prevUrl, false);

		$this->actingAs($admin)->get($prevUrl)->assertOk();

		Carbon::setTestNow();
	}

	#[Test]
	public function admin_can_navigate_to_next_day_from_admin_attendance_list(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->createAdminUser();

		$targetDate = now()->toDateString();
		$nextDate = now()->copy()->addDay()->toDateString();

		$response = $this->actingAs($admin)->get(route('admin.attendance.list', ['date' => $targetDate]));
		$response->assertOk();

		$nextUrl = route('admin.attendance.list', ['date' => $nextDate]);
		$response->assertSee($nextUrl, false);

		$this->actingAs($admin)->get($nextUrl)->assertOk();

		Carbon::setTestNow();
	}
}
