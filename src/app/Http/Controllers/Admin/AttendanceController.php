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
	/**
	 * 勤怠一覧（管理者）: 日次勤怠一覧
	 */
	public function index(Request $request): View
	{
		$targetDate = $this->resolveTargetDate($request->query('date'));

		$attendancesByUserId = Attendance::query()
			->with('user')
			->where('work_date', $targetDate->toDateString())
			->get()
			->keyBy('user_id');

		$users = User::query()
			->where('role', '!=', 2)
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
		} catch (\Throwable $exception) {
			return Carbon::today();
		}
	}

	/**
	 * 勤怠詳細（管理者）
	 */
	public function show(Request $request, Attendance $attendance): View
	{
		// 関連ロード（休憩は表示に必須）
		$attendance->load([
			'user',
			'breaks' => fn($q) => $q->orderBy('break_start_at'),
		]);

		$user = $attendance->user;

		// 最新申請（pending かどうか判定に使う）
		$latestRequest = StampCorrectionRequest::query()
			->where('attendance_id', $attendance->id)
			->latest()
			->first();

		$isPending = $latestRequest
			&& $latestRequest->status === StampCorrectionRequest::STATUS_PENDING;

		/*
		|------------------------------------------
		| 表示モード決定（Admin）
		|------------------------------------------
		| - pending があれば after を表示（閲覧専用）
		| - それ以外は attendance を表示（編集OK）
		*/
		$useAfterData  = $isPending;
		$isEditable    = ! $isPending;
		$requestStatus = $isPending ? 'pending' : null; // bladeの表示制御用

		/*
		|------------------------------------------
		| 出勤・退勤（表示）
		|------------------------------------------
		*/
		$displayClockInAt  = $attendance->clock_in_at;
		$displayClockOutAt = $attendance->clock_out_at;

		if ($useAfterData && $latestRequest) {
			$displayClockInAt  = $latestRequest->after_clock_in_at;
			$displayClockOutAt = $latestRequest->after_clock_out_at;
		}

		$clockInTime  = optional($displayClockInAt)->format('H:i');
		$clockOutTime = optional($displayClockOutAt)->format('H:i');

		/*
		|------------------------------------------
		| 休憩（表示）
		|------------------------------------------
		| - after表示モードなら stamp_correction_breaks を優先
		| - それ以外は attendance_breaks
		*/
		$breakRows = [];

		if ($useAfterData && $latestRequest) {
			$latestRequest->load([
				'correctionBreaks' => fn($q) => $q->orderBy('break_order'),
			]);

			if ($latestRequest->correctionBreaks->isNotEmpty()) {
				$breakRows = $latestRequest->correctionBreaks->map(fn($b) => [
					'start' => optional($b->break_start_at)->format('H:i'),
					'end'   => optional($b->break_end_at)->format('H:i'),
				])->values()->toArray();
			}
		}

		if (empty($breakRows)) {
			$breakRows = $attendance->breaks->map(fn($b) => [
				'start' => optional($b->break_start_at)->format('H:i'),
				'end'   => optional($b->break_end_at)->format('H:i'),
			])->values()->toArray();
		}

		// 編集可能なら「休憩枠 + 1枠」を確保（休憩0なら空欄1枠だけになる）
		if ($isEditable) {
			$targetRowCount = count($breakRows) + 1;

			for ($i = count($breakRows); $i < $targetRowCount; $i++) {
				$breakRows[] = ['start' => null, 'end' => null];
			}
		}

		/*
		|------------------------------------------
		| 備考（表示/フォーム初期値）
		|------------------------------------------
		| - pending：pending の reason を表示（閲覧用）
		| - editable：フォーム初期値は「最新 approved の reason」（User側と同じ仕様）
		*/
		$noteForDisplay = null;
		$noteForForm    = null;

		if ($isPending && $latestRequest) {
			$noteForDisplay = $latestRequest->reason;
		}

		if ($isEditable) {
			$latestApproved = StampCorrectionRequest::query()
				->where('attendance_id', $attendance->id)
				->where('status', StampCorrectionRequest::STATUS_APPROVED)
				->orderByDesc('approved_at')
				->orderByDesc('created_at')
				->first();

			$noteForForm = $latestApproved?->reason;
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
		]);
	}

	/**
	 * 勤怠更新（管理者）
	 */
	public function update(AdminAttendanceUpdateRequest $request, Attendance $attendance): RedirectResponse
	{
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

		// 休憩入力を整形（両方空は捨てる）
		$inputBreaks = $request->input('breaks', []);
		$normalizedBreaks = [];

		if (is_array($inputBreaks)) {
			foreach ($inputBreaks as $b) {
				$start = $b['start'] ?? null;
				$end = $b['end'] ?? null;

				if (!$start && !$end) {
					continue;
				}

				$normalizedBreaks[] = [
					'start' => Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $start),
					'end' => Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $end),
				];
			}
		}

		DB::transaction(function () use ($attendance, $clockInAt, $clockOutAt, $normalizedBreaks) {
			// attendances 更新
			$attendance->clock_in_at = $clockInAt;
			$attendance->clock_out_at = $clockOutAt;

			// breaks 作り直し
			AttendanceBreak::query()
				->where('attendance_id', $attendance->id)
				->delete();

			$totalBreakMinutes = 0;

			foreach ($normalizedBreaks as $b) {
				AttendanceBreak::create([
					'attendance_id' => $attendance->id,
					'break_start_at' => $b['start'],
					'break_end_at' => $b['end'],
				]);

				$totalBreakMinutes += $b['start']->diffInMinutes($b['end']);
			}

			$attendance->total_break_minutes = $totalBreakMinutes;

			// 勤務時間（出勤〜退勤 − 休憩）
			$attendance->working_minutes = $clockInAt->diffInMinutes($clockOutAt) - $totalBreakMinutes;

			// status（退勤があるなら FINISHED）
			$attendance->status = Attendance::STATUS_FINISHED;

			$attendance->save();

			$latestApproved = StampCorrectionRequest::query()
				->where('attendance_id', $attendance->id)
				->where('status', StampCorrectionRequest::STATUS_APPROVED)
				->orderByDesc('approved_at')
				->orderByDesc('created_at')
				->first();

			if ($latestApproved) {
				$latestApproved->reason = $request->input('reason'); // nullでもOKならそのまま
				$latestApproved->save();
			}
		});

		return redirect()
			->route('admin.attendance.detail', ['attendance' => $attendance->id])
			->with('success', '勤怠を更新しました。');
	}
}
