<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
	use RefreshDatabase;

	#[\PHPUnit\Framework\Attributes\Test]
	public function admin_can_see_all_staff_names_and_emails(): void
	{
		$admin = User::factory()->create(['role' => 2]);

		User::factory()->create(['role' => 1, 'name' => '山田 太郎', 'email' => 'taro@example.com']);
		User::factory()->create(['role' => 1, 'name' => '佐藤 花子', 'email' => 'hanako@example.com']);

		$response = $this->actingAs($admin)->get(route('admin.staff.list'));

		$response->assertOk();
		$response->assertSee('山田 太郎');
		$response->assertSee('taro@example.com');
		$response->assertSee('佐藤 花子');
		$response->assertSee('hanako@example.com');
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function admin_can_see_selected_staff_attendances_correctly(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = User::factory()->create(['role' => 2]);
		$staff = User::factory()->create(['role' => 1, 'name' => '山田 太郎']);

		Attendance::factory()->finished('2026-01-10', 60)->create([
			'user_id' => $staff->id,
			'clock_in_at' => Carbon::parse('2026-01-10 09:00:00'),
			'clock_out_at' => Carbon::parse('2026-01-10 18:00:00'),
		]);

		$response = $this->actingAs($admin)->get(route('admin.attendance.staff', ['id' => $staff->id]));

		$response->assertOk();

		$response->assertSee('山田 太郎');
		$response->assertSee('09:00');
		$response->assertSee('18:00');

		Carbon::setTestNow();
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function staff_attendance_month_prev_navigation_works(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = User::factory()->create(['role' => 2]);
		$staff = User::factory()->create(['role' => 1]);

		Attendance::factory()->finished('2025-12-10', 60)->create([
			'user_id' => $staff->id,
		]);

		$response = $this->actingAs($admin)->get(route('admin.attendance.staff', [
			'id' => $staff->id,
			'month' => '2026-01',
		]));

		$response->assertOk();

		$prevUrl = route('admin.attendance.staff', ['id' => $staff->id, 'month' => '2025-12']);
		$response->assertSee($prevUrl, false);

		Carbon::setTestNow();
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function staff_attendance_month_next_navigation_works(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = User::factory()->create(['role' => 2]);
		$staff = User::factory()->create(['role' => 1]);

		Attendance::factory()->finished('2026-02-10', 60)->create([
			'user_id' => $staff->id,
		]);

		$response = $this->actingAs($admin)->get(route('admin.attendance.staff', [
			'id' => $staff->id,
			'month' => '2026-01',
		]));

		$response->assertOk();

		$nextUrl = route('admin.attendance.staff', ['id' => $staff->id, 'month' => '2026-02']);
		$response->assertSee($nextUrl, false);

		Carbon::setTestNow();
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function admin_can_navigate_to_attendance_detail_from_staff_attendance(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = User::factory()->create(['role' => 2]);
		$staff = User::factory()->create(['role' => 1]);

		$attendance = Attendance::factory()->finished('2026-01-10', 60)->create([
			'user_id' => $staff->id,
		]);

		$listResponse = $this->actingAs($admin)->get(route('admin.attendance.staff', [
			'id' => $staff->id,
			'month' => '2026-01',
		]));
		$listResponse->assertOk();

		$detailUrl = route('admin.attendance.detail', ['id' => $attendance->id]);
		$listResponse->assertSee($detailUrl, false);

		$this->actingAs($admin)->get($detailUrl)->assertOk();

		Carbon::setTestNow();
	}
}
