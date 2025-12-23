<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StampCorrectionRequest;
use App\Models\AttendanceBreak;
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
			->orderByDesc('approved_at')
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
			'attendance.breaks' => fn($builder) => $builder->orderBy('break_start_at'),
			'correctionBreaks'  => fn($builder) => $builder->orderBy('break_order'),
		]);

		$attendance = $stampCorrectionRequest->attendance;

		if (! $stampCorrectionRequest->attendance) {
			abort(404);
		}

		$breakSource = $stampCorrectionRequest->correctionBreaks->isNotEmpty()
			? $stampCorrectionRequest->correctionBreaks
			: ($attendance?->breaks ?? collect());

		$breakRows = collect($breakSource)->map(function ($break) {
			return [
				'start' => optional($break->break_start_at)->format('H:i'),
				'end'   => optional($break->break_end_at)->format('H:i'),
			];
		})->values()->toArray();

		return view('admin.stamp_correction_request.show', compact(
			'stampCorrectionRequest',
			'breakRows'
		));
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

			$stampCorrectionRequest->load([
				'attendance.breaks' => function ($builder) {
					$builder->orderBy('break_start_at');
				},
				'correctionBreaks' => function ($builder) {
					$builder->orderBy('break_order');
				},
			]);

			$attendance = $stampCorrectionRequest->attendance;

			if (! $attendance) {
				abort(404);
			}

			// ② 休憩は「申請に明細があるときだけ」作り直す ＋ 分も再計算
			$hasCorrectionBreaks = $stampCorrectionRequest->correctionBreaks->isNotEmpty();

			if ($hasCorrectionBreaks) {
				$recalculatedBreakMinutes = 0;

				foreach ($stampCorrectionRequest->correctionBreaks as $correctionBreak) {
					$recalculatedBreakMinutes += $correctionBreak->break_start_at
						->diffInMinutes($correctionBreak->break_end_at);
				}

				// 明細合計で上書き（ズレ防止）
				$stampCorrectionRequest->after_break_minutes = $recalculatedBreakMinutes;
				$attendance->total_break_minutes = $recalculatedBreakMinutes;

				// attendance_breaks を作り直す
				AttendanceBreak::where('attendance_id', $attendance->id)->delete();

				foreach ($stampCorrectionRequest->correctionBreaks as $correctionBreak) {
					AttendanceBreak::create([
						'attendance_id'  => $attendance->id,
						'break_start_at' => $correctionBreak->break_start_at,
						'break_end_at'   => $correctionBreak->break_end_at,
					]);
				}
			} else {
				// 明細がない申請は「休憩は現状維持」
				$attendance->total_break_minutes = (int) $stampCorrectionRequest->after_break_minutes;
			}

			// ① attendances を after_* で更新（※休憩分が確定した後にやる）
			$attendance->clock_in_at = $stampCorrectionRequest->after_clock_in_at;
			$attendance->clock_out_at = $stampCorrectionRequest->after_clock_out_at;

			if ($attendance->clock_in_at && $attendance->clock_out_at) {
				$attendance->working_minutes =
					$attendance->clock_in_at->diffInMinutes($attendance->clock_out_at)
					- (int) $attendance->total_break_minutes;
			}

			$attendance->save();

			// ③ 申請を承認済みに
			$stampCorrectionRequest->status = StampCorrectionRequest::STATUS_APPROVED;
			$stampCorrectionRequest->approved_at = now();
			$stampCorrectionRequest->save();
		});

		return redirect()
			->route('admin.stamp_correction_request.index');
	}
}
