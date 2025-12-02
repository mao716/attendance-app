@extends('layouts.base')

@section('title', '会員登録')

@push('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<div class="auth-container">

	<h1>会員登録</h1>

	@if ($errors->any())
	<div class="auth-error">
		<ul>
			@foreach ($errors->all() as $error)
			<li>{{ $error }}</li>
			@endforeach
		</ul>
	</div>
	@endif

	<form method="POST" action="{{ route('register') }}">
		@csrf

		<div class="auth-field">
			<label for="name">名前</label>
			<input id="name" type="text" name="name">
		</div>

		<div class="auth-field">
			<label for="email">メールアドレス</label>
			<input id="email" type="email" name="email">
		</div>

		<div class="auth-field">
			<label for="password">パスワード</label>
			<input id="password" type="password" name="password">
		</div>

		<div class="auth-field">
			<label for="password_confirmation">パスワード確認</label>
			<input id="password_confirmation" type="password" name="password_confirmation">
		</div>

		<button class="btn btn-primary" type="submit">登録する</button>

		<div class="auth-link">
			<a href="{{ route('login') }}">ログインはこちら</a>
		</div>

	</form>

</div>
@endsection
