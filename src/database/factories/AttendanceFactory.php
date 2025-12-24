<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
	protected $model = Attendance::class;

	public function definition(): array
	{
		// ★ デフォルトの勤務日（stateで上書き可能）
		$workDate = $this->faker->dateTimeBetween('-30 days', 'yesterday')->format('Y-m-d');

		// 出勤時間（09:00〜11:00）
		$clockIn = Carbon::parse($this->faker->dateTimeBetween(
			"{$workDate} 09:00:00",
			"{$workDate} 11:00:00"
		));

		// 退勤時間（17:00〜20:00）
		$clockOut = Carbon::parse($this->faker->dateTimeBetween(
			"{$workDate} 17:00:00",
			"{$workDate} 20:00:00"
		));

		$workMinutes = $clockIn->diffInMinutes($clockOut);

		$breakMinutes = $this->faker->numberBetween(30, 90);
		if ($workMinutes <= 0) {
			$workMinutes = 0;
			$breakMinutes = 0;
		} elseif ($breakMinutes >= $workMinutes) {
			$breakMinutes = $workMinutes - 1;
		}

		$workingMinutes = $workMinutes - $breakMinutes;

		return [
			// user_id はSeederやテストで渡す前提でもOK。渡されない時だけ作るならこれ
			'user_id'             => User::inRandomOrder()->first()?->id ?? User::factory(),
			'work_date'           => $workDate,
			'clock_in_at'         => $clockIn,
			'clock_out_at'        => $clockOut,
			'total_break_minutes' => $breakMinutes,
			'working_minutes'     => $workingMinutes,
			'status'              => 4,
		];
	}

	public function forDate(string $workDate): static
	{
		return $this->state(fn() => ['work_date' => $workDate]);
	}

	public function clockedInOnly(): static
	{
		return $this->state(function (array $attributes) {
			// clock_in_at が無いなら作る（work_date が無い場合にも対応）
			$workDate = $attributes['work_date'] ?? now()->subDay()->toDateString();

			$clockIn = $attributes['clock_in_at']
				? Carbon::parse($attributes['clock_in_at'])
				: Carbon::parse($workDate)->setTime(rand(9, 11), rand(0, 59), rand(0, 59));

			return [
				'work_date'           => $workDate,
				'clock_in_at'         => $clockIn,
				'clock_out_at'        => null,
				'total_break_minutes' => 0,
				'working_minutes'     => 0,
				'status'              => 1, // 出勤中（あなたの定義に合わせて）
			];
		});
	}

	public function clockedOut(): static
	{
		return $this->state(function (array $attributes) {
			$workDate = $attributes['work_date'] ?? now()->subDay()->toDateString();

			$clockIn = $attributes['clock_in_at']
				? Carbon::parse($attributes['clock_in_at'])
				: Carbon::parse($workDate)->setTime(rand(9, 11), rand(0, 59), rand(0, 59));

			$clockOut = $attributes['clock_out_at']
				? Carbon::parse($attributes['clock_out_at'])
				: Carbon::parse($workDate)->setTime(rand(17, 20), rand(0, 59), rand(0, 59));

			if ($clockOut->lte($clockIn)) {
				$clockOut = Carbon::parse($workDate)->setTime(20, 0, 0);
			}

			$workMinutes = $clockIn->diffInMinutes($clockOut);

			$breakMinutes = $attributes['total_break_minutes']
				?? $this->faker->numberBetween(30, 90);

			if ($breakMinutes >= $workMinutes) {
				$breakMinutes = max(0, $workMinutes - 1);
			}

			return [
				'work_date'           => $workDate,
				'clock_in_at'         => $clockIn,
				'clock_out_at'        => $clockOut,
				'total_break_minutes' => $breakMinutes,
				'working_minutes'     => max(0, $workMinutes - $breakMinutes),
				'status'              => 4, // 退勤済（あなたの定義に合わせて）
			];
		});
	}

	public function clockedOutOn(string $workDate): static
	{
		return $this->forDate($workDate)->clockedOut();
	}

	public function clockedInOnlyOn(string $workDate): static
	{
		return $this->forDate($workDate)->clockedInOnly();
	}
}
