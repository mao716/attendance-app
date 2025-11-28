<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthLoginController extends Controller
{
	/**
	 * ログイン画面表示
	 */
	public function showLoginForm()
	{
		return view('auth.login');
	}
}
