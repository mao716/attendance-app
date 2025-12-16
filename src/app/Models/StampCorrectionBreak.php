<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampCorrectionBreak extends Model
{
	use HasFactory;

	protected $fillable = [
		'stamp_correction_request_id',
		'break_order',
		'break_start_at',
		'break_end_at',
	];

	protected $casts = [
		'break_start_at' => 'datetime',
		'break_end_at'   => 'datetime',
		'break_order'    => 'integer',
	];

	public function correctionRequest(): BelongsTo
	{
		return $this->belongsTo(
			StampCorrectionRequest::class,
			'stamp_correction_request_id'
		);
	}
}
