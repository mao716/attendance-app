<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class AttendanceController extends Controller
{
	/**
	 * 打刻画面表示
	 */
	public function index(): View
	{
		$user = Auth::user();
		$today = Carbon::today();
		$now = Carbon::now();

		$attendance = Attendance::with('breaks')
			->where('user_id', $user->id)
			->where('work_date', $today->toDateString())
			->first();

		// ステータス関連
		$status = $attendance?->status ?? Attendance::STATUS_OFF;
		$statusLabel = $attendance?->status_label ?? '勤務外';

		// 日付・時刻の表示用
		$todayLabel = $this->formatJapaneseDate($today); // 2023年6月1日(木) みたいな書式
		$timeLabel  = $now->format('H:i');               // 08:00

		return view('attendance.index', [
			'todayLabel'   => $todayLabel,
			'timeLabel'    => $timeLabel,
			'status'       => $status,
			'statusLabel'  => $statusLabel,
			'attendance'   => $attendance,
		]);
	}

	// 追記：日付フォーマット用メソッド
	private function formatJapaneseDate(Carbon $date): string
	{
		$weekdays = ['月', '火', '水', '木', '金', '土', '日'];
		$weekday = $weekdays[$date->dayOfWeekIso - 1];

		return $date->format("Y年n月j日") . "({$weekday})";
	}


	/**
	 * 出勤
	 */
	public function clockIn(): RedirectResponse
	{
		$user = Auth::user();
		$today = Carbon::today();
		$now = Carbon::now();

		$attendance = Attendance::where('user_id', $user->id)
			->where('work_date', $today->toDateString())
			->first();

		// すでに出勤済 or 休憩中 or 退勤済 → 何もしない
		if ($attendance && ! $attendance->isNotStarted()) {
			return redirect()->route('attendance.index');
		}

		// 新規 or 未出勤データを作成
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


	/**
	 * 休憩開始
	 */
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

		// 休憩レコード作成
		AttendanceBreak::create([
			'attendance_id' => $attendance->id,
			'break_start_at' => $now,
		]);

		$attendance->status = Attendance::STATUS_BREAK;
		$attendance->save();

		return redirect()->route('attendance.index');
	}



	/**
	 * 休憩終了
	 */
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

		// 未終了の休憩を取得
		$break = $attendance->breaks
			->whereNull('break_end_at')
			->sortByDesc('break_start_at')
			->first();

		if (! $break) {
			// 想定外：とりあえず勤務中に戻す
			$attendance->status = Attendance::STATUS_WORKING;
			$attendance->save();
			return redirect()->route('attendance.index');
		}

		// 終了時刻を記録
		$break->break_end_at = $now;
		$break->save();

		// 合計休憩時間を再計算
		$totalBreak = 0;
		foreach ($attendance->breaks as $b) {
			if ($b->break_start_at && $b->break_end_at) {
				$totalBreak += Carbon::parse($b->break_start_at)
					->diffInMinutes(Carbon::parse($b->break_end_at));
			}
		}

		$attendance->total_break_minutes = $totalBreak;
		$attendance->status = Attendance::STATUS_WORKING;
		$attendance->save();

		return redirect()->route('attendance.index');
	}


	/**
	 * 退勤
	 */
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

		// ⚠︎ 再度休憩集計しておく（念のため）
		$totalBreak = 0;
		foreach ($attendance->breaks as $b) {
			if ($b->break_start_at && $b->break_end_at) {
				$totalBreak += Carbon::parse($b->break_start_at)
					->diffInMinutes(Carbon::parse($b->break_end_at));
			}
		}
		$attendance->total_break_minutes = $totalBreak;

		// 勤務時間（出勤〜退勤 − 休憩）
		if ($attendance->clock_in_at) {
			$attendance->working_minutes =
				Carbon::parse($attendance->clock_in_at)
				->diffInMinutes($now)
				- $totalBreak;
		}

		$attendance->save();

		return redirect()->route('attendance.index');
	}
}
