<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'clock_in_at'  => ['nullable', 'date_format:H:i'],
			'clock_out_at' => ['nullable', 'date_format:H:i'],

			'breaks'         => ['array'],
			'breaks.*.start' => ['nullable', 'date_format:H:i'],
			'breaks.*.end'   => ['nullable', 'date_format:H:i'],

			'reason' => ['required', 'string', 'max:255'],
		];
	}

	public function messages(): array
	{
		return [
			// 要件④だけは必須
			'reason.required' => '備考を記入してください',

			// ★要件外なので書かない
			// 'clock_in_at.date_format' ...
			// 'breaks.*.start.date_format' ...
			// 'reason.max' ...
		];
	}

	public function withValidator($validator): void
	{
		$validator->after(function ($validator) {
			/** @var \App\Models\Attendance|null $attendance */
			$attendance = $this->route('attendance');

			if (!$attendance) {
				return;
			}

			$baseClockIn  = optional($attendance->clock_in_at)?->format('H:i');
			$baseClockOut = optional($attendance->clock_out_at)?->format('H:i');

			$inputClockIn  = $this->input('clock_in_at')  ?: $baseClockIn;
			$inputClockOut = $this->input('clock_out_at') ?: $baseClockOut;

			// 片方だけ入力 → 要件①でまとめて弾く
			if ($this->filled('clock_in_at') xor $this->filled('clock_out_at')) {
				$validator->errors()->add(
					'clock_in_at',
					'出勤時間もしくは退勤時間が不適切な値です'
				);
				return;
			}

			// 出勤・退勤の前後（要件①）
			if ($inputClockIn && $inputClockOut) {
				$inMinutes  = $this->toMinutes($inputClockIn);
				$outMinutes = $this->toMinutes($inputClockOut);

				if ($inMinutes >= $outMinutes) {
					$validator->errors()->add(
						'clock_in_at',
						'出勤時間もしくは退勤時間が不適切な値です'
					);
				}
			}

			$breakRows = $this->input('breaks', []);

			foreach ($breakRows as $index => $breakRow) {
				$start = $breakRow['start'] ?? null;
				$end   = $breakRow['end'] ?? null;

				// 片方だけ入力 → 要件②に寄せる（休憩が不適切）
				if (($start && !$end) || (!$start && $end)) {
					$validator->errors()->add(
						"breaks.$index.start",
						'休憩時間が不適切な値です'
					);
					continue;
				}

				// 両方空（=休憩削除扱いの入力）ならスキップ
				if (!$start && !$end) {
					continue;
				}

				// 出勤・退勤がなければ休憩の前後関係は判定できないのでスキップ
				if (!$inputClockIn || !$inputClockOut) {
					continue;
				}

				$startMinutes = $this->toMinutes($start);
				$endMinutes   = $this->toMinutes($end);
				$inMinutes    = $this->toMinutes($inputClockIn);
				$outMinutes   = $this->toMinutes($inputClockOut);

				// 要件②：休憩開始が出勤より前 or 退勤より後
				if ($startMinutes < $inMinutes || $startMinutes > $outMinutes) {
					$validator->errors()->add(
						"breaks.$index.start",
						'休憩時間が不適切な値です'
					);
				}

				// 要件③：休憩終了が退勤より後
				if ($endMinutes > $outMinutes) {
					$validator->errors()->add(
						"breaks.$index.end",
						'休憩時間もしくは退勤時間が不適切な値です'
					);
				}

				// 開始>=終了（要件文言指定なし → 要件②に寄せる）
				if ($endMinutes <= $startMinutes) {
					$validator->errors()->add(
						"breaks.$index.end",
						'休憩時間が不適切な値です'
					);
				}
			}
		});
	}

	private function toMinutes(string $time): int
	{
		[$h, $m] = explode(':', $time);
		return ((int) $h) * 60 + (int) $m;
	}
}
