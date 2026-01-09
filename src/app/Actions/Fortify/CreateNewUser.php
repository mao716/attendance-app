<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
	public function create(array $input): User
	{
		Validator::make($input, [
			'name' => ['required', 'string', 'max:20'],
			'email' => ['required', 'email', Rule::unique('users', 'email')],
			'password' => ['required', 'string', 'min:8'],
		])->validate();

		Validator::make($input, [
			'password_confirmation' => ['required', 'same:password'],
		])->validate();

		return User::create([
			'name' => $input['name'],
			'email' => $input['email'],
			'password' => Hash::make($input['password']),
			'role' => User::ROLE_USER,
		]);
	}
}
