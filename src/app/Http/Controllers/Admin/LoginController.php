<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
	public function showLoginForm(): View
	{
		return view('admin.auth.login');
	}

	public function authenticate(AdminLoginRequest $request): RedirectResponse
	{
		$credentials = $request->only('email', 'password');

		// 認証失敗（メール or パス不一致）
		if (! Auth::attempt($credentials)) {
			return back()
				->withErrors(['login_error' => 'ログイン情報が登録されていません'])
				->withInput();
		}

		// セッション固定攻撃対策
		$request->session()->regenerate();

		// 管理者チェック（role !== 2 は弾く）
		if ((int) Auth::user()->role !== 2) {
			Auth::logout();

			$request->session()->invalidate();
			$request->session()->regenerateToken();

			return back()
				->withErrors(['login_error' => 'ログイン情報が登録されていません'])
				->withInput();
		}

		// 管理者トップ（とりあえず申請一覧でOK）
		return redirect()->route('admin.stamp_correction_request.index');
	}

	public function logout(): RedirectResponse
	{
		Auth::logout();

		request()->session()->invalidate();
		request()->session()->regenerateToken();

		return redirect()->route('admin.attendance.list');
	}
}
