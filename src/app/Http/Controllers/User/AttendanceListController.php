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
		// ?month=YYYY-MM（なければ今月）
		$monthParam = $request->query('month');

		$targetMonth = $this->resolveTargetMonth($monthParam);

		$year  = $targetMonth->year;
		$month = $targetMonth->month;

		$attendances = Attendance::query()
			->where('user_id', Auth::id())
			->whereYear('work_date', $year)
			->whereMonth('work_date', $month)
			->orderBy('work_date')
			->get();

		$attendancesByDate = $attendances->keyBy(function (Attendance $attendance) {
			return $attendance->work_date->toDateString();
		});

		$weekdays = ['日', '月', '火', '水', '木', '金', '土'];

		$rows = [];
		$daysInMonth = $targetMonth->daysInMonth;

		for ($dayOffset = 0; $dayOffset < $daysInMonth; $dayOffset++) {
			$date = $targetMonth->copy()->addDays($dayOffset);
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

	private function resolveTargetMonth(?string $monthParam): Carbon
	{
		try {
			return $monthParam
				? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
				: now()->startOfMonth();
		} catch (\Throwable) {
			return now()->startOfMonth();
		}
	}
}
