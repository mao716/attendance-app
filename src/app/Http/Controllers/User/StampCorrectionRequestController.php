<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class StampCorrectionRequestController extends Controller
{
	/**
	 * PG06 申請一覧（一般ユーザー）
	 * - 承認待ちタブ：自分の PENDING
	 * - 承認済みタブ：自分の APPROVED
	 */
	public function indexForUser()
	{
		$userId = Auth::id();

		$pendingRequests = StampCorrectionRequest::with('attendance')
			->where('user_id', $userId)
			->where('status', StampCorrectionRequest::STATUS_PENDING)
			->orderByDesc('created_at')
			->get();

		$approvedRequests = StampCorrectionRequest::with('attendance')
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

	/**
	 * 勤怠詳細画面からの「修正」押下 → 修正申請を新規作成
	 */
	public function store(AttendanceCorrectionRequest $request, Attendance $attendance)
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
		$beforeBreakMinutes = $attendance->total_break_minutes;

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

		// ---- after（休憩 明細保存のため入力取得） ----
		$breakInputs = $validated['breaks'] ?? [];
		$hasAnyBreakInput = collect($breakInputs)->contains(function ($row) {
			return !empty($row['start']) || !empty($row['end']);
		});

		// 合計休憩時間をまず計算
		$afterBreakMinutes = 0;

		if ($hasAnyBreakInput) {
			foreach ($breakInputs as $row) {
				if (!empty($row['start']) && !empty($row['end'])) {
					$startAt = Carbon::parse("$workDate {$row['start']}");
					$endAt   = Carbon::parse("$workDate {$row['end']}");
					$afterBreakMinutes += $startAt->diffInMinutes($endAt);
				}
			}
		} else {
			$afterBreakMinutes = $beforeBreakMinutes;
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

		// ---- 修正後の休憩を子テーブルへ保存 ----
		if ($hasAnyBreakInput) {
			$order = 1;

			foreach ($breakInputs as $row) {
				if (!empty($row['start']) && !empty($row['end'])) {
					$requestRecord->correctionBreaks()->create([
						'break_order'    => $order,
						'break_start_at' => Carbon::parse("$workDate {$row['start']}"),
						'break_end_at'   => Carbon::parse("$workDate {$row['end']}"),
					]);
					$order++;
				}
			}
		}

		return redirect()->route('stamp_correction_request.user_index');
	}
}
