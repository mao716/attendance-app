<!DOCTYPE html>
<html lang="ja">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title>@yield('title')</title>

	{{-- CSS --}}
	<link rel="stylesheet" href="{{ asset('css/reset.css') }}">
	<link rel="stylesheet" href="{{ asset('css/common.css') }}">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">

	@stack('css')
</head>

<body class="@yield('bodyClass')">
	@hasSection('header')
	@yield('header')
	@else
	@include('components.header-guest')
	@endif

	<main class="main">
		@yield('content')
	</main>
	@stack('scripts')
</body>

</html>
