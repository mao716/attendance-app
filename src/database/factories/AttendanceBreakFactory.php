<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceBreakFactory extends Factory
{
	public function definition(): array
	{
		// Attendance が無いときは自動生成
		$attendance = Attendance::inRandomOrder()->first()
			?? Attendance::factory()->create();

		// Carbon で扱う（必須）
		$clockIn  = Carbon::parse($attendance->clock_in_at);
		$clockOut = Carbon::parse($attendance->clock_out_at);

		// 休憩開始：出勤〜退勤の間でランダム
		$breakStart = $this->faker->dateTimeBetween(
			$clockIn->format('Y-m-d H:i:s'),
			$clockOut->format('Y-m-d H:i:s')
		);

		// 休憩終了：開始〜退勤の間でランダム
		$breakEnd = $this->faker->dateTimeBetween(
			$breakStart->format('Y-m-d H:i:s'),
			$clockOut->format('Y-m-d H:i:s')
		);

		return [
			'attendance_id'   => $attendance->id,
			'break_start_at'  => $breakStart,
			'break_end_at'    => $breakEnd,
		];
	}
}
