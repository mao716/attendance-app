<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Http\RedirectResponse;

class RegisterController extends Controller
{
	public function showRegisterForm()
	{
		return view('auth.register');
	}

	public function store(RegisterRequest $request, CreatesNewUsers $creator): RedirectResponse
	{
		$user = $creator->create($request->validated());

		auth()->login($user);

		return redirect('/attendance');
	}
}
