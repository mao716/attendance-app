<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceDetailController extends Controller
{
	public function show(Request $request, Attendance $attendance)
	{
		// 自分以外の勤怠IDを叩かれたときは403
		if ($attendance->user_id !== Auth::id()) {
			abort(403);
		}

		// 申請一覧から来たかどうか
		$fromRequestList = $request->boolean('from_request');

		// 関連ロード
		$attendance->load([
			'breaks' => fn($q) => $q->orderBy('break_start_at'),
			'correctionRequests' => fn($q) => $q->latest(),
			'correctionRequests.correctionBreaks',
		]);

		// 最新の修正申請
		$latestRequest = $attendance->correctionRequests->first();

		/*
		|--------------------------------------------------------------------------
		| 申請状態と編集可否の判定
		|--------------------------------------------------------------------------
		*/
		$requestStatus = null; // 'pending' | 'approved' | null
		$isEditable    = true;

		if ($latestRequest) {
			if ($latestRequest->status === StampCorrectionRequest::STATUS_PENDING) {
				$requestStatus = 'pending';
				$isEditable    = false;
			}

			if ($latestRequest->status === StampCorrectionRequest::STATUS_APPROVED) {
				if ($fromRequestList) {
					$requestStatus = 'approved';
					$isEditable    = false;
				} else {
					// 勤怠一覧から見た承認済み → 再申請OK
					$requestStatus = null;
					$isEditable    = true;
				}
			}
		}

		/*
		|--------------------------------------------------------------------------
		| 表示用データの決定（after を使うかどうか）
		|--------------------------------------------------------------------------
		*/
		$useAfterData = false;

		if ($latestRequest) {
			// 承認待ちは必ず after を表示
			if ($latestRequest->status === StampCorrectionRequest::STATUS_PENDING) {
				$useAfterData = true;
			}

			// 申請一覧から見た承認済みも after を表示
			if (
				$latestRequest->status === StampCorrectionRequest::STATUS_APPROVED
				&& $fromRequestList
			) {
				$useAfterData = true;
			}
		}

		/*
		|--------------------------------------------------------------------------
		| 出勤・退勤（表示用）
		|--------------------------------------------------------------------------
		*/
		$displayClockInAt  = $attendance->clock_in_at;
		$displayClockOutAt = $attendance->clock_out_at;

		if ($useAfterData && $latestRequest) {
			$displayClockInAt  = $latestRequest->after_clock_in_at  ?? $displayClockInAt;
			$displayClockOutAt = $latestRequest->after_clock_out_at ?? $displayClockOutAt;
		}

		$clockInTime  = optional($displayClockInAt)->format('H:i');
		$clockOutTime = optional($displayClockOutAt)->format('H:i');

		/*
		|--------------------------------------------------------------------------
		| 休憩（表示用）
		|--------------------------------------------------------------------------
		*/
		if (
			$useAfterData &&
			$latestRequest &&
			$latestRequest->correctionBreaks->isNotEmpty()
		) {
			// 修正後休憩を表示
			$breakRows = $latestRequest->correctionBreaks
				->sortBy('break_order')
				->map(fn($b) => [
					'start' => optional($b->break_start_at)->format('H:i'),
					'end'   => optional($b->break_end_at)->format('H:i'),
				])
				->values()
				->toArray();
		} else {
			// 元の勤怠の休憩
			$breakRows = $attendance->breaks
				->map(fn($b) => [
					'start' => optional($b->break_start_at)->format('H:i'),
					'end'   => optional($b->break_end_at)->format('H:i'),
				])
				->values()
				->toArray();
		}

		// 編集可能なときは最低2行確保
		if ($isEditable) {
			for ($i = count($breakRows); $i < 2; $i++) {
				$breakRows[] = [
					'start' => null,
					'end'   => null,
				];
			}
		}

		/*
		|--------------------------------------------------------------------------
		| 備考
		|--------------------------------------------------------------------------
		*/
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
