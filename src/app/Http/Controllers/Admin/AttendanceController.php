<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
	public function index(Request $request): View
	{
		$targetDate = $this->resolveTargetDate($request->query('date'));

		$attendancesByUserId = Attendance::query()
			->with('user')
			->where('work_date', $targetDate->toDateString())
			->get()
			->keyBy('user_id');

		$users = User::query()
			->where('role', '!=', User::ROLE_ADMIN)
			->orderBy('id')
			->get();

		$rows = $users->map(function (User $user) use ($attendancesByUserId) {
			return [
				'user' => $user,
				'attendance' => $attendancesByUserId->get($user->id),
			];
		});

		return view('admin.attendance.list', [
			'targetDate' => $targetDate,
			'targetDateLabel' => $targetDate->format('Y年n月j日'),
			'rows' => $rows,
		]);
	}

	private function resolveTargetDate(?string $dateParam): Carbon
	{
		try {
			return $dateParam
				? Carbon::createFromFormat('Y-m-d', $dateParam)->startOfDay()
				: Carbon::today();
		} catch (\Throwable) {
			return Carbon::today();
		}
	}

	public function show(Request $request, int $id): View
	{
		$fromRequest = $request->boolean('from_request');
		$requestId   = $request->integer('request_id');

		$attendance = Attendance::query()
			->with([
				'user',
				'breaks' => fn($query) => $query->orderBy('break_start_at'),
			])
			->findOrFail($id);

		$user = $attendance->user;

		$targetRequest = null;

		if ($fromRequest) {
			if (!$requestId) {
				abort(404);
			}

			$targetRequest = StampCorrectionRequest::query()
				->where('id', $requestId)
				->where('attendance_id', $attendance->id)
				->with(['correctionBreaks' => fn($query) => $query->orderBy('break_order')])
				->firstOrFail();
		} else {
			$targetRequest = StampCorrectionRequest::query()
				->where('attendance_id', $attendance->id)
				->with(['correctionBreaks' => fn($query) => $query->orderBy('break_order')])
				->latest()
				->first();
		}

		$isPending = $targetRequest
			&& $targetRequest->status === StampCorrectionRequest::STATUS_PENDING;

		$useAfterData = false;
		$isEditable = true;
		$requestStatus = null;

		if ($fromRequest) {
			$isEditable    = false;
			$useAfterData  = true;
			$requestStatus = $isPending ? 'pending' : 'approved';
		} else {
			if ($isPending) {
				$isEditable    = false;
				$useAfterData  = false;
				$requestStatus = 'pending';
			} else {
				$isEditable    = true;
				$useAfterData  = false;
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

		$breakRows = [];

		if ($useAfterData && $targetRequest && $targetRequest->correctionBreaks->isNotEmpty()) {
			$breakRows = $targetRequest->correctionBreaks->map(fn($correctionBreak) => [
				'start' => optional($correctionBreak->break_start_at)->format('H:i'),
				'end'   => optional($correctionBreak->break_end_at)->format('H:i'),
			])->values()->toArray();
		} else {
			$breakRows = $attendance->breaks->map(fn($attendanceBreak) => [
				'start' => optional($attendanceBreak->break_start_at)->format('H:i'),
				'end'   => optional($attendanceBreak->break_end_at)->format('H:i'),
			])->values()->toArray();
		}

		if ($isEditable) {
			$targetRowCount = count($breakRows) + 1;
			for ($breakIndex = count($breakRows); $breakIndex < $targetRowCount; $breakIndex++) {
				$breakRows[] = ['start' => null, 'end' => null];
			}
		}

		$noteForDisplay = null;
		$noteForForm    = null;

		if ($fromRequest && $targetRequest) {
			$noteForDisplay = $targetRequest->reason;
		} else {
			$noteForDisplay = $attendance->note;

			if ($isEditable) {
				$noteForForm = $attendance->note;
			}
		}

		return view('admin.attendance.detail', [
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

	public function update(AdminAttendanceUpdateRequest $request, int $id): RedirectResponse
	{
		$attendance = Attendance::query()->findOrFail($id);

		$hasPending = StampCorrectionRequest::query()
			->where('attendance_id', $attendance->id)
			->where('status', StampCorrectionRequest::STATUS_PENDING)
			->exists();

		if ($hasPending) {
			return back()->withErrors([
				'attendance_update_error' => '※承認待ちのため修正はできません。',
			]);
		}

		$workDate = $attendance->work_date instanceof Carbon
			? $attendance->work_date->toDateString()
			: Carbon::parse($attendance->work_date)->toDateString();

		$clockInAt = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $request->input('clock_in_at'));
		$clockOutAt = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $request->input('clock_out_at'));

		$inputBreaks = $request->input('breaks', []);
		$normalizedBreaks = [];

		if (is_array($inputBreaks)) {
			foreach ($inputBreaks as $breakInput) {
				$start = $breakInput['start'] ?? null;
				$end = $breakInput['end'] ?? null;

				if (!$start && !$end) {
					continue;
				}

				$normalizedBreaks[] = [
					'start' => Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $start),
					'end' => Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $end),
				];
			}
		}

		DB::transaction(function () use ($attendance, $clockInAt, $clockOutAt, $normalizedBreaks, $request) {
			$attendance->clock_in_at = $clockInAt;
			$attendance->clock_out_at = $clockOutAt;

			$attendance->note = $request->input('reason');

			AttendanceBreak::query()
				->where('attendance_id', $attendance->id)
				->delete();

			$totalBreakMinutes = 0;

			foreach ($normalizedBreaks as $normalizedBreak) {
				AttendanceBreak::create([
					'attendance_id' => $attendance->id,
					'break_start_at' => $normalizedBreak['start'],
					'break_end_at' => $normalizedBreak['end'],
				]);

				$totalBreakMinutes += $normalizedBreak['start']->diffInMinutes($normalizedBreak['end']);
			}

			$attendance->total_break_minutes = $totalBreakMinutes;

			$attendance->working_minutes = $clockInAt->diffInMinutes($clockOutAt) - $totalBreakMinutes;

			$attendance->status = Attendance::STATUS_FINISHED;

			$attendance->save();
		});

		return redirect()
			->route('admin.attendance.detail', ['id' => $attendance->id])
			->with('success', '勤怠を更新しました。');
	}
}
