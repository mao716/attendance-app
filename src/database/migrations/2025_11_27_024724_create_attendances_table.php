<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
		Schema::create('attendances', function (Blueprint $table) {
			$table->id();

			$table->foreignId('user_id')->constrained('users');
			$table->date('work_date');

			$table->dateTime('clock_in_at')->nullable();
			$table->dateTime('clock_out_at')->nullable();

			$table->integer('total_break_minutes')->default(0);
			$table->integer('working_minutes')->default(0);

			$table->tinyInteger('status')->default(0);

			$table->string('note', 255)->nullable();

			$table->timestamps();

			$table->unique(['user_id', 'work_date']);
		});
	}

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
