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
				// 承認待ち：どこから見ても編集不可
				$requestStatus = 'pending';
				$isEditable    = false;
			} elseif ($latestRequest->status === StampCorrectionRequest::STATUS_APPROVED) {
				if ($fromRequestList) {
					// ★ 申請一覧から見た承認済み → 閲覧専用（バッジ表示）
					$requestStatus = 'approved';
					$isEditable    = false;
				} else {
					// ★ 勤怠一覧から見た承認済み → 再修正OK
					// requestStatus は null のまま扱う（= Blade 側では「ステータスなし」として扱われる）
					$requestStatus = null;
					$isEditable    = true;
				}
			}
		}

		// ---- 表示用の時刻フォーマット ----
		$clockInTime  = optional($attendance->clock_in_at)->format('H:i');
		$clockOutTime = optional($attendance->clock_out_at)->format('H:i');

		// 休憩（存在するものだけ H:i にする）
		$breakRows = $attendance->breaks->map(function ($break) {
			return [
				'start' => optional($break->break_start_at)->format('H:i'),
				'end'   => optional($break->break_end_at)->format('H:i'),
			];
		})->values()->toArray();

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
