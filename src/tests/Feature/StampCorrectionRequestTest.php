<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StampCorrectionRequestTest extends TestCase
{
	use RefreshDatabase;

	private function pendingStatus(): int
	{
		return defined(StampCorrectionRequest::class . '::STATUS_PENDING')
			? constant(StampCorrectionRequest::class . '::STATUS_PENDING')
			: 0;
	}

	private function approvedStatus(): int
	{
		return defined(StampCorrectionRequest::class . '::STATUS_APPROVED')
			? constant(StampCorrectionRequest::class . '::STATUS_APPROVED')
			: 1;
	}

	private function createVerifiedUser(array $overrides = []): User
	{
		$user = User::factory()->create(array_merge([
			'name' => '山田 太郎',
			'email' => 'taro@example.com',
		], $overrides));

		$user->forceFill(['email_verified_at' => now()])->save();

		return $user;
	}

	private function createFinishedAttendance(User $user, string $workDate): Attendance
	{
		return Attendance::factory()
			->for($user)
			->finished($workDate, 60)
			->create([
				'status' => Attendance::STATUS_FINISHED,
			]);
	}

	private function basePayload(): array
	{
		return [
			'clock_in_at' => '09:00',
			'clock_out_at' => '18:00',
			'breaks' => [
				['start' => '12:00', 'end' => '13:00'],
			],
			'reason' => 'テスト修正申請',
		];
	}

	#[Test]
	public function shows_error_when_clock_in_is_after_clock_out(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();
		$attendance = $this->createFinishedAttendance($user, now()->toDateString());

		$payload = $this->basePayload();
		$payload['clock_in_at'] = '19:00';
		$payload['clock_out_at'] = '18:00';

		$response = $this->actingAs($user)
			->post(route('stamp_correction_request.store', ['id' => $attendance->id]), $payload);

		$response->assertStatus(302);
		$response->assertSessionHasErrors();

		$messages = implode(' ', session('errors')->all());

		$this->assertStringContainsString('出勤時間もしくは退勤時間が不適切な値です', $messages);

		Carbon::setTestNow();
	}

	#[Test]
	public function shows_error_when_break_start_is_after_clock_out(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();
		$attendance = $this->createFinishedAttendance($user, now()->toDateString());

		$payload = $this->basePayload();
		$payload['breaks'][0]['start'] = '19:00';
		$payload['breaks'][0]['end'] = '19:30';

		$response = $this->actingAs($user)
			->post(route('stamp_correction_request.store', ['id' => $attendance->id]), $payload);

		$response->assertSessionHasErrors(['breaks.0.start']);
		$response->assertSessionHasErrors([
			'breaks.0.start' => '休憩時間が不適切な値です',
		]);

		Carbon::setTestNow();
	}

	#[Test]
	public function shows_error_when_break_end_is_after_clock_out(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();
		$attendance = $this->createFinishedAttendance($user, now()->toDateString());

		$payload = $this->basePayload();
		$payload['breaks'][0]['start'] = '17:30';
		$payload['breaks'][0]['end'] = '18:30';

		$response = $this->actingAs($user)
			->post(route('stamp_correction_request.store', ['id' => $attendance->id]), $payload);

		$response->assertSessionHasErrors(['breaks.0.end']);
		$response->assertSessionHasErrors([
			'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です',
		]);

		Carbon::setTestNow();
	}

	#[Test]
	public function shows_error_when_reason_is_empty(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();
		$attendance = $this->createFinishedAttendance($user, now()->toDateString());

		$payload = $this->basePayload();
		$payload['reason'] = '';

		$response = $this->actingAs($user)
			->post(route('stamp_correction_request.store', ['id' => $attendance->id]), $payload);

		$response->assertSessionHasErrors(['reason']);
		$response->assertSessionHasErrors([
			'reason' => '備考を記入してください',
		]);

		Carbon::setTestNow();
	}

	#[Test]
	public function user_can_create_request_and_it_is_listed_and_detail_is_accessible(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 10, 10, 0, 0, 'Asia/Tokyo'));

		$user = $this->createVerifiedUser();
		$attendance = $this->createFinishedAttendance($user, now()->toDateString());

		AttendanceBreak::factory()->for($attendance)->create([
			'break_start_at' => Carbon::parse(now()->toDateString())->setTime(12, 0),
			'break_end_at' => Carbon::parse(now()->toDateString())->setTime(13, 0),
		]);

		$payload = $this->basePayload();

		$this->actingAs($user)
			->post(route('stamp_correction_request.store', ['id' => $attendance->id]), $payload)
			->assertStatus(302);

		$request = StampCorrectionRequest::query()
			->where('attendance_id', $attendance->id)
			->where('user_id', $user->id)
			->latest('id')
			->first();

		$this->assertNotNull($request);

		$this->assertDatabaseHas('stamp_correction_requests', [
			'id' => $request->id,
			'attendance_id' => $attendance->id,
			'user_id' => $user->id,
			'after_break_minutes' => 60,
			'reason' => 'テスト修正申請',
			'status' => $this->pendingStatus(),
		]);

		$listResponse = $this->actingAs($user)->get(route('stamp_correction_request.list'));
		$listResponse->assertOk();

		$requestShowUrl = route('stamp_correction_request.user_show', [
			'stampCorrectionRequest' => $request->id,
		]);

		$listResponse = $this->actingAs($user)->get(route('stamp_correction_request.list'));
		$listResponse->assertOk();

		$this->actingAs($user)
			->get(route('stamp_correction_request.user_show', ['stampCorrectionRequest' => $request->id]))
			->assertOk();

		$request->update([
			'status' => $this->approvedStatus(),
			'approved_at' => now(),
		]);

		$approvedList = $this->actingAs($user)->get(route('stamp_correction_request.list', ['status' => 'approved']));
		$approvedList->assertOk();

		$listResponse = $this->actingAs($user)->get(route('stamp_correction_request.list'));
		$listResponse->assertOk();

		$this->assertDatabaseHas('stamp_correction_requests', [
			'id' => $request->id,
			'user_id' => $user->id,
			'attendance_id' => $attendance->id,
		]);

		$this->actingAs($user)
			->get(route('stamp_correction_request.user_show', ['stampCorrectionRequest' => $request->id]))
			->assertOk();

		Carbon::setTestNow();
	}
}
