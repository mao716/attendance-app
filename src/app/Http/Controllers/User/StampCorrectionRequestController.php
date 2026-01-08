<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Carbon\Carbon;


class StampCorrectionRequestController extends Controller
{
	private const FIRST_BREAK_ORDER = 1;

	public function indexForUser(): View
	{
		$userId = Auth::id();

		$pendingRequests = StampCorrectionRequest::query()
			->with(['attendance.user', 'user'])
			->where('user_id', $userId)
			->where('status', StampCorrectionRequest::STATUS_PENDING)
			->orderByDesc('created_at')
			->get();

		$approvedRequests = StampCorrectionRequest::query()
			->with(['attendance.user', 'user'])
			->where('user_id', $userId)
			->where('status', StampCorrectionRequest::STATUS_APPROVED)
			->orderByDesc('approved_at')
			->orderByDesc('created_at')
			->get();

		return view('stamp_correction_request.list', [
			'pendingRequests'  => $pendingRequests,
			'approvedRequests' => $approvedRequests,
		]);
	}

	public function store(AttendanceCorrectionRequest $request, Attendance $attendance): RedirectResponse
	{
		$user = Auth::user();

		if (! $user || $attendance->user_id !== $user->id) {
			abort(403);
		}

		$validated = $request->validated();

		$attendance->load([
			'breaks' => fn($query) => $query->orderBy('break_start_at'),
		]);

		$workDate = $attendance->work_date->toDateString();

		$beforeClockInAt = $attendance->clock_in_at;
		$beforeClockOutAt = $attendance->clock_out_at;
		$beforeBreakMinutes = (int) $attendance->total_break_minutes;

		$clockInStr = $validated['clock_in_at']  ?? null;
		$clockOutStr = $validated['clock_out_at'] ?? null;

		if ($clockInStr && $clockOutStr) {
			$afterClockInAt = Carbon::parse("$workDate $clockInStr");
			$afterClockOutAt = Carbon::parse("$workDate $clockOutStr");
		} else {
			$afterClockInAt = $beforeClockInAt;
			$afterClockOutAt = $beforeClockOutAt;
		}

		$breakInputs = $validated['breaks'] ?? [];

		$normalizedBreaks = [];
		$afterBreakMinutes = 0;
		$hasAnyBreakTouched = false;

		foreach ($breakInputs as $breakInput) {
			$startStr = $breakInput['start'] ?? null;
			$endStr = $breakInput['end'] ?? null;

			if (!empty($startStr) || !empty($endStr)) {
				$hasAnyBreakTouched = true;
			}

			if (empty($startStr) && empty($endStr)) {
				continue;
			}

			if (empty($startStr) || empty($endStr)) {
				continue;
			}

			$startAt = Carbon::parse("$workDate $startStr");
			$endAt = Carbon::parse("$workDate $endStr");

			$afterBreakMinutes += $startAt->diffInMinutes($endAt);

			$normalizedBreaks[] = [
				'start_at' => $startAt,
				'end_at'   => $endAt,
			];
		}

		if (! $hasAnyBreakTouched) {
			$afterBreakMinutes = $beforeBreakMinutes;

			$normalizedBreaks = $attendance->breaks->map(fn($break) => [
				'start_at' => $break->break_start_at,
				'end_at'   => $break->break_end_at,
			])->values()->toArray();
		}

		$latestRequest = $attendance->stampCorrectionRequests()->latest()->first();

		if ($latestRequest && $latestRequest->status === StampCorrectionRequest::STATUS_PENDING) {
			return back()
				->withErrors(['request' => '承認待ちの修正申請が既に存在します。'])
				->withInput();
		}

		$requestRecord = StampCorrectionRequest::create([
			'attendance_id'        => $attendance->id,
			'user_id'              => $user->id,
			'before_clock_in_at'   => $beforeClockInAt,
			'before_clock_out_at'  => $beforeClockOutAt,
			'before_break_minutes' => $beforeBreakMinutes,
			'after_clock_in_at'    => $afterClockInAt,
			'after_clock_out_at'   => $afterClockOutAt,
			'after_break_minutes'  => $afterBreakMinutes,
			'reason'               => $validated['reason'],
			'status'               => StampCorrectionRequest::STATUS_PENDING,
		]);

		if (! empty($normalizedBreaks)) {
			$breakOrder = self::FIRST_BREAK_ORDER;

			foreach ($normalizedBreaks as $break) {
				$requestRecord->correctionBreaks()->create([
					'break_order'    => $breakOrder++,
					'break_start_at' => $break['start_at'],
					'break_end_at'   => $break['end_at'],
				]);
			}
		}

		return redirect()->route('stamp_correction_request.user_index');
	}

	public function showForUser(StampCorrectionRequest $stampCorrectionRequest): View
	{
		$userId = Auth::id();

		if (! $userId || $stampCorrectionRequest->user_id !== $userId) {
			abort(403);
		}

		$stampCorrectionRequest->load([
			'attendance.breaks' => fn($builder) => $builder->orderBy('break_start_at'),
			'correctionBreaks'  => fn($builder) => $builder->orderBy('break_order'),
		]);

		$attendance = $stampCorrectionRequest->attendance;

		if (! $attendance) {
			abort(404);
		}

		$requestStatus = $stampCorrectionRequest->status === StampCorrectionRequest::STATUS_PENDING
			? 'pending'
			: 'approved';

		$isEditable = false;

		$clockInTime  = optional($stampCorrectionRequest->after_clock_in_at)->format('H:i');
		$clockOutTime = optional($stampCorrectionRequest->after_clock_out_at)->format('H:i');

		$breakSource = $stampCorrectionRequest->correctionBreaks->isNotEmpty()
			? $stampCorrectionRequest->correctionBreaks
			: $attendance->breaks;

		$breakRows = $breakSource->map(fn($break) => [
			'start' => optional($break->break_start_at)->format('H:i'),
			'end'   => optional($break->break_end_at)->format('H:i'),
		])->values()->toArray();

		$noteForDisplay = $stampCorrectionRequest->reason;
		$noteForForm = null;

		return view('attendance.detail', [
			'attendance'     => $attendance,
			'user'           => Auth::user(),
			'clockInTime'    => $clockInTime,
			'clockOutTime'   => $clockOutTime,
			'breakRows'      => $breakRows,
			'noteForDisplay' => $noteForDisplay,
			'noteForForm'    => $noteForForm,
			'requestStatus'  => $requestStatus,
			'isEditable'     => $isEditable,
		]);
	}
}
