<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;

class StampCorrectionRequestSeeder extends Seeder
{
	public function run(): void
	{
		$attendances = Attendance::all();

		foreach ($attendances as $attendance) {

			// 20%の確率で修正申請
			if (rand(1, 100) > 20) {
				continue;
			}

			if (empty($attendance->clock_in_at) || empty($attendance->clock_out_at)) {
				continue;
			}

			$clockIn  = Carbon::parse($attendance->clock_in_at);
			$clockOut = Carbon::parse($attendance->clock_out_at);

			$beforeBreak = (int) $attendance->total_break_minutes;

			// 修正パターンを選択
			$type = rand(1, 3);

			// before（共通）
			$beforeClockIn  = $clockIn->copy();
			$beforeClockOut = $clockOut->copy();

			// 初期値（beforeと同じ）
			$afterClockIn  = $beforeClockIn->copy();
			$afterClockOut = $beforeClockOut->copy();
			$afterBreak    = $beforeBreak;
			$reason        = '';

			switch ($type) {
				// ① 遅刻申請
				case 1:
					$afterClockIn = $beforeClockIn->copy()->addMinutes(rand(10, 30));
					$reason = '遅刻のため出勤時刻を修正';
					break;

				// ② 退勤打刻漏れ
				case 2:
					$afterClockOut = $beforeClockOut->copy()->addMinutes(rand(30, 60));
					$reason = '退勤打刻漏れのため';
					break;

				// ③ 休憩入力漏れ
				case 3:
					$afterBreak = $beforeBreak + rand(15, 45);
					$reason = '休憩時間の入力漏れ';
					break;
			}

			// 勤務時間を超える休憩はNG（保険）
			$workMinutes = $afterClockIn->diffInMinutes($afterClockOut);
			if ($afterBreak >= $workMinutes) {
				$afterBreak = max(0, $workMinutes - 1);
			}

			StampCorrectionRequest::create([
				'attendance_id'        => $attendance->id,
				'user_id'              => $attendance->user_id,

				'before_clock_in_at'   => $beforeClockIn,
				'before_clock_out_at'  => $beforeClockOut,
				'before_break_minutes' => $beforeBreak,

				'after_clock_in_at'    => $afterClockIn,
				'after_clock_out_at'   => $afterClockOut,
				'after_break_minutes'  => $afterBreak,

				'reason'               => $reason,

				// 承認待ち固定
				'status'               => 0,
				'approved_at'          => null,
			]);
		}
	}
}
