<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Attendance;

class StampCorrectionRequest extends Model
{
	use HasFactory;

	protected $fillable = [
		'attendance_id',
		'user_id',
		'before_clock_in_at',
		'before_clock_out_at',
		'before_break_minutes',
		'after_clock_in_at',
		'after_clock_out_at',
		'after_break_minutes',
		'reason',
		'status',
		'approved_at',
	];

	protected $casts = [
		'before_clock_in_at'  => 'datetime',
		'before_clock_out_at' => 'datetime',
		'after_clock_in_at'   => 'datetime',
		'after_clock_out_at'  => 'datetime',
		'approved_at'         => 'datetime',
	];

	// 修正元の勤怠
	public function attendance()
	{
		return $this->belongsTo(Attendance::class);
	}

	// 申請したユーザー
	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
