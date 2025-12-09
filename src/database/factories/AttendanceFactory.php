<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
	public function definition(): array
	{
		$userId = User::inRandomOrder()->first()?->id
			?? User::factory()->create()->id;

		// 勤務日（過去30日以内）
		$workDate = $this->faker
			->dateTimeBetween('-30 days', 'now')
			->format('Y-m-d');

		// 出勤時間（09:00〜11:00）
		$clockIn = Carbon::parse(
			$this->faker->dateTimeBetween(
				"{$workDate} 09:00:00",
				"{$workDate} 11:00:00"
			)
		);

		// ★退勤時間（17:00〜20:00）に修正  ← ここがポイント
		$clockOut = Carbon::parse(
			$this->faker->dateTimeBetween(
				"{$workDate} 17:00:00",
				"{$workDate} 20:00:00"
			)
		);

		// 勤務時間（分）
		$workMinutes = $clockIn->diffInMinutes($clockOut);

		// 休憩時間（30〜90分）※勤務時間より長くならないように保険を入れる
		$breakMinutes = $this->faker->numberBetween(30, 90);
		// 勤務時間が休憩より短いときの保険
		if ($workMinutes <= 0) {
			$workMinutes = 0;
			$breakMinutes = 0;
		} elseif ($breakMinutes >= $workMinutes) {
			$breakMinutes = $workMinutes - 1; // 少なくとも1分は働く
		}

		// 実働時間
		$workingMinutes = $workMinutes - $breakMinutes;

		$status = 4; // 退勤済

		return [
			'user_id'             => $userId,
			'work_date'           => $workDate,
			'clock_in_at'         => $clockIn,
			'clock_out_at'        => $clockOut,
			'total_break_minutes' => $breakMinutes,
			'working_minutes'     => $workingMinutes,
			'status'              => $status,
		];
	}
}
