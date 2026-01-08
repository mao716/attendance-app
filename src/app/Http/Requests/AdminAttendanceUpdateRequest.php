<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class AdminAttendanceUpdateRequest extends FormRequest
{
	private const MINUTES_PER_HOUR = 60;
	private const TIME_SEPARATOR = ':';

	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'clock_in_at'  => ['required', 'date_format:H:i'],
			'clock_out_at' => ['required', 'date_format:H:i'],

			'breaks' => ['nullable', 'array'],
			'breaks.*.start' => ['nullable', 'date_format:H:i'],
			'breaks.*.end'   => ['nullable', 'date_format:H:i'],

			'reason' => ['required', 'string', 'max:255'],
		];
	}

	public function messages(): array
	{
		return [
			'clock_in_at.required'  => '出勤時間を入力してください',
			'clock_out_at.required' => '退勤時間を入力してください',

			'reason.required' => '備考を記入してください',
			'reason.max'      => '備考は255文字以内で入力してください',
		];
	}

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator) {
			$clockIn  = $this->input('clock_in_at');
			$clockOut = $this->input('clock_out_at');

			if ($clockIn && $clockOut && $this->toMinutes($clockIn) >= $this->toMinutes($clockOut)) {
				$validator->errors()->add('clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
			}

			$breakRows = $this->input('breaks', []);
			if (!is_array($breakRows)) {
				return;
			}

			$inMinutes  = $clockIn  ? $this->toMinutes($clockIn)  : null;
			$outMinutes = $clockOut ? $this->toMinutes($clockOut) : null;

			foreach ($breakRows as $index => $breakRow) {
				$start = $breakRow['start'] ?? null;
				$end   = $breakRow['end'] ?? null;

				if (!$start && !$end) {
					continue;
				}

				if (!$start || !$end) {
					$validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です');
					continue;
				}

				$startMinutes = $this->toMinutes($start);
				$endMinutes   = $this->toMinutes($end);

				if ($startMinutes >= $endMinutes) {
					$validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です');
					continue;
				}

				if ($inMinutes === null || $outMinutes === null) {
					continue;
				}

				if ($startMinutes < $inMinutes || $startMinutes >= $outMinutes) {
					$validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です');
				}

				if ($endMinutes > $outMinutes) {
					$validator->errors()->add("breaks.$index.end", '休憩時間もしくは退勤時間が不適切な値です');
					continue;
				}
			}

			$normalized = [];

			foreach ($breakRows as $index => $breakRow) {
				$start = $breakRow['start'] ?? null;
				$end   = $breakRow['end'] ?? null;

				if (! $start || ! $end) {
					continue;
				}

				$startMinutes = $this->toMinutes($start);
				$endMinutes   = $this->toMinutes($end);

				$normalized[] = [
					'index' => $index,
					'start' => $startMinutes,
					'end'   => $endMinutes,
				];
			}

			usort($normalized, fn($a, $b) => $a['start'] <=> $b['start']);

			for ($i = 0; $i < count($normalized) - 1; $i++) {
				if ($normalized[$i]['end'] > $normalized[$i + 1]['start']) {
					$validator->errors()->add(
						"breaks.{$normalized[$i]['index']}.start",
						'休憩時間が不適切な値です'
					);
					break;
				}
			}
		});
	}

	private function toMinutes(string $time): int
	{
		[$h, $m] = explode(self::TIME_SEPARATOR, $time);

		return ((int) $h) * self::MINUTES_PER_HOUR + (int) $m;
	}
}
