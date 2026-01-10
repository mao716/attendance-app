<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
	use RefreshDatabase;

	private function createVerifiedUser(): User
	{
		return User::factory()->create([
			'email_verified_at' => now(),
		]);
	}

	#[Test]
	public function only_my_attendances_are_displayed_in_list(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 15, 9, 0, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();
		$otherUser = User::factory()->create();

		Attendance::factory()->for($user)->finished('2026-01-10')->create();
		Attendance::factory()->for($user)->finished('2026-01-11')->create();

		Attendance::factory()->for($otherUser)->finished('2026-01-10')->create();

		$response = $this->actingAs($user)->get(route('attendance.list'));

		$response->assertStatus(200);
		$response->assertSee('01/10');
		$response->assertSee('01/11');
		$response->assertDontSee($otherUser->name);

		Carbon::setTestNow();
	}

	#[Test]
	public function attendance_list_month_navigation_works_correctly(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 15, 9, 0, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();

		Attendance::factory()->for($user)->finished('2025-12-20')->create();
		Attendance::factory()->for($user)->finished('2026-01-10')->create();
		Attendance::factory()->for($user)->finished('2026-02-05')->create();

		$response = $this->actingAs($user)->get(route('attendance.list'));
		$response->assertStatus(200);
		$response->assertSee('2026/01');

		$responsePrev = $this->actingAs($user)->get(
			route('attendance.list', ['month' => '2025-12'])
		);
		$responsePrev->assertStatus(200);
		$responsePrev->assertSee('2025/12');
		$responsePrev->assertSee('12/20');

		$responseNext = $this->actingAs($user)->get(
			route('attendance.list', ['month' => '2026-02'])
		);
		$responseNext->assertStatus(200);
		$responseNext->assertSee('2026/02');
		$responseNext->assertSee('02/05');

		Carbon::setTestNow();
	}

	#[Test]
	public function user_can_navigate_to_attendance_detail_from_list(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 15, 9, 0, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();

		$attendance = Attendance::factory()
			->for($user)
			->finished('2026-01-10')
			->create();

		$response = $this->actingAs($user)->get(route('attendance.list'));

		$response->assertStatus(200);

		$response->assertSee(
			route('attendance.detail', ['id' => $attendance->id]),
			false
		);

		$detail = $this->actingAs($user)->get(
			route('attendance.detail', ['id' => $attendance->id])
		);

		$detail->assertStatus(200);

		Carbon::setTestNow();
	}
}
