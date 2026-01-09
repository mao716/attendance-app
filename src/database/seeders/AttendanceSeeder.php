<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
	private const DAYS = 90;

	private const OFF_DAYS_PER_WEEK = 2;

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
			$offDates = $this->buildOffDatesForUser();

			foreach (range(0, self::DAYS - 1) as $i) {
				$date = Carbon::today(self::TZ)->subDays($i);
				$workDate = $date->toDateString();

				if (isset($offDates[$workDate])) {
					continue;
				}

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

	private function buildOffDatesForUser(): array
	{
		$offDates = [];

		$end = Carbon::today(self::TZ);
		$start = $end->copy()->subDays(self::DAYS - 1);

		$cursor = $start->copy()->startOfWeek(Carbon::MONDAY);

		while ($cursor->lte($end)) {
			$weekDays = [];

			for ($d = 0; $d < 7; $d++) {
				$day = $cursor->copy()->addDays($d);

				if ($day->lt($start) || $day->gt($end)) {
					continue;
				}

				$weekDays[] = $day->toDateString();
			}

			if (count($weekDays) > 0) {
				$pick = min(self::OFF_DAYS_PER_WEEK, count($weekDays));

				$pickedKeys = array_rand($weekDays, $pick);
				$pickedKeys = is_array($pickedKeys) ? $pickedKeys : [$pickedKeys];

				foreach ($pickedKeys as $key) {
					$offDates[$weekDays[$key]] = true;
				}
			}

			$cursor->addWeek();
		}

		return $offDates;
	}
}
