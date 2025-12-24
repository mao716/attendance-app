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
			foreach (range(1, $days) as $i) {
				$workDate = now()->subDays($i)->toDateString();

				if (rand(1, 100) <= 20) {
					continue;
				}

				Attendance::factory()
					->clockedOutOn($workDate)
					->create(['user_id' => $user->id]);
			}
		}
	}
}
