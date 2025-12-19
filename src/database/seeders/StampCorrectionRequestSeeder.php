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
		// breaks も一緒に読む（これ超重要）
		$attendances = Attendance::with(['breaks' => fn($query) => $query->orderBy('break_start_at')])->get();

		foreach ($attendances as $attendance) {

			// 20%の確率で修正申請
			if (rand(1, 100) > 20) {
				continue;
			}

			if (empty($attendance->clock_in_at) || empty($attendance->clock_out_at)) {
				continue;
			}

			$beforeClockIn  = Carbon::parse($attendance->clock_in_at);
			$beforeClockOut = Carbon::parse($attendance->clock_out_at);
			$beforeBreakMinutes = (int) $attendance->total_break_minutes;

			// 修正パターンを選択
			$type = rand(1, 3);

			// after 初期値
			$afterClockIn  = $beforeClockIn->copy();
			$afterClockOut = $beforeClockOut->copy();
			$reason        = '';

			// まず「休憩明細」は attendance_breaks をコピーする（B方式の前提）
			$correctedBreakRows = [];
			foreach ($attendance->breaks as $break) {
				if (!$break->break_start_at || !$break->break_end_at) {
					continue;
				}
				$correctedBreakRows[] = [
					'start_at' => Carbon::parse($break->break_start_at),
					'end_at'   => Carbon::parse($break->break_end_at),
				];
			}

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

				// ③ 休憩入力漏れ：休憩明細を「追加」する（合計だけ増やさない）
				case 3:
					$reason = '休憩時間の入力漏れ';

					// 追加休憩（15〜45分）を、勤務時間内のどこかに作る
					$additionalMinutes = rand(15, 45);

					// 追加休憩の開始を「出勤+60分〜退勤-60分」の範囲に寄せる（ざっくり安全）
					$latestStart = $afterClockOut->copy()->subMinutes(60 + $additionalMinutes);
					$earliestStart = $afterClockIn->copy()->addMinutes(60);

					if ($latestStart->greaterThan($earliestStart)) {
						$startAt = $earliestStart->copy()->addMinutes(
							rand(0, $earliestStart->diffInMinutes($latestStart))
						);
						$endAt = $startAt->copy()->addMinutes($additionalMinutes);

						$correctedBreakRows[] = [
							'start_at' => $startAt,
							'end_at'   => $endAt,
						];
					}
					break;
			}

			// after_break_minutes は「休憩明細から再計算」して矛盾させない
			$afterBreakMinutes = 0;
			foreach ($correctedBreakRows as $row) {
				$afterBreakMinutes += $row['start_at']->diffInMinutes($row['end_at']);
			}

			// 勤務時間を超える休憩はNG（保険）
			$workMinutes = $afterClockIn->diffInMinutes($afterClockOut);
			if ($afterBreakMinutes >= $workMinutes) {
				// 明細が矛盾するので、このデータは作らない方が安全（落とす）
				continue;
			}

			// 親を作成（返り値を受ける！）
			$stampCorrectionRequest = StampCorrectionRequest::create([
				'attendance_id'        => $attendance->id,
				'user_id'              => $attendance->user_id,

				'before_clock_in_at'   => $beforeClockIn,
				'before_clock_out_at'  => $beforeClockOut,
				'before_break_minutes' => $beforeBreakMinutes,

				'after_clock_in_at'    => $afterClockIn,
				'after_clock_out_at'   => $afterClockOut,
				'after_break_minutes'  => $afterBreakMinutes,

				'reason'               => $reason,

				'status'               => StampCorrectionRequest::STATUS_PENDING,
				'approved_at'          => null,
			]);

			// 子（stamp_correction_breaks）を作成
			$order = 1;
			foreach ($correctedBreakRows as $row) {
				$stampCorrectionRequest->correctionBreaks()->create([
					'break_order'    => $order++,
					'break_start_at' => $row['start_at'],
					'break_end_at'   => $row['end_at'],
				]);
			}
		}
	}
}
