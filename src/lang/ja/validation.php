<?php

return [
	'required' => ':attributeを入力してください',
	'email' => ':attributeの形式が正しくありません',
	'min' => [
		'string' => ':attributeは:min文字以上で入力してください',
	],

	'attributes' => [
		'name' => 'お名前',
		'email' => 'メールアドレス',
		'password' => 'パスワード',
		'password_confirmation' => '確認用パスワード',
	],

	'custom' => [
		'password_confirmation' => [
			'required' => 'パスワードと一致しません',
			'same' => 'パスワードと一致しません',
		],
	],
];
