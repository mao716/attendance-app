@extends('layouts.base')

@section('title', 'ログイン')

@push('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<div class="auth-container">

	<h1>ログイン</h1>

	@if ($errors->any())
	<div class="auth-error">
		<ul>
			@foreach ($errors->all() as $error)
			<li>{{ $error }}</li>
			@endforeach
		</ul>
	</div>
	@endif

	<form method="POST" action="{{ route('login') }}">
		@csrf

		<div class="auth-field">
			<label for="email">メールアドレス</label>
			<input id="email" type="email" name="email" required autofocus>
		</div>

		<div class="auth-field">
			<label for="password">パスワード</label>
			<input id="password" type="password" name="password" required>
		</div>

		<button class="btn btn-primary" type="submit">ログインする</button>

		<div class="auth-link">
			<a href="{{ route('register') }}">会員登録はこちら</a>
		</div>

	</form>

</div>
@endsection
