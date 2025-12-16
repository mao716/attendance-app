<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceListController extends Controller
{
	public function index(Request $request)
	{
		// ?month=2025-12 の形式（なければ今月）
		$monthParam = $request->query('month');

		try {
			$targetMonth = $monthParam
				? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
				: Carbon::now()->startOfMonth();
		} catch (\Exception $exception) {
			$targetMonth = Carbon::now()->startOfMonth();
		}

		$year  = $targetMonth->year;
		$month = $targetMonth->month;

		// その月の勤怠（一覧は「勤怠の最新＝attendances」を表示するだけでOK）
		$attendances = Attendance::query()
			->where('user_id', Auth::id())
			->whereYear('work_date', $year)
			->whereMonth('work_date', $month)
			->orderBy('work_date')
			->get();

		// 日付で引けるようにする（1ユーザー1日1レコード前提）
		$attendancesByDate = $attendances->keyBy(function (Attendance $attendance) {
			return $attendance->work_date->toDateString();
		});

		$weekdays = ['日', '月', '火', '水', '木', '金', '土'];

		$rows = [];
		$daysInMonth = $targetMonth->daysInMonth;

		for ($dayIndex = 0; $dayIndex < $daysInMonth; $dayIndex++) {
			$date = $targetMonth->copy()->addDays($dayIndex);
			$dateKey = $date->toDateString();

			/** @var Attendance|null $attendance */
			$attendance = $attendancesByDate->get($dateKey);

			$weekdayLabel = $weekdays[$date->dayOfWeek];
			$dateLabel = sprintf('%02d/%02d(%s)', $date->month, $date->day, $weekdayLabel);

			$rows[] = [
				'date_label'    => $dateLabel,
				'clock_in'      => $attendance?->clock_in_at?->format('H:i') ?? '',
				'clock_out'     => $attendance?->clock_out_at?->format('H:i') ?? '',
				'break_minutes' => $attendance?->total_break_minutes,
				'work_minutes'  => $attendance?->working_minutes,
				'attendance_id' => $attendance?->id,
			];
		}

		return view('attendance.list', [
			'rows'           => $rows,
			'targetMonth'    => $targetMonth,
			'prevMonthParam' => $targetMonth->copy()->subMonth()->format('Y-m'),
			'nextMonthParam' => $targetMonth->copy()->addMonth()->format('Y-m'),
		]);
	}
}
