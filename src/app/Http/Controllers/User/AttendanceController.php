<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class AttendanceController extends Controller
{
	private const WEEKDAY_INDEX_OFFSET = 1;

	public function index(): View
	{
		$user = Auth::user();
		$today = Carbon::today();
		$now = Carbon::now();

		$attendance = Attendance::with('breaks')
			->where('user_id', $user->id)
			->where('work_date', $today->toDateString())
			->first();

		$status = $attendance?->status ?? Attendance::STATUS_OFF;
		$statusLabel = $attendance?->status_label ?? '勤務外';

		$todayLabel = $this->formatJapaneseDate($today);
		$timeLabel  = $now->format('H:i');

		return view('attendance.index', [
			'todayLabel'   => $todayLabel,
			'timeLabel'    => $timeLabel,
			'status'       => $status,
			'statusLabel'  => $statusLabel,
			'attendance'   => $attendance,
		]);
	}

	private function formatJapaneseDate(Carbon $date): string
	{
		$weekdays = ['月', '火', '水', '木', '金', '土', '日'];
		$weekday = $weekdays[$date->dayOfWeekIso - self::WEEKDAY_INDEX_OFFSET];

		return $date->format("Y年n月j日") . "({$weekday})";
	}

	public function clockIn(): RedirectResponse
	{
		$user = Auth::user();
		$today = Carbon::today();
		$now = Carbon::now();

		$attendance = Attendance::where('user_id', $user->id)
			->where('work_date', $today->toDateString())
			->first();

		if ($attendance && ! $attendance->isNotStarted()) {
			return redirect()->route('attendance.index');
		}

		if (! $attendance) {
			$attendance = new Attendance();
			$attendance->user_id = $user->id;
			$attendance->work_date = $today->toDateString();
			$attendance->total_break_minutes = 0;
			$attendance->working_minutes = 0;
		}

		$attendance->clock_in_at = $now;
		$attendance->status = Attendance::STATUS_WORKING;
		$attendance->save();

		return redirect()->route('attendance.index');
	}

	public function breakIn(): RedirectResponse
	{
		$user = Auth::user();
		$today = Carbon::today();
		$now = Carbon::now();

		$attendance = Attendance::where('user_id', $user->id)
			->where('work_date', $today->toDateString())
			->first();

		if (! $attendance || ! $attendance->isWorking()) {
			return redirect()->route('attendance.index');
		}

		AttendanceBreak::create([
			'attendance_id' => $attendance->id,
			'break_start_at' => $now,
		]);

		$attendance->status = Attendance::STATUS_BREAK;
		$attendance->save();

		return redirect()->route('attendance.index');
	}

	public function breakOut(): RedirectResponse
	{
		$user = Auth::user();
		$today = Carbon::today();
		$now = Carbon::now();

		$attendance = Attendance::with('breaks')
			->where('user_id', $user->id)
			->where('work_date', $today->toDateString())
			->first();

		if (! $attendance || ! $attendance->isOnBreak()) {
			return redirect()->route('attendance.index');
		}

		$break = $attendance->breaks
			->whereNull('break_end_at')
			->sortByDesc('break_start_at')
			->first();

		if (! $break) {
			$attendance->status = Attendance::STATUS_WORKING;
			$attendance->save();
			return redirect()->route('attendance.index');
		}

		$break->break_end_at = $now;
		$break->save();

		$attendance->load('breaks');

		$totalBreak = $this->calculateTotalBreakMinutes($attendance);

		$attendance->total_break_minutes = $totalBreak;
		$attendance->status = Attendance::STATUS_WORKING;
		$attendance->save();

		return redirect()->route('attendance.index');
	}

	public function clockOut(): RedirectResponse
	{
		$user = Auth::user();
		$today = Carbon::today();
		$now = Carbon::now();

		$attendance = Attendance::with('breaks')
			->where('user_id', $user->id)
			->where('work_date', $today->toDateString())
			->first();

		if (! $attendance || ! $attendance->isWorking() || $attendance->clock_out_at) {
			return redirect()->route('attendance.index');
		}

		$attendance->clock_out_at = $now;
		$attendance->status = Attendance::STATUS_FINISHED;

		$totalBreak = $this->calculateTotalBreakMinutes($attendance);

		$attendance->total_break_minutes = $totalBreak;

		if ($attendance->clock_in_at) {
			$attendance->working_minutes =
				Carbon::parse($attendance->clock_in_at)
				->diffInMinutes($now)
				- $totalBreak;
		}

		$attendance->save();

		return redirect()->route('attendance.index');
	}

	private function calculateTotalBreakMinutes(Attendance $attendance): int
	{
		$totalBreakMinutes = 0;

		foreach ($attendance->breaks as $break) {
			if ($break->break_start_at && $break->break_end_at) {
				$totalBreakMinutes += Carbon::parse($break->break_start_at)
					->diffInMinutes(Carbon::parse($break->break_end_at));
			}
		}

		return $totalBreakMinutes;
	}
}
