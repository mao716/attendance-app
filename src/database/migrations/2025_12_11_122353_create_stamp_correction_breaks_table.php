<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('stamp_correction_breaks', function (Blueprint $table) {
			$table->id();

			$table->unsignedBigInteger('stamp_correction_request_id');
			$table->unsignedTinyInteger('break_order'); // 1, 2, 3...
			$table->dateTime('break_start_at');
			$table->dateTime('break_end_at');

			$table->timestamps();

			// 外部キー（親の申請が消えたら子も消す）
			$table->foreign('stamp_correction_request_id')
				->references('id')->on('stamp_correction_requests')
				->onDelete('cascade');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('stamp_correction_breaks');
	}
};
