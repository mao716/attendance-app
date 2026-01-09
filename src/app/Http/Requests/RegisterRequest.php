<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class RegisterRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'name' => ['required', 'string', 'max:20'],
			'email' => ['required', 'email', 'unique:users,email'],
			'password' => ['required', 'string', 'min:8'],
			'password_confirmation' => ['nullable', 'string'],
		];
	}

	public function withValidator(Validator $validator): void
	{
		$validator->sometimes(
			'password_confirmation',
			['required', 'same:password'],
			function ($input) {
				$password = (string) ($input->password ?? '');
				return $password !== '' && mb_strlen($password) >= 8;
			}
		);
	}
}
