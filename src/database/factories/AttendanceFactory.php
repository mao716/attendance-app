<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
	protected $model = Attendance::class;

	private const TZ = 'Asia/Tokyo';

	public function definition(): array
	{
		return [
			'user_id'             => User::factory(),
			'work_date'           => now(self::TZ)->toDateString(),
			'clock_in_at'         => null,
			'clock_out_at'        => null,
			'total_break_minutes' => 0,
			'working_minutes'     => 0,
			'status'              => Attendance::STATUS_OFF,
			'note'                => null,
		];
	}

	public function forDate(string $workDate): static
	{
		return $this->state(fn() => [
			'work_date' => Carbon::createFromFormat('Y-m-d', $workDate, self::TZ)->toDateString(),
		]);
	}

	public function working(?string $workDate = null): static
	{
		return $this->state(function () use ($workDate) {
			$date = Carbon::createFromFormat(
				'Y-m-d',
				$workDate ?? now(self::TZ)->toDateString(),
				self::TZ
			);

			$clockIn = $date->copy()->setTime(rand(8, 11), rand(0, 59), 0);

			return [
				'work_date'           => $date->toDateString(),
				'clock_in_at'         => $clockIn,
				'clock_out_at'        => null,
				'total_break_minutes' => 0,
				'working_minutes'     => 0,
				'status'              => Attendance::STATUS_WORKING,
			];
		});
	}

	public function onBreak(?string $workDate = null): static
	{
		return $this->state(function () use ($workDate) {
			$date = Carbon::createFromFormat(
				'Y-m-d',
				$workDate ?? now(self::TZ)->toDateString(),
				self::TZ
			);

			$clockIn = $date->copy()->setTime(rand(8, 11), rand(0, 59), 0);

			return [
				'work_date'           => $date->toDateString(),
				'clock_in_at'         => $clockIn,
				'clock_out_at'        => null,
				'total_break_minutes' => 0,
				'working_minutes'     => 0,
				'status'              => Attendance::STATUS_BREAK,
			];
		});
	}

	public function finished(?string $workDate = null, ?int $breakMinutes = null): static
	{
		return $this->state(function () use ($workDate, $breakMinutes) {
			$date = Carbon::createFromFormat(
				'Y-m-d',
				$workDate ?? now(self::TZ)->toDateString(),
				self::TZ
			);

			$clockIn = $date->copy()->setTime(rand(8, 13), rand(0, 59), 0);

			$workingMinutes = rand(6, 8) * 60;
			$break = $breakMinutes ?? rand(30, 60);
			$totalMinutes = $workingMinutes + $break;

			$clockOut = $clockIn->copy()->addMinutes($totalMinutes);

			$endLimit = $date->copy()->setTime(20, 0, 0);
			if ($clockOut->gt($endLimit)) {
				$clockOut = $endLimit->copy();
				$clockIn = $clockOut->copy()->subMinutes($totalMinutes);

				$startLimit = $date->copy()->setTime(8, 0, 0);
				if ($clockIn->lt($startLimit)) {
					$clockIn = $startLimit->copy();
					$clockOut = $clockIn->copy()->addMinutes($totalMinutes);

					if ($clockOut->gt($endLimit)) {
						$clockOut = $endLimit->copy();
					}
				}
			}

			return [
				'work_date'           => $date->toDateString(),
				'clock_in_at'         => $clockIn,
				'clock_out_at'        => $clockOut,
				'total_break_minutes' => $break,
				'working_minutes'     => $workingMinutes,
				'status'              => Attendance::STATUS_FINISHED,
			];
		});
	}

	public function finishedOn(string $workDate, ?int $breakMinutes = null): static
	{
		return $this->finished($workDate, $breakMinutes);
	}

	public function workingOn(string $workDate): static
	{
		return $this->working($workDate);
	}

	public function onBreakOn(string $workDate): static
	{
		return $this->onBreak($workDate);
	}
}
