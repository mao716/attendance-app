<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceDetailController extends Controller
{
	public function show(Request $request, int $id): View
	{
		$user = Auth::user();

		$attendance = Attendance::query()
			->with(['breaks' => fn($query) => $query->orderBy('break_start_at')])
			->findOrFail($id);

		if (! $user || $attendance->user_id !== $user->id) {
			abort(403);
		}

		$fromRequest = $request->boolean('from_request');
		$requestId = $request->integer('request_id');

		$targetRequest = null;

		if ($fromRequest) {
			if (! $requestId) {
				abort(404);
			}

			$targetRequest = StampCorrectionRequest::query()
				->where('id', $requestId)
				->where('attendance_id', $attendance->id)
				->where('user_id', $user->id)
				->with(['correctionBreaks' => fn($query) => $query->orderBy('break_order')])
				->firstOrFail();
		} else {
			$targetRequest = StampCorrectionRequest::query()
				->where('attendance_id', $attendance->id)
				->where('user_id', $user->id)
				->with(['correctionBreaks' => fn($query) => $query->orderBy('break_order')])
				->latest()
				->first();
		}

		$isPending = $targetRequest && $targetRequest->status === StampCorrectionRequest::STATUS_PENDING;

		$useAfterData = false;
		$isEditable   = true;
		$requestStatus = null;

		if ($fromRequest) {
			$isEditable = false;
			$useAfterData = true;
			$requestStatus = $isPending ? 'pending' : 'approved';
		} else {
			if ($isPending) {
				$isEditable = false;
				$useAfterData = false;
				$requestStatus = 'pending';
			} else {
				$isEditable = true;
				$useAfterData = false;
				$requestStatus = null;
			}
		}

		$displayClockInAt  = $attendance->clock_in_at;
		$displayClockOutAt = $attendance->clock_out_at;

		if ($useAfterData && $targetRequest) {
			$displayClockInAt  = $targetRequest->after_clock_in_at;
			$displayClockOutAt = $targetRequest->after_clock_out_at;
		}

		$clockInTime  = optional($displayClockInAt)->format('H:i');
		$clockOutTime = optional($displayClockOutAt)->format('H:i');

		$breakSource = ($useAfterData && $targetRequest && $targetRequest->correctionBreaks->isNotEmpty())
			? $targetRequest->correctionBreaks
			: $attendance->breaks;

		$breakRows = $breakSource->map(fn($break) => [
			'start' => optional($break->break_start_at)->format('H:i'),
			'end'   => optional($break->break_end_at)->format('H:i'),
		])->values()->toArray();

		if ($isEditable) {
			$targetRowCount = count($breakRows) + 1;

			for ($index = count($breakRows); $index < $targetRowCount; $index++) {
				$breakRows[] = ['start' => null, 'end' => null];
			}
		}

		$noteForDisplay = null;
		$noteForForm    = null;

		if ($fromRequest && $targetRequest) {
			$noteForDisplay = $targetRequest->reason;
		} else {
			if ($isPending) {
				$noteForDisplay = null;
			}

			if ($isEditable) {
				$noteForForm = $attendance->note;
			}
		}

		return view('attendance.detail', [
			'attendance'     => $attendance,
			'user'           => $user,
			'clockInTime'    => $clockInTime,
			'clockOutTime'   => $clockOutTime,
			'breakRows'      => $breakRows,
			'noteForDisplay' => $noteForDisplay,
			'noteForForm'    => $noteForForm,
			'requestStatus'  => $requestStatus,
			'isEditable'     => $isEditable,
			'fromRequest'    => $fromRequest,
			'requestId'      => $requestId,
		]);
	}
}
