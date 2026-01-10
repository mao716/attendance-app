<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
	use RefreshDatabase;

	#[Test]
	public function status_label_is_displayed_correctly(): void
	{
		Carbon::setTestNow(Carbon::create(2026, 1, 9, 8, 15, 0, 'Asia/Tokyo'));

		$user = User::factory()->create([
			'email_verified_at' => now(),
		]);

		$workDate = now('Asia/Tokyo')->toDateString();

		$cases = [
			'勤務外' => [
				'status' => Attendance::STATUS_OFF,
				'factory' => null,
			],
			'出勤中' => [
				'status' => Attendance::STATUS_WORKING,
				'factory' => fn() => Attendance::factory()->workingOn($workDate),
			],
			'休憩中' => [
				'status' => Attendance::STATUS_BREAK,
				'factory' => fn() => Attendance::factory()->onBreakOn($workDate),
			],
			'退勤済' => [
				'status' => Attendance::STATUS_FINISHED,
				'factory' => fn() => Attendance::factory()->finishedOn($workDate),
			],
		];

		foreach ($cases as $expectedLabel => $case) {
			Attendance::query()->delete();

			if ($case['factory'] === null) {

			} else {
				/** @var \Illuminate\Database\Eloquent\Factories\Factory $factory */
				$factory = $case['factory']();
				$factory->create([
					'user_id' => $user->id,
				]);
			}

			$response = $this->actingAs($user)->get(route('attendance.index'));

			$response->assertStatus(200);
			$response->assertSee($expectedLabel);
		}

		Carbon::setTestNow();
	}
}
