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

	public function show(int $attendanceCorrectRequestId): View
	{
		$stampCorrectionRequest = StampCorrectionRequest::query()
			->with([
				'attendance.user',
				'attendance.breaks' => fn($builder) => $builder->orderBy('break_start_at'),
				'correctionBreaks'  => fn($builder) => $builder->orderBy('break_order'),
			])
			->findOrFail($attendanceCorrectRequestId);

		$attendance = $stampCorrectionRequest->attendance;

		if (! $attendance) {
			abort(404);
		}

		$breakSource = $stampCorrectionRequest->correctionBreaks->isNotEmpty()
			? $stampCorrectionRequest->correctionBreaks
			: ($attendance->breaks ?? collect());

		$breakRows = $breakSource->map(fn($break) => [
			'start' => optional($break->break_start_at)->format('H:i'),
			'end'   => optional($break->break_end_at)->format('H:i'),
		])->values()->toArray();

		return view('admin.stamp_correction_request.show', compact('stampCorrectionRequest', 'breakRows'));
	}

	public function approve(int $attendanceCorrectRequestId): RedirectResponse
	{
		$stampCorrectionRequest = StampCorrectionRequest::query()->findOrFail($attendanceCorrectRequestId);

		if ($stampCorrectionRequest->status !== StampCorrectionRequest::STATUS_PENDING) {
			return back()->withErrors(['request' => 'この申請は承認待ちではありません。']);
		}

		DB::transaction(function () use ($stampCorrectionRequest) {
			$stampCorrectionRequest->load([
				'attendance.breaks' => fn($builder) => $builder->orderBy('break_start_at'),
				'correctionBreaks'  => fn($builder) => $builder->orderBy('break_order'),
			]);

			$attendance = $stampCorrectionRequest->attendance;

			if (! $attendance) {
				abort(404);
			}

			$hasCorrectionBreaks = $stampCorrectionRequest->correctionBreaks->isNotEmpty();

			if ($hasCorrectionBreaks) {
				$recalculatedBreakMinutes = 0;

				foreach ($stampCorrectionRequest->correctionBreaks as $correctionBreak) {
					$recalculatedBreakMinutes += $correctionBreak->break_start_at
						->diffInMinutes($correctionBreak->break_end_at);
				}

				$stampCorrectionRequest->after_break_minutes = $recalculatedBreakMinutes;
				$attendance->total_break_minutes = $recalculatedBreakMinutes;

				AttendanceBreak::where('attendance_id', $attendance->id)->delete();

				foreach ($stampCorrectionRequest->correctionBreaks as $correctionBreak) {
					AttendanceBreak::create([
						'attendance_id'  => $attendance->id,
						'break_start_at' => $correctionBreak->break_start_at,
						'break_end_at'   => $correctionBreak->break_end_at,
					]);
				}
			} else {
				$attendance->total_break_minutes = (int) $stampCorrectionRequest->after_break_minutes;
			}

			$attendance->clock_in_at = $stampCorrectionRequest->after_clock_in_at;
			$attendance->clock_out_at = $stampCorrectionRequest->after_clock_out_at;

			$attendance->note = $stampCorrectionRequest->reason;

			if ($attendance->clock_in_at && $attendance->clock_out_at) {
				$attendance->working_minutes =
					$attendance->clock_in_at->diffInMinutes($attendance->clock_out_at)
					- (int) $attendance->total_break_minutes;
			}

			$attendance->save();

			$stampCorrectionRequest->status = StampCorrectionRequest::STATUS_APPROVED;
			$stampCorrectionRequest->approved_at = now();
			$stampCorrectionRequest->save();
		});

		return redirect()->route('stamp_correction_request.list');
	}
}
