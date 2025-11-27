<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance;

class AttendanceBreak extends Model
{
	use HasFactory;

	protected $fillable = [
		'attendance_id',
		'break_start_at',
		'break_end_at',
	];

	// 勤怠（Attendance）とのリレーション（多対1）
	public function attendance()
	{
		return $this->belongsTo(Attendance::class);
	}
}
