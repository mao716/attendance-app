<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

class AttendanceBreakSeeder extends Seeder
{
	public function run(): void
	{
		$attendances = Attendance::all();

		foreach ($attendances as $attendance) {
			// 出勤 or 退勤が入ってないレコードはスキップ（保険）
			if (empty($attendance->clock_in_at) || empty($attendance->clock_out_at)) {
				continue;
			}

			$clockIn  = Carbon::parse($attendance->clock_in_at);
			$clockOut = Carbon::parse($attendance->clock_out_at);

			// 1日の労働時間（分）
			$workMinutes = $clockIn->diffInMinutes($clockOut);

			// 労働時間が短すぎる日は休憩を作らない（3時間未満とか）
			if ($workMinutes < 180) {
				continue;
			}

			// この日の休憩回数（0〜3回）
			$breakCount = rand(0, 3);

			for ($i = 0; $i < $breakCount; $i++) {
				// 1回の休憩時間（10〜60分）
				$breakDuration = rand(10, 60);

				// 休憩開始を置ける最大のオフセット（分）
				$maxOffset = $workMinutes - $breakDuration;
				if ($maxOffset <= 0) {
					continue;
				}

				// 出勤から何分後に休憩を開始するか
				$offsetMinutes = rand(0, $maxOffset);

				$breakStart = $clockIn->copy()->addMinutes($offsetMinutes);
				$breakEnd   = $breakStart->copy()->addMinutes($breakDuration);

				// 念のため退勤時間を越えないようクリップ
				if ($breakEnd->greaterThan($clockOut)) {
					$breakEnd = $clockOut->copy();
				}

				AttendanceBreak::create([
					'attendance_id'  => $attendance->id,
					'break_start_at' => $breakStart,
					'break_end_at'   => $breakEnd,
				]);
			}
		}
	}
}
