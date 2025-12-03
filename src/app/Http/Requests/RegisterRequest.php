<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
			'password_confirmation' => ['same:password'],
		];
    }

	public function messages(): array
	{
		return [
			'name.required' => 'お名前を入力してください',
			'name.max' => 'お名前は20文字以内で入力してください',

			'email.required' => 'メールアドレスを入力してください',
			'email.email' => 'メールアドレスの形式が正しくありません',
			'email.unique' => 'このメールアドレスは既に登録されています',

			'password.required' => 'パスワードを入力してください',
			'password.min' => 'パスワードは8文字以上で入力してください',

			'password_confirmation.same' => 'パスワードと一致しません',
		];
	}
}
