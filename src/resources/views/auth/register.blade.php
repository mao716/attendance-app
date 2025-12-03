@extends('layouts.base')

@section('title', '会員登録')

@push('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<div class="auth-container">

	<h1>会員登録</h1>

	<form method="POST" action="{{ route('register') }}">
		@csrf

		<div class="auth-field">
			<label for="name">名前</label>
			<input id="name" type="text" name="name" value="{{ old('name') }}">
			@error('name')
			<p class="error-text">{{ $message }}</p>
			@enderror
		</div>

		<div class="auth-field">
			<label for="email">メールアドレス</label>
			<input id="email" type="email" name="email" value="{{ old('email') }}">
			@error('email')
			<p class="error-text">{{ $message }}</p>
			@enderror
		</div>

		<div class="auth-field">
			<label for="password">パスワード</label>
			<input id="password" type="password" name="password">
			@error('password')
			<p class="error-text">{{ $message }}</p>
			@enderror
		</div>

		<div class="auth-field">
			<label for="password_confirmation">パスワード確認</label>
			<input id="password_confirmation" type="password" name="password_confirmation">
			@error('password_confirmation')
			<p class="error-text">{{ $message }}</p>
			@enderror
		</div>

		<button class="btn btn-primary" type="submit">登録する</button>

		<div class="auth-link">
			<a href="{{ route('login') }}">ログインはこちら</a>
		</div>

	</form>

</div>
@endsection
