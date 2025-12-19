<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StampCorrectionRequestController extends Controller
{
	/**
	 * PG06 申請一覧（一般ユーザー）
	 * - 承認待ちタブ：自分の PENDING
	 * - 承認済みタブ：自分の APPROVED
	 */
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
			->orderByDesc('created_at')
			->get();

		return view('stamp_correction_request.list', [
			'pendingRequests'  => $pendingRequests,
			'approvedRequests' => $approvedRequests,
		]);
	}

	/**
	 * 勤怠詳細画面からの「修正」押下 → 修正申請を新規作成
	 */
	public function store(AttendanceCorrectionRequest $request, Attendance $attendance): RedirectResponse
	{
		// 自分以外の勤怠IDなら403
		if ($attendance->user_id !== Auth::id()) {
			abort(403);
		}

		$validated = $request->validated();

		// 勤務日
		$workDate = $attendance->work_date->format('Y-m-d');

		// ---- before（元の値） ----
		$beforeClockInAt    = $attendance->clock_in_at;
		$beforeClockOutAt   = $attendance->clock_out_at;
		$beforeBreakMinutes = (int) $attendance->total_break_minutes;

		// ---- after（出勤・退勤） ----
		$clockInStr  = $validated['clock_in_at']  ?? null;
		$clockOutStr = $validated['clock_out_at'] ?? null;

		if ($clockInStr && $clockOutStr) {
			$afterClockInAt  = Carbon::parse("$workDate $clockInStr");
			$afterClockOutAt = Carbon::parse("$workDate $clockOutStr");
		} else {
			$afterClockInAt  = $beforeClockInAt;
			$afterClockOutAt = $beforeClockOutAt;
		}

		// ---- after（休憩：入力を正規化して保存対象だけ作る） ----
		$breakInputs = $validated['breaks'] ?? [];

		$normalizedBreaks  = []; // 保存する休憩だけ
		$afterBreakMinutes = 0;

		foreach ($breakInputs as $breakInput) {
			$startStr = $breakInput['start'] ?? null;
			$endStr   = $breakInput['end'] ?? null;

			// 両方空（--:--～--:--）＝削除扱い：保存しない
			if (empty($startStr) && empty($endStr)) {
				continue;
			}

			// 片方だけ入力は FormRequest で弾く想定（保険）
			if (empty($startStr) || empty($endStr)) {
				continue;
			}

			$startAt = Carbon::parse("$workDate $startStr");
			$endAt   = Carbon::parse("$workDate $endStr");

			$afterBreakMinutes += $startAt->diffInMinutes($endAt);

			$normalizedBreaks[] = [
				'start_at' => $startAt,
				'end_at'   => $endAt,
			];
		}

		// 休憩を「一切入力してない」場合は、現状維持
		$hasAnyBreakTouched = collect($breakInputs)->contains(
			fn($breakInput) => !empty($breakInput['start']) || !empty($breakInput['end'])
		);

		if (! $hasAnyBreakTouched) {
			$afterBreakMinutes = $beforeBreakMinutes;
			$normalizedBreaks  = []; // 申請側の休憩明細は「なし」
		}

		// ---- 多重申請防止（pending がある時） ----
		$latestRequest = $attendance->correctionRequests()->latest()->first();

		if ($latestRequest && $latestRequest->status === StampCorrectionRequest::STATUS_PENDING) {
			return back()
				->withErrors(['request' => '承認待ちの修正申請が既に存在します。'])
				->withInput();
		}

		// ---- 修正申請（親）を作成 ----
		$requestRecord = StampCorrectionRequest::create([
			'attendance_id'        => $attendance->id,
			'user_id'              => Auth::id(),
			'before_clock_in_at'   => $beforeClockInAt,
			'before_clock_out_at'  => $beforeClockOutAt,
			'before_break_minutes' => $beforeBreakMinutes,
			'after_clock_in_at'    => $afterClockInAt,
			'after_clock_out_at'   => $afterClockOutAt,
			'after_break_minutes'  => $afterBreakMinutes,
			'reason'               => $validated['reason'],
			'status'               => StampCorrectionRequest::STATUS_PENDING,
		]);

		// ---- 修正後の休憩を子テーブルへ保存（保存対象だけ） ----
		if (! empty($normalizedBreaks)) {
			$order = 1;

			foreach ($normalizedBreaks as $break) {
				$requestRecord->correctionBreaks()->create([
					'break_order'    => $order++,
					'break_start_at' => $break['start_at'],
					'break_end_at'   => $break['end_at'],
				]);
			}
		}

		return redirect()->route('stamp_correction_request.user_index');
	}

	/**
	 * 申請一覧から「詳細」 → 勤怠詳細（閲覧専用）へ
	 */
	public function showForUser(StampCorrectionRequest $stampCorrectionRequest): View
	{
		// 本人以外は403
		if ($stampCorrectionRequest->user_id !== Auth::id()) {
			abort(403);
		}

		// 勤怠・休憩・修正休憩をロード
		$stampCorrectionRequest->load([
			'attendance.breaks' => fn($builder) => $builder->orderBy('break_start_at'),
			'correctionBreaks'  => fn($builder) => $builder->orderBy('break_order'),
		]);

		$attendance = $stampCorrectionRequest->attendance;

		// 変なデータ混入対策
		if (! $attendance) {
			abort(404);
		}

		// 申請一覧から来た詳細は「閲覧専用」
		$requestStatus = $stampCorrectionRequest->status === StampCorrectionRequest::STATUS_PENDING
			? 'pending'
			: 'approved';

		$isEditable = false;

		// 表示する時間は「申請の after_*」
		$clockInTime  = optional($stampCorrectionRequest->after_clock_in_at)->format('H:i');
		$clockOutTime = optional($stampCorrectionRequest->after_clock_out_at)->format('H:i');

		// 休憩は「申請に明細があればそれ優先」、なければ「勤怠の休憩」
		$breakSource = $stampCorrectionRequest->correctionBreaks->isNotEmpty()
			? $stampCorrectionRequest->correctionBreaks->sortBy('break_order')
			: $attendance->breaks->sortBy('break_start_at');

		$breakRows = collect($breakSource)->map(fn($break) => [
			'start' => optional($break->break_start_at)->format('H:i'),
			'end'   => optional($break->break_end_at)->format('H:i'),
		])->values()->toArray();

		// 備考欄は「申請理由」を表示用に渡す（フォーム入力はなし）
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
