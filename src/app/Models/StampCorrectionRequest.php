<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Attendance;
use App\Models\StampCorrectionBreak;

class StampCorrectionRequest extends Model
{
	use HasFactory;

	public const STATUS_PENDING  = 0; // 承認待ち
	public const STATUS_APPROVED = 1; // 承認済み

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
		'status'              => 'integer',
		'before_break_minutes' => 'integer',
		'after_break_minutes' => 'integer',
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

	public function correctionBreaks()
	{
		return $this->hasMany(StampCorrectionBreak::class);
	}

	public function isPending(): bool
	{
		return $this->status === self::STATUS_PENDING;
	}

	public function isApproved(): bool
	{
		return $this->status === self::STATUS_APPROVED;
	}

	public function getStatusLabelAttribute(): string
	{
		if ($this->isPending()) {
			return '承認待ち';
		}

		if ($this->isApproved()) {
			return '承認済み';
		}

		return '不明';
	}
}
