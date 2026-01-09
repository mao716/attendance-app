<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
	public function showLoginForm(): View
	{
		session(['url.intended' => route('admin.attendance.list')]);
		return view('admin.auth.login');
	}

	public function authenticate(AdminLoginRequest $request): RedirectResponse
	{
		$credentials = $request->only('email', 'password');

		$failedResponse = back()
			->withErrors(['login_error' => 'ログイン情報が登録されていません'])
			->withInput();

		if (!Auth::attempt($credentials)) {
			return $failedResponse;
		}

		$request->session()->regenerate();

		$user = Auth::user();

		if (!$user || (int) $user->role !== User::ROLE_ADMIN) {
			Auth::logout();

			$request->session()->invalidate();
			$request->session()->regenerateToken();

			return $failedResponse;
		}

		return redirect()->route('admin.stamp_correction_request.index');
	}

	public function logout(): RedirectResponse
	{
		Auth::logout();

		request()->session()->invalidate();
		request()->session()->regenerateToken();

		return redirect()->route('admin.login');
	}
}
