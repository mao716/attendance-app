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

		// 元の勤怠情報（before 系に保存する用）
		$beforeClockInAt      = $attendance->clock_in_at;
		$beforeClockOutAt     = $attendance->clock_out_at;
		$beforeBreakMinutes   = $attendance->total_break_minutes;

		// 勤務日（"Y-m-d" だけ取り出して使う）
		$workDate = Carbon::parse($attendance->work_date)->format('Y-m-d');

		// ---- after の出退勤を決定 ----
		// 入力が空なら「元の値」をそのまま after に使う
		$clockInStr  = $validated['clock_in_at']  ?? null;
		$clockOutStr = $validated['clock_out_at'] ?? null;

		if ($clockInStr && $clockOutStr) {
			$afterClockInAt = Carbon::parse($workDate . ' ' . $clockInStr);
			$afterClockOutAt = Carbon::parse($workDate . ' ' . $clockOutStr);
		} else {
			$afterClockInAt  = $beforeClockInAt;
			$afterClockOutAt = $beforeClockOutAt;
		}

		// ---- after の休憩合計分数を計算 ----
		$breakInputs = $validated['breaks'] ?? [];

		$afterBreakMinutes = null;

		// 1つでも開始/終了が入力されていたら「入力値から再計算」
		$hasAnyBreakInput = collect($breakInputs)->contains(function ($row) {
			return !empty($row['start']) || !empty($row['end']);
		});

		if ($hasAnyBreakInput) {
			$afterBreakMinutes = 0;

			foreach ($breakInputs as $row) {
				$start = $row['start'] ?? null;
				$end   = $row['end'] ?? null;

				if (!$start || !$end) {
					continue; // ここは FormRequest 側で本来は弾かれている想定
				}

				$startAt = Carbon::parse($workDate . ' ' . $start);
				$endAt   = Carbon::parse($workDate . ' ' . $end);

				$afterBreakMinutes += $startAt->diffInMinutes($endAt);
			}
		} else {
			// 休憩入力が何もなければ元の合計休憩時間をそのまま使用
			$afterBreakMinutes = $beforeBreakMinutes;
		}

		// ---- 既に承認待ちがある場合はエラー返却（多重申請防止） ----
		$latestRequest = $attendance->correctionRequests()
			->latest()
			->first();

		if ($latestRequest && $latestRequest->status === StampCorrectionRequest::STATUS_PENDING) {
			return back()
				->withErrors(['request' => '承認待ちの修正申請が既に存在します。'])
				->withInput();
		}

		// ---- 修正申請レコード作成 ----
		StampCorrectionRequest::create([
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

		return redirect()
			->route('stamp_correction_request.user_index');
	}
}
