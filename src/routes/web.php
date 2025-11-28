<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
	AuthRegisterController,
	AuthLoginController,
	AdminAuthLoginController,
	AttendanceController,
	AttendanceListController,
	AttendanceDetailController,
	StampCorrectionRequestController,
	AdminAttendanceController,
	AdminStaffController,
	AdminStaffAttendanceController,
	AdminStampCorrectionRequestController,
};

// 必要ならトップはログイン or 打刻画面にリダイレクト
Route::get('/', function () {
	return redirect()->route('login');
});

// ========================
// 一般ユーザー側
// ========================

// 未ログイン時
Route::middleware('guest')->group(function () {
	// 会員登録
	Route::get('/register', [AuthRegisterController::class, 'showRegisterForm'])->name('register');
	Route::post('/register', [AuthRegisterController::class, 'register'])->name('register.perform');

	// ログイン
	Route::get('/login', [AuthLoginController::class, 'showLoginForm'])->name('login');
	Route::post('/login', [AuthLoginController::class, 'authenticate'])->name('login.perform');
});

// ログアウト
Route::post('/logout', [AuthLoginController::class, 'logout'])->name('logout');

// ログイン必須（一般ユーザー）
Route::middleware(['auth', 'verified'])->group(function () {
	// PG03 出勤登録画面（打刻） /attendance
	Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

	// 打刻アクション
	Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock_in');
	Route::post('/attendance/break-in', [AttendanceController::class, 'breakIn'])->name('attendance.break_in');
	Route::post('/attendance/break-out', [AttendanceController::class, 'breakOut'])->name('attendance.break_out');
	Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock_out');

	// PG04 勤怠一覧 /attendance/list
	Route::get('/attendance/list', [AttendanceListController::class, 'index'])->name('attendance.list');

	// PG05 勤怠詳細 /attendance/detail/{id}
	Route::get('/attendance/detail/{attendance}', [AttendanceDetailController::class, 'show'])->name('attendance.detail');

	// 勤怠修正申請（一般ユーザー側から申請）
	Route::post(
		'/attendance/detail/{attendance}/request',
		[StampCorrectionRequestController::class, 'store']
	)->name('stamp_correction_request.store');

	// PG06 申請一覧（一般ユーザー） /stamp_correction_request/list
	Route::get(
		'/stamp_correction_request/list',
		[StampCorrectionRequestController::class, 'indexForUser']
	)->name('stamp_correction_request.user_index');
});

// ========================
// 管理者側
// ========================

Route::prefix('admin')->name('admin.')->group(function () {
	// 管理者ログイン前
	Route::middleware('guest')->group(function () {
		// PG07 管理者ログイン /admin/login
		Route::get('/login', [AdminAuthLoginController::class, 'showLoginForm'])->name('login');
		Route::post('/login', [AdminAuthLoginController::class, 'authenticate'])->name('login.perform');
	});

	// 管理者ログイン後（role=2 だけ通す想定のミドルウェア）
	Route::middleware(['auth', 'can:is-admin'])->group(function () {
		Route::post('/logout', [AdminAuthLoginController::class, 'logout'])->name('logout');

		// PG08 日次勤怠一覧 /admin/attendance/list
		Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.list');

		// PG09 日次勤怠詳細 /admin/attendance/{id}
		Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'show'])->name('attendance.detail');
		// 管理者による直接修正（基本設計の AdminAttendanceUpdateRequest 用）:contentReference[oaicite:1]{index=1}
		Route::put('/attendance/{attendance}', [AdminAttendanceController::class, 'update'])->name('attendance.update');

		// PG10 スタッフ一覧 /admin/staff/list
		Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.list');

		// PG11 スタッフ別月次勤怠 /admin/attendance/staff/{id}
		Route::get(
			'/attendance/staff/{user}',
			[AdminStaffAttendanceController::class, 'index']
		)->name('attendance.staff');

		// PG12 修正申請一覧（管理者） /stamp_correction_request/list
		Route::get(
			'/stamp_correction_request/list',
			[AdminStampCorrectionRequestController::class, 'index']
		)->name('stamp_correction_request.index');

		// PG13 修正申請承認画面 /stamp_correction_request/approve/{id}
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
