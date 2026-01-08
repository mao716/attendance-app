<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;

class User extends Authenticatable implements MustVerifyEmail
{
	use HasFactory, Notifiable;

	public const ROLE_GENERAL = 1;
	public const ROLE_ADMIN = 2;

	protected $fillable = [
		'name',
		'email',
		'password',
		'role',
	];

	protected $hidden = [
		'password',
		'remember_token',
	];

	protected $casts = [
		'email_verified_at' => 'datetime',
		'password'          => 'hashed',
	];

	public function attendances()
	{
		return $this->hasMany(Attendance::class);
	}

	public function stampCorrectionRequests()
	{
		return $this->hasMany(StampCorrectionRequest::class);
	}
}
