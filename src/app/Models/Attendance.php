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

	// ▼ ステータス定数
	public const STATUS_OFF      = 0;
	public const STATUS_WORKING  = 1;
	public const STATUS_BREAK    = 2;
	public const STATUS_FINISHED = 3;

	protected $fillable = [
		'user_id',
		'work_date',
		'clock_in_at',
		'clock_out_at',
		'total_break_minutes',
		'working_minutes',
		'status',
	];

	protected $casts = [
		'work_date'           => 'date',
		'clock_in_at'         => 'datetime',
		'clock_out_at'        => 'datetime',
	];

	// ▼ ステータスのラベル
	public function getStatusLabelAttribute(): string
	{
		return match ($this->status) {
			self::STATUS_WORKING  => '出勤中',
			self::STATUS_BREAK    => '休憩中',
			self::STATUS_FINISHED => '退勤済',
			default               => '勤務外',
		};
	}

	// ▼ 状態判定（Controller が読みやすくなる）
	public function isWorking(): bool
	{
		return $this->status === self::STATUS_WORKING;
	}
	public function isOnBreak(): bool
	{
		return $this->status === self::STATUS_BREAK;
	}
	public function isFinished(): bool
	{
		return $this->status === self::STATUS_FINISHED;
	}
	public function isNotStarted(): bool
	{
		return $this->status === self::STATUS_OFF;
	}

	// ▼ リレーション
	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function breaks()
	{
		return $this->hasMany(AttendanceBreak::class);
	}

	public function correctionRequests()
	{
		return $this->hasMany(StampCorrectionRequest::class);
	}
}
