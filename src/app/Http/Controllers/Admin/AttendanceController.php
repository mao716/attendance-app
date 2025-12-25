<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
	/**
	 * 勤怠一覧（管理者）: 日次勤怠一覧
	 */
	public function index(Request $request): View
	{
		$targetDate = $this->resolveTargetDate($request->query('date'));

		// 当日の勤怠を user_id で引けるようにする
		$attendancesByUserId = Attendance::query()
			->with('user')
			->where('work_date', $targetDate->toDateString())
			->get()
			->keyBy('user_id');

		// 一般ユーザーのみ（管理者は除外）
		$users = User::query()
			->where('role', '!=', 2)
			->orderBy('id')
			->get();

		// 表示用にまとめる（attendanceが無いユーザーは null）
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
}
