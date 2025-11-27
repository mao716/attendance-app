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
		Schema::create('attendances', function (Blueprint $table) {
			$table->id(); // unsigned bigint, PK

			// usersテーブルへのFK
			$table->foreignId('user_id')
				->constrained('users'); // users(id) への外部キー

			// 1ユーザー1日1レコード
			$table->date('work_date'); // NOT NULL

			// 打刻時刻（NULL許可）
			$table->dateTime('clock_in_at')->nullable();
			$table->dateTime('clock_out_at')->nullable();

			// 分単位の集計値
			$table->integer('total_break_minutes'); // 休憩合計（分）
			$table->integer('working_minutes');     // 実働時間（分）

			// 勤務ステータス（例：0=勤務外,1=出勤中…などは後で定数化）
			$table->tinyInteger('status');

			$table->timestamps(); // created_at / updated_at

			// (user_id, work_date) の組み合わせで一意
			$table->unique(['user_id', 'work_date']);
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
