<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminAttendanceUpdateRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true; // can:is-admin はルート側で担保
	}

	public function rules(): array
	{
		return [
			'clock_in_at' => ['required', 'date_format:H:i'],
			'clock_out_at' => ['required', 'date_format:H:i'],

			'breaks' => ['array'],
			'breaks.*.start' => ['nullable', 'date_format:H:i'],
			'breaks.*.end' => ['nullable', 'date_format:H:i'],

			'reason' => ['nullable', 'string', 'max:255'],
		];
	}

	public function messages(): array
	{
		return [
			'clock_in_at.required' => '出勤時間を入力してください',
			'clock_out_at.required' => '退勤時間を入力してください',
			'clock_in_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
			'clock_out_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',

			'breaks.*.start.date_format' => '休憩時間が不適切な値です',
			'breaks.*.end.date_format' => '休憩時間が不適切な値です',

			'reason.max' => '備考は255文字以内で入力してください',
		];
	}

	public function withValidator(Validator $validator): void
	{
		$validator->after(function (Validator $validator) {
			$clockIn = $this->input('clock_in_at');
			$clockOut = $this->input('clock_out_at');

			// 出勤 > 退勤 はNG
			if ($clockIn && $clockOut && $clockIn > $clockOut) {
				$validator->errors()->add('clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
			}

			$breaks = $this->input('breaks', []);
			if (!is_array($breaks)) {
				return;
			}

			// 入力された休憩だけに絞る（両方空は無視）
			$rows = [];
			foreach ($breaks as $i => $b) {
				$start = $b['start'] ?? null;
				$end = $b['end'] ?? null;

				if (!$start && !$end) {
					continue;
				}

				// 片方だけ入力はNG
				if (!$start || !$end) {
					$validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
					continue;
				}

				// start >= end はNG
				if ($start >= $end) {
					$validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
					continue;
				}

				// 出勤〜退勤の範囲外はNG（出勤/退勤が揃ってる前提）
				if ($clockIn && $clockOut) {
					if ($start < $clockIn || $end > $clockOut) {
						$validator->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
						continue;
					}
				}

				$rows[] = ['start' => $start, 'end' => $end, 'index' => $i];
			}

			// 休憩の重なりチェック（start順にして end > nextStart ならNG）
			usort($rows, fn($a, $b) => strcmp($a['start'], $b['start']));
			for ($i = 0; $i < count($rows) - 1; $i++) {
				if ($rows[$i]['end'] > $rows[$i + 1]['start']) {
					$validator->errors()->add("breaks.{$rows[$i]['index']}.start", '休憩時間が不適切な値です');
					break;
				}
			}
		});
	}
}
