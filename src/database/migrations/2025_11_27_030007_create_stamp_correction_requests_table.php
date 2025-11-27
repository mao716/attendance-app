<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
		Schema::create('stamp_correction_requests', function (Blueprint $table) {
			$table->id(); // unsigned bigint, PK

			// 紐づく勤怠＆ユーザー
			$table->foreignId('attendance_id')
				->constrained('attendances'); // attendances(id)

			$table->foreignId('user_id')
				->constrained('users'); // users(id)

			// 修正前の勤怠（NULL許可）
			$table->dateTime('before_clock_in_at')->nullable();
			$table->dateTime('before_clock_out_at')->nullable();
			$table->integer('before_break_minutes')->nullable();

			// 修正後の勤怠（必須）
			$table->dateTime('after_clock_in_at');
			$table->dateTime('after_clock_out_at');
			$table->integer('after_break_minutes');

			// 申請理由（必須）
			$table->text('reason');

			// 申請ステータス（例：0=承認待ち,1=承認済み…などは後で定数化）
			$table->tinyInteger('status');

			// 承認日時（まだ承認されてなければNULL）
			$table->dateTime('approved_at')->nullable();

			$table->timestamps(); // created_at / updated_at
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stamp_correction_requests');
    }
};
