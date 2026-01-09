@extends('layouts.base')

@section('title', 'メール認証')

@push('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<div class="auth-container">

	<h1>メール認証</h1>

	<p>登録したメールアドレスに認証リンクを送信しました。</p>
	<p>メール内のリンクをクリックして完了してください。</p>

	@if (session('status') === 'verification-link-sent')
	<p class="auth-success">認証メールを再送しました。</p>
	@endif

	<form method="POST" action="{{ route('verification.send') }}">
		@csrf
		<button type="submit" class="button button-primary">認証メールを再送する</button>
	</form>

	<form method="POST" action="{{ route('logout') }}" style="margin-top: 12px;">
		@csrf
		<button type="submit" class="btn-primary">ログアウト</button>
	</form>

</div>

@endsection
