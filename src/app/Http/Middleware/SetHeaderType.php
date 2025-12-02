<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetHeaderType
{
	public function handle(Request $request, Closure $next)
	{
		// ★ いまは admin ガードが無いのでコメントアウト
		// if (auth('admin')->check()) {
		// view()->share('headerType', 'admin');
		// }

		if (auth()->check()) {
			view()->share('headerType', 'user');
		}

		else {
			view()->share('headerType', 'guest');
		}

		return $next($request);
	}
}
