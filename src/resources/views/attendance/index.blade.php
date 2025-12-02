@extends('layouts.base')

@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endpush

@section('title', '勤怠打刻')

@section('content')
<main class="attendance-page">

	{{-- 現在日時 --}}
	<section class="time-block">
		<p class="current-date">{{ $today->format('Y年m月d日 (D)') }}</p>
		<p class="current-time">{{ $now->format('H:i:s') }}</p>
	</section>

	{{-- 状態表示 --}}
	<section class="status-block">
		@if ($attendance)
		<p class="status-label">現在の状態：<strong>{{ $attendance->status_label }}</strong></p>
		@else
		<p class="status-label">現在の状態：<strong>勤務外</strong></p>
		@endif
	</section>

	{{-- ボタン --}}
	<section class="button-block">

		{{-- 出勤 --}}
		@if (! $attendance || $attendance->isNotStarted())
		<form action="{{ route('attendance.clockIn') }}" method="POST">
			@csrf
			<button class="btn btn-primary">出勤</button>
		</form>
		@endif

		{{-- 休憩開始 --}}
		@if ($attendance && $attendance->isWorking())
		<form action="{{ route('attendance.breakIn') }}" method="POST">
			@csrf
			<button class="btn btn-secondary">休憩開始</button>
		</form>
		@endif

		{{-- 休憩終了 --}}
		@if ($attendance && $attendance->isOnBreak())
		<form action="{{ route('attendance.breakOut') }}" method="POST">
			@csrf
			<button class="btn btn-secondary">休憩終了</button>
		</form>
		@endif

		{{-- 退勤 --}}
		@if ($attendance && $attendance->isWorking())
		<form action="{{ route('attendance.clockOut') }}" method="POST">
			@csrf
			<button class="btn btn-danger">退勤</button>
		</form>
		@endif
	</section>

	{{-- フラッシュメッセージ --}}
	@if (session('success'))
	<p class="flash-message">{{ session('success') }}</p>
	@endif

</main>
@endsection
