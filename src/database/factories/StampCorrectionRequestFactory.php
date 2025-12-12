<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class StampCorrectionRequestFactory extends Factory
{
	public function definition(): array
	{
		$attendance = Attendance::inRandomOrder()->first()
			?? Attendance::factory()->create();

		$beforeClockIn  = $attendance->clock_in_at;
		$beforeClockOut = $attendance->clock_out_at;

		$afterClockIn  = optional($beforeClockIn)?->copy()->addMinutes(5);
		$afterClockOut = optional($beforeClockOut)?->copy()->addMinutes(10);

		return [
			'attendance_id' => $attendance->id,
			'user_id'       => $attendance->user_id,

			'before_clock_in_at'   => $beforeClockIn,
			'before_clock_out_at'  => $beforeClockOut,
			'before_break_minutes' => $attendance->total_break_minutes,

			'after_clock_in_at'    => $afterClockIn,
			'after_clock_out_at'   => $afterClockOut,
			'after_break_minutes'  => max(0, $attendance->total_break_minutes - 5),

			'reason' => 'テスト用修正申請',

			// Seeder 側で上書き前提
			'status' => StampCorrectionRequest::STATUS_PENDING,
		];
	}
}
