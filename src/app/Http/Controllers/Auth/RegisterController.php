<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
	public function showRegisterForm(): View
	{
		return view('auth.register');
	}

	public function store(RegisterRequest $request, CreatesNewUsers $creator): RedirectResponse
	{
		$user = $creator->create($request->validated());

		Auth::login($user);

		return redirect()->route('attendance.index');
	}
}
