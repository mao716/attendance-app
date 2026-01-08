<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceListController extends Controller
{
	private const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

	public function index(Request $request): View
	{
		$monthParam = $request->query('month');
		$targetMonth = $this->resolveTargetMonth($monthParam);

		$year = $targetMonth->year;
		$month = $targetMonth->month;

		$attendances = Attendance::query()
			->where('user_id', Auth::id())
			->whereYear('work_date', $year)
			->whereMonth('work_date', $month)
			->orderBy('work_date')
			->get();

		$attendancesByDate = $attendances->keyBy(function (Attendance $attendance): string {
			return $attendance->work_date->toDateString();
		});

		$rows = [];
		$daysInMonth = $targetMonth->daysInMonth;

		for ($dayOffset = 0; $dayOffset < $daysInMonth; $dayOffset++) {
			$date = $targetMonth->copy()->addDays($dayOffset);
			$dateKey = $date->toDateString();

			$attendance = $attendancesByDate->get($dateKey);

			$weekdayLabel = self::WEEKDAYS[$date->dayOfWeek];
			$dateLabel = sprintf('%02d/%02d(%s)', $date->month, $date->day, $weekdayLabel);

			$rows[] = [
				'date_label' => $dateLabel,
				'clock_in' => $attendance?->clock_in_at?->format('H:i') ?? '',
				'clock_out' => $attendance?->clock_out_at?->format('H:i') ?? '',
				'break_minutes' => $attendance?->total_break_minutes,
				'work_minutes' => $attendance?->working_minutes,
				'attendance_id' => $attendance?->id,
			];
		}

		return view('attendance.list', [
			'rows' => $rows,
			'targetMonth' => $targetMonth,
			'prevMonthParam' => $targetMonth->copy()->subMonthNoOverflow()->format('Y-m'),
			'nextMonthParam' => $targetMonth->copy()->addMonthNoOverflow()->format('Y-m'),
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
