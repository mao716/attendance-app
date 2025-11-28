<!DOCTYPE html>
<html lang="ja">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title>@yield('title')</title>

	{{-- CSS --}}
	<link rel="stylesheet" href="{{ asset('css/reset.css') }}">
	<link rel="stylesheet" href="{{ asset('css/common.css') }}">
	@stack('css')
</head>

<body>
	{{-- 各レイアウトがここにヘッダーを挿し込む --}}
	@yield('header')

	<main class="main">
		@yield('content')
	</main>

	@stack('js')
</body>

</html>
