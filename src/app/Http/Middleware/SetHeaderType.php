<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SetHeaderType
{
	public function handle(Request $request, Closure $next)
	{
		if (auth()->check()) {
			if (Gate::allows('is-admin')) {
				view()->share('headerType', 'admin');
			} else {
				view()->share('headerType', 'user');
			}
		} else {
			view()->share('headerType', 'guest');
		}

		return $next($request);
	}
}
