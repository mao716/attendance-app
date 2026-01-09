<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffAttendanceController extends Controller
{
	public function index(Request $request, int $id): View
	{
		$user = User::query()->findOrFail($id);

		$targetMonth = $this->resolveTargetMonth($request->query('month'));
		$monthStart = $targetMonth->copy()->startOfMonth();
		$monthEnd = $targetMonth->copy()->endOfMonth();

		$prevMonthParam = $targetMonth->copy()->subMonthNoOverflow()->format('Y-m');
		$nextMonthParam = $targetMonth->copy()->addMonthNoOverflow()->format('Y-m');

		$attendancesByDate = Attendance::query()
			->where('user_id', $user->id)
			->whereBetween('work_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
			->get()
			->keyBy(function (Attendance $attendance) {
				return Carbon::parse($attendance->work_date)->toDateString();
			});

		$rows = [];
		$cursor = $monthStart->copy();
		while ($cursor->lte($monthEnd)) {
			$workDate = $cursor->toDateString();
			$attendance = $attendancesByDate->get($workDate);

			$rows[] = [
				'date_label' => $this->formatMonthDayWithWeekday($cursor),
				'clock_in' => $attendance?->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : '',
				'clock_out' => $attendance?->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : '',
				'break_minutes' => $attendance?->total_break_minutes,
				'work_minutes' => $attendance?->working_minutes,
				'attendance_id' => $attendance?->id,
			];

			$cursor->addDay();
		}

		return view('admin.attendance.staff', [
			'user' => $user,
			'targetMonth' => $targetMonth,
			'prevMonthParam' => $prevMonthParam,
			'nextMonthParam' => $nextMonthParam,
			'rows' => $rows,
		]);
	}

	private function resolveTargetMonth(?string $monthParam): Carbon
	{
		try {
			return $monthParam
				? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
				: Carbon::today()->startOfMonth();
		} catch (\Throwable) {
			return Carbon::today()->startOfMonth();
		}
	}

	private const WEEKDAYS = ['月', '火', '水', '木', '金', '土', '日'];
	private const DAY_OF_WEEK_ISO_OFFSET = 1;

	private function formatMonthDayWithWeekday(Carbon $date): string
	{
		$weekdayIndex = $date->dayOfWeekIso - self::DAY_OF_WEEK_ISO_OFFSET;
		$weekday = self::WEEKDAYS[$weekdayIndex];

		return $date->format('m/d') . "({$weekday})";
	}
}
