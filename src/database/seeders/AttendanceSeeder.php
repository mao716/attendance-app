<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
	private const DAYS = 90;

	private const MIN_WORK_HOURS = 6;
	private const MAX_WORK_HOURS = 8;

	private const MIN_BREAK_MINUTES = 30;
	private const MAX_BREAK_MINUTES = 60;

	private const START_HOUR_MIN = 8;
	private const END_HOUR_MAX = 20;

	private const TZ = 'Asia/Tokyo';

	public function run(): void
	{
		$users = User::query()
			->where('role', '!=', User::ROLE_ADMIN)
			->get();

		foreach ($users as $user) {
			foreach (range(0, self::DAYS - 1) as $i) {
				$date = Carbon::today(self::TZ)->subDays($i);

				if ($date->isWeekend()) {
					continue;
				}

				$workDate = $date->toDateString();

				$workingHours = rand(self::MIN_WORK_HOURS, self::MAX_WORK_HOURS);
				$workingMinutes = $workingHours * 60;

				$breakMinutes = rand(self::MIN_BREAK_MINUTES, self::MAX_BREAK_MINUTES);

				$totalMinutes = $workingMinutes + $breakMinutes;

				$baseDate = Carbon::createFromFormat('Y-m-d', $workDate, self::TZ);

				$earliestStart = $baseDate->copy()->setTime(self::START_HOUR_MIN, 0, 0);
				$latestStart = $baseDate->copy()
					->setTime(self::END_HOUR_MAX, 0, 0)
					->subMinutes($totalMinutes);

				if ($latestStart->lt($earliestStart)) {
					$latestStart = $earliestStart->copy();
				}

				$startTimestamp = rand($earliestStart->timestamp, $latestStart->timestamp);

				$clockIn = Carbon::createFromTimestamp($startTimestamp, self::TZ)->second(0);
				$clockOut = $clockIn->copy()->addMinutes($totalMinutes);

				Attendance::create([
					'user_id'             => $user->id,
					'work_date'           => $workDate,
					'clock_in_at'         => $clockIn,
					'clock_out_at'        => $clockOut,
					'total_break_minutes' => $breakMinutes,
					'working_minutes'     => $workingMinutes,
					'status'              => Attendance::STATUS_FINISHED,
					'note'                => null,
				]);
			}
		}
	}
}
