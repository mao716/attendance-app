<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;

class AttendanceDetailController extends Controller
{
	public function show(Attendance $attendance)
	{
		// 自分以外の勤怠IDを叩かれたときは403
		if ($attendance->user_id !== Auth::id()) {
			abort(403);
		}

		// 関連ロード（休憩・修正申請）
		$attendance->load([
			'breaks' => function ($query) {
				$query->orderBy('break_start_at');
			},
			'correctionRequests' => function ($query) {
				$query->latest();
			},
		]);

		// 最新の修正申請（あれば）
		$latestRequest = $attendance->correctionRequests->first();

		// ---- 申請状態の判定 ----
		$requestStatus = null;      // 'pending' | 'approved' | null
		$isEditable    = true;      // 修正ボタンを出すかどうか

		if ($latestRequest) {
			if ($latestRequest->status === StampCorrectionRequest::STATUS_PENDING) {
				$requestStatus = 'pending';
				$isEditable    = false;   // 承認待ちは修正不可
			} elseif ($latestRequest->status === StampCorrectionRequest::STATUS_APPROVED) {
				$requestStatus = 'approved';
				$isEditable    = false;   // 承認済みも再申請不可
			}
		}

		// ---- 表示用の時刻フォーマット ----
		$clockInTime  = optional($attendance->clock_in_at)->format('H:i');
		$clockOutTime = optional($attendance->clock_out_at)->format('H:i');

		$breakRows = $attendance->breaks->map(function ($break) {
			return [
				'start' => optional($break->break_start_at)->format('H:i'),
				'end'   => optional($break->break_end_at)->format('H:i'),
			];
		})->values();

		// 編集可能なときだけ「追加用の空行」を1行足す（UIの 休憩2 行）
		if ($isEditable) {
			$breakRows->push([
				'start' => null,
				'end'   => null,
			]);
		}

		// 備考は「最新の修正申請の理由」を表示（なければ空）
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
