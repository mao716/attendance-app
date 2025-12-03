<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class LoginController extends Controller
{
	public function login(LoginRequest $request): RedirectResponse
	{
		// 入力された email / password を取得
		$credentials = $request->only('email', 'password');

		// 認証試行
		if (! Auth::attempt($credentials)) {
			return back()
				->withErrors(['login_error' => 'ログイン情報が登録されていません'])
				->withInput(); // email の old() を復元
		}

		// セッション固定攻撃対策
		$request->session()->regenerate();

		// ログイン後の遷移先 → 打刻画面 /attendance
		return redirect()->intended('/attendance');
	}
}
