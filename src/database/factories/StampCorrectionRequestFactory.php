<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class StampCorrectionRequestFactory extends Factory
{
    public function definition(): array
    {
        // Attendance をランダム取得（なければ生成）
        $attendance = Attendance::inRandomOrder()->first()
            ?? Attendance::factory()->create();

        // その勤怠の元の打刻
        $originalClockIn  = Carbon::parse($attendance->clock_in_at);
        $originalClockOut = Carbon::parse($attendance->clock_out_at);

        // before のデータ（元の値のまま or 少し前後した値を使う）
        $beforeClockIn  = $originalClockIn->copy();
        $beforeClockOut = $originalClockOut->copy();
        $beforeBreakMin = $attendance->total_break_minutes ?? 60;

        // after のデータ（修正後）：バリデーションに通るように必ず整合性を保つ
        $afterClockIn = $originalClockIn->copy()->subMinutes(
            $this->faker->numberBetween(0, 10)
        );
        $afterClockOut = $originalClockOut->copy()->addMinutes(
            $this->faker->numberBetween(0, 10)
        );

        // after の休憩時間（必ず 退勤より前・出勤より後）
        $afterBreakMin = $this->faker->numberBetween(30, 90);

        return [
            'attendance_id'          => $attendance->id,
            'user_id'                => $attendance->user_id,

            'before_clock_in_at'     => $beforeClockIn,
            'before_clock_out_at'    => $beforeClockOut,
            'before_break_minutes'   => $beforeBreakMin,

            'after_clock_in_at'      => $afterClockIn,
            'after_clock_out_at'     => $afterClockOut,
            'after_break_minutes'    => $afterBreakMin,

            // 備考は必須（基本設計書ルール）
            'reason'                 => $this->faker->sentence(6),

            // ステータス（0=承認待ち / 1=承認済）
            'status'                 => $this->faker->randomElement([0, 1]),

            // 承認済なら日時、承認待ちなら null
            'approved_at'            => $this->faker->boolean(30)
                                        ? now()
                                        : null,

            'created_at'             => now(),
            'updated_at'             => now(),
        ];
    }
}
