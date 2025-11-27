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
		// 対象：全ユーザーの勤怠データ
		$attendances = Attendance::all();

		foreach ($attendances as $attendance) {

			// ★ ランダム20%の確率で修正申請をつくる
			// （= 全日数の2割くらいに申請がある感じ → 自然）
			if (rand(1, 100) > 20) {
				continue;
			}

			$clockIn  = Carbon::parse($attendance->clock_in_at);
			$clockOut = Carbon::parse($attendance->clock_out_at);

			// 元の休憩時間
			$beforeBreak = $attendance->total_break_minutes;

			// before（修正前）はオリジナル値そのまま
			$beforeClockIn  = $clockIn->copy();
			$beforeClockOut = $clockOut->copy();

			// after（修正後）は、少しずらす（±0〜10分）
			$afterClockIn = $clockIn->copy()->subMinutes(rand(0, 10));
			$afterClockOut = $clockOut->copy()->addMinutes(rand(0, 10));

			$afterBreak = rand(30, 90); // 修正後の休憩

			// ステータス（0=承認待ち / 1=承認済み）
			$status = rand(0, 1);

			StampCorrectionRequest::create([
				'attendance_id'        => $attendance->id,
				'user_id'              => $attendance->user_id,

				'before_clock_in_at'   => $beforeClockIn,
				'before_clock_out_at'  => $beforeClockOut,
				'before_break_minutes' => $beforeBreak,

				'after_clock_in_at'    => $afterClockIn,
				'after_clock_out_at'   => $afterClockOut,
				'after_break_minutes'  => $afterBreak,

				'reason'               => '打刻漏れのため修正をお願いします。',

				'status'               => $status,
				'approved_at'          => $status === 1 ? now() : null,
			]);
		}
	}
}
