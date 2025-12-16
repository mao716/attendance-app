<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
	public function run(): void
	{
		$users = User::where('role', 1)->get();
		$days = 90;

		foreach ($users as $user) {
			foreach (range(0, $days - 1) as $i) {

				$workDate = now()->subDays($i)->format('Y-m-d');

				// 20%休日
				if (rand(1, 100) <= 20) {
					continue;
				}

				// 出勤（09:00〜11:00）
				$clockIn = Carbon::parse($workDate)->setTime(
					rand(9, 11),
					rand(0, 59),
					rand(0, 59)
				);

				// 退勤（17:00〜20:00）
				$clockOut = Carbon::parse($workDate)->setTime(
					rand(17, 20),
					rand(0, 59),
					rand(0, 59)
				);

				// 保険：万一 退勤 <= 出勤 なら 8時間足す
				if ($clockOut->lte($clockIn)) {
					$clockOut = $clockIn->copy()->addHours(8);
				}

				$workMinutes = $clockIn->diffInMinutes($clockOut);

				// 休憩（30〜90分）ただし勤務時間を超えない
				$breakMinutes = rand(30, 90);
				if ($breakMinutes >= $workMinutes) {
					$breakMinutes = max(0, $workMinutes - 1);
				}

				$workingMinutes = max(0, $workMinutes - $breakMinutes);

				Attendance::create([
					'user_id'             => $user->id,
					'work_date'           => $workDate,
					'clock_in_at'         => $clockIn,
					'clock_out_at'        => $clockOut,
					'total_break_minutes' => $breakMinutes,
					'working_minutes'     => $workingMinutes,
					'status'              => 4, // 退勤済
				]);
			}
		}
	}
}
