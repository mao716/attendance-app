<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceListController extends Controller
{
	public function index(Request $request)
	{
		$userId = auth()->id();

		// ?month=2025-09 みたいなパラメータ（なければ今月）
		$monthParam = $request->query('month');

		if ($monthParam) {
			try {
				$targetMonth = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
			} catch (\Exception $e) {
				$targetMonth = now()->startOfMonth();
			}
		} else {
			$targetMonth = now()->startOfMonth();
		}

		$year  = $targetMonth->year;
		$month = $targetMonth->month;

		// ログインユーザーの「その月だけ」の勤怠
		$attendances = Attendance::where('user_id', $userId)
			->whereYear('work_date', $year)
			->whereMonth('work_date', $month)
			->orderBy('work_date')
			->get();

		// 日付ごとに引きやすいように keyBy
		$attendancesByDate = $attendances->keyBy(function ($attendance) {
			// work_date が Carbon キャストされていれば ->toDateString() でもOK
			return Carbon::parse($attendance->work_date)->toDateString(); // 'YYYY-MM-DD'
		});

		// その月の全日分 rows 配列を作る
		$startOfMonth = $targetMonth->copy();
		$daysInMonth  = $startOfMonth->daysInMonth;

		$weekdays = ['日', '月', '火', '水', '木', '金', '土'];

		$rows = [];

		for ($day = 0; $day < $daysInMonth; $day++) {
			$date    = $startOfMonth->copy()->addDays($day);
			$dateKey = $date->toDateString(); // 'YYYY-MM-DD'

			/** @var \App\Models\Attendance|null $attendance */
			$attendance = $attendancesByDate->get($dateKey);

			$weekdayLabel = $weekdays[$date->dayOfWeek];
			// 例: "09/01(金)" みたいな表示
			$dateLabel = sprintf('%02d/%02d(%s)', $date->month, $date->day, $weekdayLabel);

			$rows[] = [
				'date'          => $date,
				'date_label'    => $dateLabel,
				'clock_in'      => $attendance?->clock_in_at?->format('H:i') ?? '',
				'clock_out'     => $attendance?->clock_out_at?->format('H:i') ?? '',
				'break_minutes' => $attendance?->total_break_minutes,
				'work_minutes'  => $attendance?->working_minutes,
				'attendance_id' => $attendance?->id,
			];
		}

		// 前月・翌月パラメータ
		$prevMonthParam = $targetMonth->copy()->subMonth()->format('Y-m');
		$nextMonthParam = $targetMonth->copy()->addMonth()->format('Y-m');

		return view('attendance.list', [
			'rows'          => $rows,
			'targetMonth'   => $targetMonth,
			'prevMonthParam' => $prevMonthParam,
			'nextMonthParam' => $nextMonthParam,
		]);
	}
}
