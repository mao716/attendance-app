<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
	public function definition(): array
	{
		// ユーザーをランダムに取得（存在しなければ作成）
		$userId = User::inRandomOrder()->first()?->id
			?? User::factory()->create()->id;

		// 勤務日（過去30日以内）
		$workDate = $this->faker
			->dateTimeBetween('-30 days', 'now')
			->format('Y-m-d');

		// 出勤時間（09:00〜11:00）→ Carbon に変換
		$clockIn = Carbon::parse(
			$this->faker->dateTimeBetween(
				"{$workDate} 09:00:00",
				"{$workDate} 11:00:00"
			)
		);

		// 退勤時間（17:00〜20:00）→ Carbon に変換
		$clockOut = Carbon::parse(
			$this->faker->dateTimeBetween(
				$clockIn->copy()->format('Y-m-d H:i:s'),
				"{$workDate} 20:00:00"
			)
		);

		// 休憩時間（30〜90分）
		$breakMinutes = $this->faker->numberBetween(30, 90);

		// 実働時間（出勤〜退勤−休憩）
		$workingMinutes = $clockIn->diffInMinutes($clockOut) - $breakMinutes;

		// ステータス（退勤済＝4）
		$status = 4;

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
