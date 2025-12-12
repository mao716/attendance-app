<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
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
				: now()->startOfMonth();
		} catch (\Exception $e) {
			$targetMonth = now()->startOfMonth();
		}

		$year  = $targetMonth->year;
		$month = $targetMonth->month;

		// その月の勤怠（修正申請も一緒に）
		$attendances = Attendance::query()
			->where('user_id', Auth::id())
			->whereYear('work_date', $year)
			->whereMonth('work_date', $month)
			->with(['correctionRequests' => function ($q) {
				$q->orderByDesc('created_at');
			}])
			->orderBy('work_date')
			->get();

		// 日付で引けるようにする
		$attendancesByDate = $attendances->keyBy(fn($a) => $a->work_date->toDateString());

		$weekdays = ['日', '月', '火', '水', '木', '金', '土'];

		$rows = [];
		$daysInMonth = $targetMonth->daysInMonth;

		for ($i = 0; $i < $daysInMonth; $i++) {
			$date = $targetMonth->copy()->addDays($i);
			$dateKey = $date->toDateString();

			/** @var \App\Models\Attendance|null $attendance */
			$attendance = $attendancesByDate->get($dateKey);

			// pending優先 → なければapproved
			$requestId = null;
			if ($attendance) {
				$pending = $attendance->correctionRequests
					->firstWhere('status', StampCorrectionRequest::STATUS_PENDING);
				$approved = $attendance->correctionRequests
					->firstWhere('status', StampCorrectionRequest::STATUS_APPROVED);

				$requestId = ($pending ?? $approved)?->id;
			}

			$weekdayLabel = $weekdays[$date->dayOfWeek];
			$dateLabel = sprintf('%02d/%02d(%s)', $date->month, $date->day, $weekdayLabel);

			$rows[] = [
				'date_label'    => $dateLabel,
				'clock_in'      => $attendance?->clock_in_at?->format('H:i') ?? '',
				'clock_out'     => $attendance?->clock_out_at?->format('H:i') ?? '',
				'break_minutes' => $attendance?->total_break_minutes,
				'work_minutes'  => $attendance?->working_minutes,
				'attendance_id' => $attendance?->id,
				'request_id'    => $requestId, // ★これが「詳細」ボタン用
			];
		}

		$prevMonthParam = $targetMonth->copy()->subMonth()->format('Y-m');
		$nextMonthParam = $targetMonth->copy()->addMonth()->format('Y-m');

		return view('attendance.list', [
			'rows'           => $rows,
			'targetMonth'    => $targetMonth,
			'prevMonthParam' => $prevMonthParam,
			'nextMonthParam' => $nextMonthParam,
		]);
	}
}
