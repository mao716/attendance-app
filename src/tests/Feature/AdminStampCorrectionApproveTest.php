<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStampCorrectionApproveTest extends TestCase
{
	use RefreshDatabase;

	private function makeAdmin(): User
	{
		return User::factory()->create(['role' => 2]);
	}

	private function makeStaff(): User
	{
		return User::factory()->create([
			'role' => 1,
			'name' => '山田 太郎',
			'email_verified_at' => now(),
		]);
	}

	private function makeAttendance(User $staff): Attendance
	{
		return Attendance::factory()->finished('2026-01-10', 60)->create([
			'user_id' => $staff->id,
			'clock_in_at' => Carbon::parse('2026-01-10 09:00:00'),
			'clock_out_at' => Carbon::parse('2026-01-10 18:00:00'),
		]);
	}

	private function createRequestByUser(User $staff, Attendance $attendance): StampCorrectionRequest
	{
		$payload = [
			'clock_in_at' => '10:00',
			'clock_out_at' => '19:00',

			'breaks' => [
				['start' => '13:00', 'end' => '14:00'],
			],

			'reason' => '管理者承認テスト',
		];

		$this->actingAs($staff)
			->post(route('stamp_correction_request.store', ['id' => $attendance->id]), $payload)
			->assertStatus(302);

		return StampCorrectionRequest::query()
			->where('attendance_id', $attendance->id)
			->latest('id')
			->firstOrFail();
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function pending_requests_are_displayed_on_request_list(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->makeAdmin();
		$staff = $this->makeStaff();
		$attendance = $this->makeAttendance($staff);
		$request = $this->createRequestByUser($staff, $attendance);

		$response = $this->actingAs($admin)->get(route('stamp_correction_request.list'));
		$response->assertOk();

		$response->assertSee('申請一覧');
		$response->assertSee($staff->name);

		$response->assertSee((string) $request->id);

		Carbon::setTestNow();
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function approved_requests_are_displayed_on_request_list(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->makeAdmin();
		$staff = $this->makeStaff();
		$attendance = $this->makeAttendance($staff);
		$request = $this->createRequestByUser($staff, $attendance);

		$request->update([
			'status' => 1,
			'approved_at' => now(),
		]);

		$response = $this->actingAs($admin)->get(route('stamp_correction_request.list', [
			'status' => 'approved',
		]));
		$response->assertOk();

		$response->assertSee('申請一覧');
		$response->assertSee((string) $request->id);

		Carbon::setTestNow();
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function admin_can_open_request_detail_and_contents_are_correct(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->makeAdmin();
		$staff = $this->makeStaff();
		$attendance = $this->makeAttendance($staff);
		$request = $this->createRequestByUser($staff, $attendance);

		$response = $this->actingAs($admin)->get(route('admin.stamp_correction_request.show', [
			'attendance_correct_request_id' => $request->id,
		]));
		$response->assertOk();

		$response->assertSee($staff->name);

		Carbon::setTestNow();
	}

	#[\PHPUnit\Framework\Attributes\Test]
	public function admin_can_approve_request_and_attendance_is_updated(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$admin = $this->makeAdmin();
		$staff = $this->makeStaff();
		$attendance = $this->makeAttendance($staff);
		$request = $this->createRequestByUser($staff, $attendance);

		$approveResponse = $this->actingAs($admin)->post(route('admin.stamp_correction_request.approve', [
			'attendance_correct_request_id' => $request->id,
		]));
		$approveResponse->assertStatus(302);

		$attendance->refresh();
		$request->refresh();

		$this->assertEquals(1, $request->status);

		$this->assertNotNull($attendance->clock_in_at);
		$this->assertNotNull($attendance->clock_out_at);

		Carbon::setTestNow();
	}
}
