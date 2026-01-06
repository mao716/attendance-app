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

			// 出勤 >= 退勤
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

				// 両方空はOK（未入力枠）
				if (!$start && !$end) {
					continue;
				}

				// 片方だけはNG
				if (!$start || !$end) {
					$validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です');
					continue;
				}

				$startMinutes = $this->toMinutes($start);
				$endMinutes   = $this->toMinutes($end);

				// 休憩開始 >= 休憩終了
				if ($startMinutes >= $endMinutes) {
					$validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です');
					continue;
				}

				// 出退勤が揃ってる前提（required）だけど念のため
				if ($inMinutes === null || $outMinutes === null) {
					continue;
				}

				// 休憩開始が出勤より前 or 退勤より後
				if ($startMinutes < $inMinutes || $startMinutes >= $outMinutes) {
					$validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です');
				}

				// 休憩終了が退勤より後
				if ($endMinutes > $outMinutes) {
					$validator->errors()->add("breaks.$index.end", '休憩時間もしくは退勤時間が不適切な値です');
					continue;
				}
			}

			// --- 重なりチェック（start順にして end > nextStart ならNG）---
			// 有効な休憩だけ集める（両方入ってる行）
			$normalized = [];

			foreach ($breakRows as $index => $breakRow) {
				$start = $breakRow['start'] ?? null;
				$end   = $breakRow['end'] ?? null;

				// 両方入力されてる行だけ（片方だけは別で弾いてる前提）
				if (!$start || !$end) {
					continue;
				}

				$normalized[] = [
					'index' => $index,
					'start' => $this->toMinutes($start),
					'end'   => $this->toMinutes($end),
				];
			}

			// start昇順
			usort($normalized, fn($a, $b) => $a['start'] <=> $b['start']);

			// 隣同士で比較（end > nextStart なら重なり）
			for ($i = 0; $i < count($normalized) - 1; $i++) {
				if ($normalized[$i]['end'] > $normalized[$i + 1]['start']) {
					// 表示する行は「重なりを起こしてる側」のindexに付けるのが分かりやすい
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
		[$h, $m] = explode(':', $time);
		return ((int) $h) * 60 + (int) $m;
	}
}
