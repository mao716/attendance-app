<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffAttendanceCsvController extends Controller
{
	public function download(Request $request, int $id): StreamedResponse
	{
		$user = User::query()->where('role', 1)->findOrFail($id);

		$targetMonth = $this->resolveTargetMonth($request->query('month'));

		$start = $targetMonth->copy()->startOfMonth();
		$end = $targetMonth->copy()->endOfMonth();

		$attendances = Attendance::query()
			->where('user_id', $user->id)
			->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
			->orderBy('work_date')
			->get();

		$fileName = sprintf('attendance_%s_%s.csv', $user->id, $targetMonth->format('Y-m'));

		$headers = [
			'Content-Type' => 'text/csv; charset=Shift_JIS',
			'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
		];

		return response()->streamDownload(function () use ($attendances) {
			$out = fopen('php://output', 'w');

			$writeRow = function (array $row) use ($out) {
				$encoded = array_map(fn($v) => mb_convert_encoding((string) $v, 'SJIS-win', 'UTF-8'), $row);
				fputcsv($out, $encoded);
			};

			$writeRow(['日付', '出勤', '退勤', '休憩', '合計']);

			foreach ($attendances as $attendance) {
				$clockIn = optional($attendance->clock_in_at)->format('H:i');
				$clockOut = optional($attendance->clock_out_at)->format('H:i');

				$breakMinutes = (int) $attendance->total_break_minutes;
				$workMinutes = (int) $attendance->working_minutes;

				$writeRow([
					Carbon::parse($attendance->work_date)->format('m/d'),
					$clockIn ?? '',
					$clockOut ?? '',
					$this->formatMinutes($breakMinutes),
					$this->formatMinutes($workMinutes),
				]);
			}

			fclose($out);
		}, $fileName, $headers);
	}

	private function resolveTargetMonth(?string $monthParam): Carbon
	{
		try {
			return $monthParam
				? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
				: now()->startOfMonth();
		} catch (\Throwable) {
			return now()->startOfMonth();
		}
	}

	private function formatMinutes(int $minutes): string
	{
		$h = intdiv($minutes, 60);
		$m = $minutes % 60;

		return sprintf('%d:%02d', $h, $m);
	}
}
