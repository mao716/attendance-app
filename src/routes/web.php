<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController as UserLoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\User\AttendanceController as UserAttendanceController;
use App\Http\Controllers\User\AttendanceListController;
use App\Http\Controllers\User\AttendanceDetailController;
use App\Http\Controllers\User\StampCorrectionRequestController as UserStampCorrectionRequestController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\StaffAttendanceCsvController;
use App\Http\Controllers\Admin\StampCorrectionRequestController as AdminStampCorrectionRequestController;

Route::get('/', function () {
	if (Auth::check()) {
		return redirect()->route('attendance.index');
	}

	return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
	Route::post('/login', [UserLoginController::class, 'login'])
		->name('login');

	Route::post('/register', [RegisterController::class, 'store'])
		->name('register');
});

Route::middleware(['auth', 'verified'])->group(function () {
	Route::get('/attendance', [UserAttendanceController::class, 'index'])
		->name('attendance.index');

	Route::post('/attendance/clock-in', [UserAttendanceController::class, 'clockIn'])
		->name('attendance.clock_in');
	Route::post('/attendance/break-in', [UserAttendanceController::class, 'breakIn'])
		->name('attendance.break_in');
	Route::post('/attendance/break-out', [UserAttendanceController::class, 'breakOut'])
		->name('attendance.break_out');
	Route::post('/attendance/clock-out', [UserAttendanceController::class, 'clockOut'])
		->name('attendance.clock_out');

	Route::get('/attendance/list', [AttendanceListController::class, 'index'])
		->name('attendance.list');

	Route::get('/attendance/detail/{attendance}', [AttendanceDetailController::class, 'show'])
		->name('attendance.detail');

	Route::post(
		'/attendance/detail/{attendance}/request',
		[UserStampCorrectionRequestController::class, 'store']
	)->name('stamp_correction_request.store');

	Route::get(
		'/stamp_correction_request/list',
		[UserStampCorrectionRequestController::class, 'indexForUser']
	)->name('stamp_correction_request.user_index');

	Route::get(
		'/stamp-correction-request/{stampCorrectionRequest}',
		[UserStampCorrectionRequestController::class, 'showForUser']
	)->name('stamp_correction_request.user_show');
});

Route::prefix('admin')->name('admin.')->group(function () {

	Route::middleware('guest')->group(function () {
		Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
		Route::post('/login', [AdminLoginController::class, 'authenticate'])->name('login.perform');
	});

	Route::middleware(['auth', 'can:is-admin'])->group(function () {
		Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

		Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.list');
		Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'show'])->name('attendance.detail');
		Route::put('/attendance/{attendance}', [AdminAttendanceController::class, 'update'])->name('attendance.update');

		Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.list');

		Route::get('/attendance/staff/{user}', [StaffAttendanceController::class, 'index'])->name('attendance.staff');

		Route::get('/attendance/staff/{user}/csv', [StaffAttendanceCsvController::class, 'download'])
			->name('attendance.staff.csv');

		Route::get('/stamp_correction_request/list', [AdminStampCorrectionRequestController::class, 'index'])->name('stamp_correction_request.index');
		Route::get('/stamp_correction_request/approve/{request}', [AdminStampCorrectionRequestController::class, 'show'])->name('stamp_correction_request.show');
		Route::post('/stamp_correction_request/approve/{request}', [AdminStampCorrectionRequestController::class, 'approve'])->name('stamp_correction_request.approve');
	});
});
