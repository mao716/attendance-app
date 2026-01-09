<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
	use HasFactory;

	// ====================
	// ステータス定数
	// ====================
	public const STATUS_OFF      = 0;
	public const STATUS_WORKING  = 1;
	public const STATUS_BREAK    = 2;
	public const STATUS_FINISHED = 3;

	// ====================
	// Mass Assignment
	// ====================
	protected $fillable = [
		'user_id',
		'work_date',
		'clock_in_at',
		'clock_out_at',
		'total_break_minutes',
		'working_minutes',
		'status',
		'note',
	];

	// ====================
	// Casts
	// ====================
	protected $casts = [
		'work_date' => 'date:Y-m-d',
		'status' => 'int',
		'clock_in_at' => 'datetime',
		'clock_out_at' => 'datetime',
	];

	// ====================
	// Accessor
	// ====================
	public function getStatusLabelAttribute(): string
	{
		return match ($this->status) {
			self::STATUS_WORKING  => '出勤中',
			self::STATUS_BREAK    => '休憩中',
			self::STATUS_FINISHED => '退勤済',
			default               => '勤務外',
		};
	}

	// ====================
	// 状態判定
	// ====================
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

	// ====================
	// Relations
	// ====================
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function breaks(): HasMany
	{
		return $this->hasMany(AttendanceBreak::class);
	}

	public function stampCorrectionRequests(): HasMany
	{
		return $this->hasMany(StampCorrectionRequest::class);
	}
}
