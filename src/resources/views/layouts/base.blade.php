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
	@if ($headerType === 'admin')
	@include('components.header-admin')
	@elseif ($headerType === 'user')
	@include('components.header-user')
	@else
	@include('components.header-guest')
	@endif
	<main class="main">
		@yield('content')
	</main>
</body>

</html>
