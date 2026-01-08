<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
	public function login(LoginRequest $request): RedirectResponse
	{
		$credentials = $request->only('email', 'password');

		if (! Auth::attempt($credentials)) {
			return back()
				->withErrors(['login_error' => 'ログイン情報が登録されていません'])
				->withInput();
		}

		$request->session()->regenerate();

		return redirect()->intended(route('attendance.index'));
	}
}
