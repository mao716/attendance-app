<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceBreak;
use App\Models\StampCorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StampCorrectionRequestController extends Controller
{
	public function index(): View
	{
		$pendingRequests = StampCorrectionRequest::query()
			->with(['attendance.user'])
			->where('status', StampCorrectionRequest::STATUS_PENDING)
			->orderByDesc('created_at')
			->get();

		$approvedRequests = StampCorrectionRequest::query()
			->with(['attendance.user'])
			->where('status', StampCorrectionRequest::STATUS_APPROVED)
			->orderByDesc('created_at')
			->get();

		return view(
			'admin.stamp_correction_request.list',
			compact('pendingRequests', 'approvedRequests')
		);
	}

	/**
	 * 申請詳細（承認ボタン付き）
	 */
	public function show(StampCorrectionRequest $request): View
	{
		$stampCorrectionRequest = $request;

		$stampCorrectionRequest->load([
			'attendance.user',
			'correctionBreaks' => function ($query) {
				$query->orderBy('break_order');
			},
		]);

		if (! $stampCorrectionRequest->attendance) {
			abort(404);
		}

		$isApproved = $stampCorrectionRequest->status === StampCorrectionRequest::STATUS_APPROVED;

		return view('admin.stamp_correction_request.show', compact('stampCorrectionRequest', 'isApproved'));
	}

	/**
	 * 承認処理
	 */
	public function approve(StampCorrectionRequest $request): RedirectResponse
	{
		$stampCorrectionRequest = $request;

		if ($stampCorrectionRequest->status !== StampCorrectionRequest::STATUS_PENDING) {
			return back()->withErrors(['request' => 'この申請は承認待ちではありません。']);
		}

		DB::transaction(function () use ($stampCorrectionRequest) {
			$attendance = $stampCorrectionRequest->attendance()->firstOrFail();

			// ① 勤怠（出退勤）を after_* で更新（休憩合計は後で再計算するのでここでは触らない）
			$attendance->clock_in_at  = $stampCorrectionRequest->after_clock_in_at;
			$attendance->clock_out_at = $stampCorrectionRequest->after_clock_out_at;
			$attendance->save();

			// ② 休憩明細を作り直す（申請の correctionBreaks を反映）
			AttendanceBreak::where('attendance_id', $attendance->id)->delete();

			$breaks = $stampCorrectionRequest->correctionBreaks()
				->orderBy('break_order')
				->get();

			foreach ($breaks as $break) {
				AttendanceBreak::create([
					'attendance_id'  => $attendance->id,
					'break_start_at' => $break->break_start_at,
					'break_end_at'   => $break->break_end_at,
				]);
			}

			// ③ 合計休憩を breaks から再計算（正＝明細）
			$totalBreakMinutes = AttendanceBreak::where('attendance_id', $attendance->id)
				->get()
				->sum(fn($b) => $b->break_start_at->diffInMinutes($b->break_end_at));

			$attendance->total_break_minutes = (int) $totalBreakMinutes;

			// ④ 実働も再計算
			if ($attendance->clock_in_at && $attendance->clock_out_at) {
				$working = $attendance->clock_in_at->diffInMinutes($attendance->clock_out_at)
					- $attendance->total_break_minutes;

				$attendance->working_minutes = max(0, (int) $working);
			} else {
				$attendance->working_minutes = 0;
			}

			$attendance->save();

			// ⑤ 申請を承認済みに
			$stampCorrectionRequest->status = StampCorrectionRequest::STATUS_APPROVED;
			$stampCorrectionRequest->approved_at = now();
			$stampCorrectionRequest->save();
		});

		return redirect()->route('admin.stamp_correction_request.index');
	}
}
