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
			// 出勤・退勤（どちらか入力したら両方必須／H:i 形式）
			'clock_in_at'  => ['nullable', 'date_format:H:i'],
			'clock_out_at' => ['nullable', 'date_format:H:i'],

			// 休憩（配列）
			'breaks'           => ['array'],
			'breaks.*.start'   => ['nullable', 'date_format:H:i'],
			'breaks.*.end'     => ['nullable', 'date_format:H:i'],

			// 備考
			'reason' => ['required', 'string', 'max:255'],
		];
	}

	public function messages(): array
	{
		return [
			'clock_in_at.date_format'  => '出勤時間は「HH:MM」形式で入力してください',
			'clock_out_at.date_format' => '退勤時間は「HH:MM」形式で入力してください',

			'breaks.*.start.date_format' => '休憩開始時間は「HH:MM」形式で入力してください',
			'breaks.*.end.date_format'   => '休憩終了時間は「HH:MM」形式で入力してください',

			'reason.required' => '備考を記入してください',
			'reason.max'      => '備考は255文字以内で入力してください',
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

			// 元の勤怠時刻（DB）を基準として使う
			$baseClockIn  = optional($attendance->clock_in_at)?->format('H:i');
			$baseClockOut = optional($attendance->clock_out_at)?->format('H:i');

			// 入力された値（空なら元の値で補完）
			$inputClockIn  = $this->input('clock_in_at')  ?: $baseClockIn;
			$inputClockOut = $this->input('clock_out_at') ?: $baseClockOut;

			// どちらかだけ入力されているパターンを弾く
			if ($this->filled('clock_in_at') xor $this->filled('clock_out_at')) {
				$validator->errors()->add(
					'clock_in_at',
					'出勤時間と退勤時間は両方入力してください'
				);
				return;
			}

			// 両方そろっているときだけ時間の前後チェック
			if ($inputClockIn && $inputClockOut) {
				$inMinutes  = $this->toMinutes($inputClockIn);
				$outMinutes = $this->toMinutes($inputClockOut);

				// 出勤 >= 退勤 はNG（機能要件のメッセージ）
				if ($inMinutes >= $outMinutes) {
					$validator->errors()->add(
						'clock_in_at',
						'出勤時間もしくは退勤時間が不適切な値です'
					);
				}
			}

			// 休憩のバリデーション
			$breaks = $this->input('breaks', []);

			foreach ($breaks as $index => $break) {
				$start = $break['start'] ?? null;
				$end   = $break['end'] ?? null;

				// どちらかだけ入力 → NG
				if (($start && !$end) || (!$start && $end)) {
					$validator->errors()->add(
						"breaks.$index.start",
						'休憩時間は開始と終了を両方入力してください'
					);
					continue;
				}

				// 両方空ならスキップ
				if (!$start && !$end) {
					continue;
				}

				$startMinutes = $this->toMinutes($start);
				$endMinutes   = $this->toMinutes($end);

				// 出勤・退勤がどちらか欠けていたら、ここでのチェックはできないのでスキップ
				if (!$inputClockIn || !$inputClockOut) {
					continue;
				}

				$inMinutes  = $this->toMinutes($inputClockIn);
				$outMinutes = $this->toMinutes($inputClockOut);

				// 休憩開始が出勤より前 or 退勤より後 → メッセージ2
				if ($startMinutes < $inMinutes || $startMinutes > $outMinutes) {
					$validator->errors()->add(
						"breaks.$index.start",
						'休憩時間が不適切な値です'
					);
				}

				// 休憩終了が退勤より後 / 開始より前or同じ → メッセージ3
				if ($endMinutes > $outMinutes || $endMinutes <= $startMinutes) {
					$validator->errors()->add(
						"breaks.$index.end",
						'休憩時間もしくは退勤時間が不適切な値です'
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
