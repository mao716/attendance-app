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
		// 自分以外は403
		if ($attendance->user_id !== Auth::id()) {
			abort(403);
		}

		// 申請一覧から来たか
		$fromRequest = $request->boolean('from_request');
		$requestId   = $request->integer('request_id'); // ← 申請一覧からはこれを必ず渡す

		// 関連ロード（勤怠の休憩は常に必要）
		$attendance->load([
			'breaks' => fn($q) => $q->orderBy('break_start_at'),
		]);

		// 申請一覧から来た場合：対象の申請を「固定」して取得
		$targetRequest = null;

		if ($fromRequest) {
			if (!$requestId) {
				// request_id が無いのは設計上おかしいので弾く（好みで 404 or 403）
				abort(404);
			}

			$targetRequest = StampCorrectionRequest::query()
				->where('id', $requestId)
				->where('attendance_id', $attendance->id)
				->where('user_id', Auth::id())
				->with(['correctionBreaks' => fn($q) => $q->orderBy('break_order')])
				->firstOrFail();
		} else {
			// 勤務一覧から来た場合：最新申請を見て「pending中かどうか」だけ判断したい
			$targetRequest = StampCorrectionRequest::query()
				->where('attendance_id', $attendance->id)
				->where('user_id', Auth::id())
				->with(['correctionBreaks' => fn($q) => $q->orderBy('break_order')])
				->latest()
				->first();
		}

		/*
        |--------------------------------------------------------------------------
        | 表示モード決定
        |--------------------------------------------------------------------------
        | - from_request=1 : targetRequest の after を必ず表示（閲覧専用）
        | - from_request=0 :
        |    - pending があれば after を表示（閲覧専用）
        |    - それ以外は attendance を表示（編集OK）
        */
		$isPending  = $targetRequest && $targetRequest->status === StampCorrectionRequest::STATUS_PENDING;
		$isApproved = $targetRequest && $targetRequest->status === StampCorrectionRequest::STATUS_APPROVED;

		$useAfterData = false;
		$isEditable   = true;
		$requestStatus = null; // 'pending' | 'approved' | null

		if ($fromRequest) {
			// 申請一覧 → 詳細は常に閲覧専用
			$isEditable = false;
			$useAfterData = true;
			$requestStatus = $isPending ? 'pending' : 'approved';
		} else {
			// 勤務一覧 → 詳細
			if ($isPending) {
				$isEditable = false;
				$useAfterData = true;
				$requestStatus = 'pending';
			} else {
				// approved でも再申請OKなので editable のまま
				$isEditable = true;
				$useAfterData = false;
				$requestStatus = null;
			}
		}

		/*
        |--------------------------------------------------------------------------
        | 出勤・退勤（表示）
        |--------------------------------------------------------------------------
        */
		$displayClockInAt  = $attendance->clock_in_at;
		$displayClockOutAt = $attendance->clock_out_at;

		if ($useAfterData && $targetRequest) {
			$displayClockInAt  = $targetRequest->after_clock_in_at;
			$displayClockOutAt = $targetRequest->after_clock_out_at;
		}

		$clockInTime  = optional($displayClockInAt)->format('H:i');
		$clockOutTime = optional($displayClockOutAt)->format('H:i');

		/*
        |--------------------------------------------------------------------------
        | 休憩（表示）
        |--------------------------------------------------------------------------
        | - after表示モードなら stamp_correction_breaks を優先
        | - それ以外は attendance_breaks
        */
		if ($useAfterData && $targetRequest && $targetRequest->correctionBreaks->isNotEmpty()) {
			$breakRows = $targetRequest->correctionBreaks->map(fn($b) => [
				'start' => optional($b->break_start_at)->format('H:i'),
				'end'   => optional($b->break_end_at)->format('H:i'),
			])->values()->toArray();
		} else {
			$breakRows = $attendance->breaks->map(fn($b) => [
				'start' => optional($b->break_start_at)->format('H:i'),
				'end'   => optional($b->break_end_at)->format('H:i'),
			])->values()->toArray();
		}

		// 編集可能なら最低2行を確保
		if ($isEditable) {
			for ($i = count($breakRows); $i < 2; $i++) {
				$breakRows[] = ['start' => null, 'end' => null];
			}
		}

		/*
        |--------------------------------------------------------------------------
        | 備考（表示/フォーム初期値）
        |--------------------------------------------------------------------------
        | - 申請一覧→詳細：その申請の reason を表示（フォームは使わない）
        | - 勤務一覧→詳細：
        |    - pending：pending の reason を表示（閲覧用）
        |    - editable：フォーム初期値は「最新 approved の reason」（残してOK仕様）
        */
		$noteForDisplay = null;
		$noteForForm    = null;

		if ($fromRequest && $targetRequest) {
			$noteForDisplay = $targetRequest->reason;
		} else {
			if ($isPending && $targetRequest) {
				$noteForDisplay = $targetRequest->reason;
			}

			if ($isEditable) {
				$latestApproved = StampCorrectionRequest::query()
					->where('attendance_id', $attendance->id)
					->where('user_id', Auth::id())
					->where('status', StampCorrectionRequest::STATUS_APPROVED)
					->orderByDesc('approved_at')
					->orderByDesc('created_at')
					->first();

				$noteForForm = $latestApproved?->reason;
			}
		}

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

			// デバッグや表示分岐に使いたいなら渡してOK
			'fromRequest'    => $fromRequest,
			'requestId'      => $requestId,
		]);
	}
}
