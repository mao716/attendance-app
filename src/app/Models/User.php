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
	public function attendances()
	{
		return $this->hasMany(Attendance::class);
	}

	public function stampCorrectionRequests()
	{
		return $this->hasMany(StampCorrectionRequest::class);
	}
}
