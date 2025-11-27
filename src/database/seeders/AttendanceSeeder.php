<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;

class AttendanceSeeder extends Seeder
{
	public function run(): void
	{
		// 一般ユーザーのみ（role=1）
		$users = User::where('role', 1)->get();

		// 3ヶ月分（90日）
		$days = 90;

		foreach ($users as $user) {
			foreach (range(0, $days - 1) as $i) {

				$workDate = now()->subDays($i)->format('Y-m-d');

				// ★ 20% の確率で休日にする（勤怠レコード作らない）
				if (rand(1, 100) <= 20) {
					continue; // ← この日は休み！
				}

				Attendance::factory()->create([
					'user_id'   => $user->id,
					'work_date' => $workDate,
				]);
			}
		}
	}
}
