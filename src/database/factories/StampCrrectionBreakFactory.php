<?php

namespace Database\Factories;

use App\Models\StampCorrectionBreak;
use App\Models\StampCorrectionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StampCorrectionBreakFactory extends Factory
{
	protected $model = StampCorrectionBreak::class;

	public function definition(): array
	{
		$start = Carbon::today()->setTime(12, 0)->addMinutes($this->faker->numberBetween(0, 60));
		$end   = (clone $start)->addMinutes($this->faker->numberBetween(15, 60));

		return [
			'stamp_correction_request_id' => StampCorrectionRequest::factory(),
			'break_order' => 1,
			'break_start_at' => $start,
			'break_end_at' => $end,
		];
	}
}
