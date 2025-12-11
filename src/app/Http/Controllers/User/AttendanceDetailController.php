<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AttendanceDetailController extends Controller
{
	public function show(Request $request, Attendance $attendance)
	{
		// 自分以外の勤怠IDを叩かれたときは403
		if ($attendance->user_id !== Auth::id()) {
			abort(403);
		}

		// 申請一覧から来たかどうか（/attendance/detail/{id}?from_request=1）
		$fromRequestList = $request->boolean('from_request');

		// 関連ロード（休憩・修正申請 + その休憩）
		$attendance->load([
			'breaks' => function ($query) {
				$query->orderBy('break_start_at');
			},
			'correctionRequests' => function ($query) {
				$query->latest(); // created_at desc
			},
			'correctionRequests.correctionBreaks',
		]);

		// 最新の修正申請（あれば）
		$latestRequest = $attendance->correctionRequests->first();

		// ---- 申請状態の判定 ----
		$requestStatus = null; // 'pending' | 'approved' | null
		$isEditable    = true; // フォームを出すかどうか

		if ($latestRequest) {
			if ($latestRequest->status === StampCorrectionRequest::STATUS_PENDING) {
				// 承認待ち：どこから見ても編集不可
				$requestStatus = 'pending';
				$isEditable    = false;
			} elseif ($latestRequest->status === StampCorrectionRequest::STATUS_APPROVED) {
				if ($fromRequestList) {
					// 申請一覧から見た承認済み → 閲覧専用（バッジ表示）
					$requestStatus = 'approved';
					$isEditable    = false;
				} else {
					// 勤怠一覧から見た承認済み → 再修正OK
					// requestStatus は null のまま扱う（= Blade ではステータスなし）
					$requestStatus = null;
					$isEditable    = true;
				}
			}
		}

		// ---- 出勤・退勤：表示用 DateTime を決定 ----
		$displayClockInAt  = $attendance->clock_in_at;
		$displayClockOutAt = $attendance->clock_out_at;

		// 修正申請があれば after_* を優先（pending / approved 共通）
		if ($latestRequest) {
			if ($latestRequest->after_clock_in_at) {
				$displayClockInAt = $latestRequest->after_clock_in_at;
			}
			if ($latestRequest->after_clock_out_at) {
				$displayClockOutAt = $latestRequest->after_clock_out_at;
			}
		}

		$clockInTime  = optional($displayClockInAt)->format('H:i');
		$clockOutTime = optional($displayClockOutAt)->format('H:i');

		// ---- 休憩行の決定 ----
		$breakRows = [];

		if ($latestRequest && $latestRequest->correctionBreaks->isNotEmpty()) {
			// 修正後休憩があればそちらを優先（承認待ち・承認済みどちらも）
			$breakRows = $latestRequest->correctionBreaks
				->sortBy('break_order')
				->map(function ($break) {
					return [
						'start' => optional($break->break_start_at)->format('H:i'),
						'end'   => optional($break->break_end_at)->format('H:i'),
					];
				})
				->values()
				->toArray();
		} else {
			// 修正後休憩がなければ、元の勤怠の休憩
			$breakRows = $attendance->breaks
				->sortBy('break_start_at')
				->map(function ($break) {
					return [
						'start' => optional($break->break_start_at)->format('H:i'),
						'end'   => optional($break->break_end_at)->format('H:i'),
					];
				})
				->values()
				->toArray();
		}

		// 編集可能なときだけ「追加用の空行」を足して、最低2行にする
		if ($isEditable) {
			for ($i = count($breakRows); $i < 2; $i++) {
				$breakRows[] = [
					'start' => null,
					'end'   => null,
				];
			}
		}

		// 備考は「最新の修正申請の理由」を表示（なければ null）
		$note = $latestRequest?->reason;

		return view('attendance.detail', [
			'attendance'    => $attendance,
			'user'          => Auth::user(),
			'clockInTime'   => $clockInTime,
			'clockOutTime'  => $clockOutTime,
			'breakRows'     => $breakRows,
			'note'          => $note,
			'requestStatus' => $requestStatus,
			'isEditable'    => $isEditable,
			'latestRequest' => $latestRequest,
		]);
	}
}
