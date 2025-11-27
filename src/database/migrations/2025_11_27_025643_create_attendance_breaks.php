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
		Schema::create('attendance_breaks', function (Blueprint $table) {
			$table->id(); // unsigned bigint, PK

			// attendances テーブルへのFK
			$table->foreignId('attendance_id')
				->constrained('attendances'); // attendances(id) へ

			// 休憩開始・終了
			$table->dateTime('break_start_at');   // NOT NULL
			$table->dateTime('break_end_at')->nullable(); // NULL許可（休憩中の場合など）

			$table->timestamps(); // created_at / updated_at
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_breaks');
    }
};
