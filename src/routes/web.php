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
use App\Http\Controllers\Admin\StampCorrectionRequestController as AdminStampCorrectionRequestController;

// ----------------------------------------
// ルート：ログイン状態によるトップ画面振り分け
// ----------------------------------------
Route::get('/', function () {
	if (Auth::check()) {
		// ログイン済みなら打刻画面へ
		return redirect()->route('attendance.index');
	}

	// ゲストならログイン画面へ
	return redirect()->route('login');
});

// ----------------------------------------
// 一般ユーザー認証（Fortify + 独自Controller）
// ----------------------------------------
// ※ GET /login は Fortify::loginView が担当
// ※ GET /register は Fortify::registerView が担当

Route::middleware('guest')->group(function () {
	// ログイン（POST）
	Route::post('/login', [UserLoginController::class, 'login'])
		->name('login');

	// 会員登録（POST）
	Route::post('/register', [RegisterController::class, 'store'])
		->name('register');
});

// ----------------------------------------
// 一般ユーザー側（要ログイン）
// ----------------------------------------
Route::middleware(['auth'])->group(function () {
	// PG03 出勤登録画面（打刻） /attendance
	Route::get('/attendance', [UserAttendanceController::class, 'index'])
		->name('attendance.index');

	// 打刻アクション
	Route::post('/attendance/clock-in', [UserAttendanceController::class, 'clockIn'])
		->name('attendance.clock_in');
	Route::post('/attendance/break-in', [UserAttendanceController::class, 'breakIn'])
		->name('attendance.break_in');
	Route::post('/attendance/break-out', [UserAttendanceController::class, 'breakOut'])
		->name('attendance.break_out');
	Route::post('/attendance/clock-out', [UserAttendanceController::class, 'clockOut'])
		->name('attendance.clock_out');

	// PG04 勤怠一覧 /attendance/list
	Route::get('/attendance/list', [AttendanceListController::class, 'index'])
		->name('attendance.list');

	// PG05 勤怠詳細 /attendance/detail/{attendance}
	Route::get('/attendance/detail/{attendance}', [AttendanceDetailController::class, 'show'])
		->name('attendance.detail');

	// 勤怠修正申請（一般ユーザー側から申請）
	Route::post(
		'/attendance/detail/{attendance}/request',
		[UserStampCorrectionRequestController::class, 'store']
	)->name('stamp_correction_request.store');

	// PG06 申請一覧（一般ユーザー） /stamp_correction_request/list
	Route::get(
		'/stamp_correction_request/list',
		[UserStampCorrectionRequestController::class, 'indexForUser']
	)->name('stamp_correction_request.user_index');

	// 申請詳細（ユーザー）
	Route::get(
		'/stamp-correction-request/{stampCorrectionRequest}',
		[UserStampCorrectionRequestController::class, 'showForUser']
	)->name('stamp_correction_request.user_show');
});

// ----------------------------------------
// 管理者側
// ----------------------------------------
Route::prefix('admin')->name('admin.')->group(function () {

	// 管理者ログイン前（PG07）
	Route::middleware('guest')->group(function () {
		Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
		Route::post('/login', [AdminLoginController::class, 'authenticate'])->name('login.perform');
	});

	// 管理者ログイン後
	Route::middleware(['auth', 'can:is-admin'])->group(function () {
		Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

		Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.list');
		Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'show'])->name('attendance.detail');
		Route::put('/attendance/{attendance}', [AdminAttendanceController::class, 'update'])->name('attendance.update');

		Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.list');
		Route::get('/attendance/staff/{user}', [StaffAttendanceController::class, 'index'])->name('attendance.staff');

		Route::get('/stamp_correction_request/list', [AdminStampCorrectionRequestController::class, 'index'])->name('stamp_correction_request.index');
		Route::get('/stamp_correction_request/approve/{request}', [AdminStampCorrectionRequestController::class, 'show'])->name('stamp_correction_request.show');
		Route::post('/stamp_correction_request/approve/{request}', [AdminStampCorrectionRequestController::class, 'approve'])->name('stamp_correction_request.approve');
	});
});
