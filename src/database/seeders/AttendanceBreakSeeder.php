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
			if (empty($attendance->clock_in_at) || empty($attendance->clock_out_at)) {
				continue;
			}

			$clockIn  = Carbon::parse($attendance->clock_in_at);
			$clockOut = Carbon::parse($attendance->clock_out_at);

			$workMinutes = $clockIn->diffInMinutes($clockOut);
			if ($workMinutes <= 0) {
				continue;
			}

			$totalBreak = (int) ($attendance->total_break_minutes ?? 0);

			// 合計休憩が0なら明細も作らない
			if ($totalBreak <= 0) {
				continue;
			}

			// 勤務時間を超える休憩はあり得ないので保険
			if ($totalBreak >= $workMinutes) {
				$totalBreak = max(0, $workMinutes - 1);
			}
			if ($totalBreak === 0) {
				continue;
			}

			// 休憩回数：1〜3回（合計が小さいなら1回）
			$breakCount = ($totalBreak < 20) ? 1 : rand(1, 3);

			// 合計$totalBreakを$breakCount個に分割（各1分以上）
			$durations = $this->splitMinutes($totalBreak, $breakCount);

			// 休憩同士が重ならないように「順に置く」
			// まず開始位置の余白（勤務時間 - 合計休憩）
			$freeMinutes = $workMinutes - array_sum($durations);
			$startOffset = rand(0, max(0, $freeMinutes));

			$cursor = $clockIn->copy()->addMinutes($startOffset);

			foreach ($durations as $duration) {
				$breakStart = $cursor->copy();
				$breakEnd   = $breakStart->copy()->addMinutes($duration);

				if ($breakEnd->greaterThan($clockOut)) {
					$breakEnd = $clockOut->copy();
				}

				AttendanceBreak::create([
					'attendance_id'  => $attendance->id,
					'break_start_at' => $breakStart,
					'break_end_at'   => $breakEnd,
				]);

				// 次の休憩までの間隔（0〜60分）
				$gap = rand(0, 60);
				$cursor = $breakEnd->copy()->addMinutes($gap);

				// はみ出し保険（次が置けないなら終了）
				if ($cursor->greaterThanOrEqualTo($clockOut)) {
					break;
				}
			}
		}
	}

	private function splitMinutes(int $minutes, int $count): array
	{
		if ($count <= 1) {
			return [$minutes];
		}

		// 最低1分ずつ確保
		$remaining = $minutes - $count;
		$parts = array_fill(0, $count, 1);

		for ($i = 0; $i < $count; $i++) {
			if ($remaining <= 0) break;

			$add = ($i === $count - 1) ? $remaining : rand(0, $remaining);
			$parts[$i] += $add;
			$remaining -= $add;
		}

		shuffle($parts);
		return $parts;
	}
}
