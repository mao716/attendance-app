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

// ログイン（POST） ※ GET /login は Fortify::loginView が担当
Route::post('/login', [UserLoginController::class, 'login'])
	->name('login');

// 会員登録（POST） ※ GET /register は Fortify::registerView が担当
Route::post('/register', [RegisterController::class, 'store'])
	->name('register');

// ----------------------------------------
// 一般ユーザー側（要ログイン）
// ----------------------------------------
Route::middleware(['auth'])->group(function () {
	// PG03 出勤登録画面（打刻） /attendance
	Route::get('/attendance', [UserAttendanceController::class, 'index'])
		->name('attendance.index');

	// 打刻アクション
	Route::post('/attendance/clock-in', [UserAttendanceController::class, 'clockIn'])
		->name('attendance.clock-in');
	Route::post('/attendance/break-in', [UserAttendanceController::class, 'breakIn'])
		->name('attendance.break-in');
	Route::post('/attendance/break-out', [UserAttendanceController::class, 'breakOut'])
		->name('attendance.break-out');
	Route::post('/attendance/clock-out', [UserAttendanceController::class, 'clockOut'])
		->name('attendance.clock-out');


	// PG04 勤怠一覧 /attendance/list
	Route::get('/attendance/list', [AttendanceListController::class, 'index'])
		->name('attendance.list');

	// PG05 勤怠詳細 /attendance/detail/{id}
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
});

// ----------------------------------------
// 管理者側
// ----------------------------------------
Route::prefix('admin')->name('admin.')->group(function () {
	// 管理者ログイン前
	Route::middleware('guest')->group(function () {
		// PG07 管理者ログイン /admin/login
		Route::get('/login', [AdminLoginController::class, 'showLoginForm'])
			->name('login');
		Route::post('/login', [AdminLoginController::class, 'authenticate'])
			->name('login.perform');
	});

	// 管理者ログイン後（role = 2 のみ）
	Route::middleware(['auth', 'can:is-admin'])->group(function () {
		Route::post('/logout', [AdminLoginController::class, 'logout'])
			->name('logout');

		// PG08 日次勤怠一覧 /admin/attendance/list
		Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
			->name('attendance.list');

		// PG09 日次勤怠詳細 /admin/attendance/{id}
		Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'show'])
			->name('attendance.detail');

		// 管理者による直接修正 /admin/attendance/{id}
		Route::put('/attendance/{attendance}', [AdminAttendanceController::class, 'update'])
			->name('attendance.update');

		// PG10 スタッフ一覧 /admin/staff/list
		Route::get('/staff/list', [StaffController::class, 'index'])
			->name('staff.list');

		// PG11 スタッフ別月次勤怠 /admin/attendance/staff/{id}
		Route::get(
			'/attendance/staff/{user}',
			[StaffAttendanceController::class, 'index']
		)->name('attendance.staff');

		// PG12 修正申請一覧（管理者） /admin/stamp_correction_request/list
		Route::get(
			'/stamp_correction_request/list',
			[AdminStampCorrectionRequestController::class, 'index']
		)->name('stamp_correction_request.index');

		// PG13 修正申請承認画面 /admin/stamp_correction_request/approve/{id}
		Route::get(
			'/stamp_correction_request/approve/{request}',
			[AdminStampCorrectionRequestController::class, 'show']
		)->name('stamp_correction_request.show');

		// 承認ボタンのPOST
		Route::post(
			'/stamp_correction_request/approve/{request}',
			[AdminStampCorrectionRequestController::class, 'approve']
		)->name('stamp_correction_request.approve');
	});
});
