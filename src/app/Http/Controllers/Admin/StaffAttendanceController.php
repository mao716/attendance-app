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
	/**
	 * スタッフ別 勤怠一覧（月次）
	 * /admin/attendance/staff/{user}?month=YYYY-MM
	 */
	public function index(Request $request, User $user): View
	{
		$targetMonth = $this->resolveTargetMonth($request->query('month'));
		$monthStart = $targetMonth->copy()->startOfMonth();
		$monthEnd = $targetMonth->copy()->endOfMonth();

		// 前月/翌月パラメータ
		$prevMonthParam = $targetMonth->copy()->subMonthNoOverflow()->format('Y-m');
		$nextMonthParam = $targetMonth->copy()->addMonthNoOverflow()->format('Y-m');

		// 対象月の勤怠をまとめて取得（N+1回避）
		$attendancesByDate = Attendance::query()
			->where('user_id', $user->id)
			->whereBetween('work_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
			->get()
			->keyBy(function (Attendance $attendance) {
				return Carbon::parse($attendance->work_date)->toDateString();
			});

		// 全日付分の行を作る（勤怠がない日は空欄）
		$rows = [];
		$cursor = $monthStart->copy();
		while ($cursor->lte($monthEnd)) {
			$workDate = $cursor->toDateString();
			$attendance = $attendancesByDate->get($workDate);

			$rows[] = [
				'date_label' => $this->formatMonthDayWithWeekday($cursor), // 06/01(木)
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
		} catch (\Throwable $exception) {
			return Carbon::today()->startOfMonth();
		}
	}

	private function formatMonthDayWithWeekday(Carbon $date): string
	{
		$weekdays = ['月', '火', '水', '木', '金', '土', '日'];
		$weekday = $weekdays[$date->dayOfWeekIso - 1];

		return $date->format('m/d') . "({$weekday})";
	}
}
