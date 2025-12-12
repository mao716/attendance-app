<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceBreak;
use App\Models\StampCorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StampCorrectionRequestController extends Controller
{
	/**
	 * 承認待ち一覧
	 */
	public function index(): View
	{
		$requests = StampCorrectionRequest::query()
			->with(['attendance.user'])
			->where('status', StampCorrectionRequest::STATUS_PENDING)
			->orderByDesc('created_at')
			->get();

		return view('admin.stamp_correction_request.list', compact('requests'));
	}

	/**
	 * 申請詳細（承認ボタン付き）
	 */
	public function show(StampCorrectionRequest $stampCorrectionRequest): View
	{
		$stampCorrectionRequest->load([
			'attendance.user',
			'correctionBreaks' => fn($q) => $q->orderBy('break_order'),
		]);

		return view(
			'admin.stamp_correction_request.show',
			compact('stampCorrectionRequest')
		);
	}

	/**
	 * 承認処理
	 * - stamp_correction_requests: status=APPROVED, approved_at を入れる
	 * - attendances: clock_in/out, total_break_minutes, working_minutes を after_* で更新
	 * - attendance_breaks: いったん全削除→修正後休憩で作り直す（おすすめ）
	 */
	public function approve(StampCorrectionRequest $stampCorrectionRequest): RedirectResponse
	{
		if ($stampCorrectionRequest->status !== StampCorrectionRequest::STATUS_PENDING) {
			return back()->withErrors(['request' => 'この申請は承認待ちではありません。']);
		}

		$attendance = $stampCorrectionRequest->attendance()->with('breaks')->firstOrFail();

		// ① 勤怠（attendances）を after_* で更新
		$attendance->clock_in_at = $stampCorrectionRequest->after_clock_in_at;
		$attendance->clock_out_at = $stampCorrectionRequest->after_clock_out_at;
		$attendance->total_break_minutes = $stampCorrectionRequest->after_break_minutes;

		// working_minutes = (出勤〜退勤) - 休憩
		if ($attendance->clock_in_at && $attendance->clock_out_at) {
			$attendance->working_minutes =
				$attendance->clock_in_at->diffInMinutes($attendance->clock_out_at)
				- (int) $attendance->total_break_minutes;
		}

		$attendance->save();

		// ② 休憩（attendance_breaks）を作り直す
		//    ※「承認後の勤怠は最新状態だけ持てばOK」ならこれが一番シンプルで事故りにくい
		AttendanceBreak::where('attendance_id', $attendance->id)->delete();

		$breaks = $stampCorrectionRequest->correctionBreaks()
			->orderBy('break_order')
			->get();

		foreach ($breaks as $b) {
			AttendanceBreak::create([
				'attendance_id'   => $attendance->id,
				'break_start_at'  => $b->break_start_at,
				'break_end_at'    => $b->break_end_at,
			]);
		}

		// ③ 申請を承認済みに
		$stampCorrectionRequest->status = StampCorrectionRequest::STATUS_APPROVED;
		$stampCorrectionRequest->approved_at = now();
		$stampCorrectionRequest->save();

		return redirect()->route('admin.stamp_corrections.index')
			->with('status', '承認しました。');
	}
}
