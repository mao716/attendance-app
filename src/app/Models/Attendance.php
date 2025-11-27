<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\AttendanceBreak;
use App\Models\StampCorrectionRequest;

class Attendance extends Model
{
	use HasFactory;

	protected $fillable = [
		'user_id',
		'work_date',
		'clock_in_at',
		'clock_out_at',
		'total_break_minutes',
		'working_minutes',
		'status',
	];

	// ユーザーとのリレーション（多対1）
	public function user()
	{
		return $this->belongsTo(User::class);
	}

	// 休憩とのリレーション（1対多）
	public function breaks()
	{
		return $this->hasMany(AttendanceBreak::class);
	}

	// 修正申請とのリレーション（1対多）
	public function correctionRequests()
	{
		return $this->hasMany(StampCorrectionRequest::class);
	}
}
