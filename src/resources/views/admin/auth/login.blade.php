@extends('layouts.base')

@section('title', '管理者ログイン')

@push('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<div class="auth-container">

	<h1>管理者ログイン</h1>

	<form method="POST" action="{{ route('login') }}" novalidate>
		@csrf
		<input type="hidden" name="login_type" value="admin">
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

		<button class="btn btn-primary" type="submit">管理者ログインする</button>
	</form>

</div>
@endsection
