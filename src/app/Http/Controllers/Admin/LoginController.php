<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
	/**
	 * 管理者ログイン画面（仮）
	 * PG07 実装時に Blade を返すように変更する。
	 */
	public function showLoginForm()
	{
		return 'admin login placeholder';
	}

	/**
	 * ログイン処理（仮）
	 * これも PG07 で本物に書き換える。
	 */
	public function authenticate(Request $request)
	{
		// とりあえず動くだけにしておく
		return 'admin authenticate placeholder';
	}
}
