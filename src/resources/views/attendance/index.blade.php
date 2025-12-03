@extends('layouts.user')

@section('title', '勤怠登録')

@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endpush

@section('content')
<div class="attendance-wrapper">
	<section class="attendance-panel" aria-labelledby="attendance-heading">
		<p class="attendance-status-badge">
			{{ $statusLabel }}
		</p>

		<h1 id="attendance-heading" class="attendance-date">
			{{ $todayLabel }}
		</h1>

		<p class="attendance-time">
			{{ $timeLabel }}
		</p>

		<div class="attendance-button-area">

			@if ($status === \App\Models\Attendance::STATUS_OFF)
			<form method="POST" action="{{ route('attendance.clock_in') }}" class="attendance-form">
				@csrf
				<button type="submit" class="attendance-button">
					出勤
				</button>
			</form>

			@elseif ($status === \App\Models\Attendance::STATUS_WORKING)
			<form method="POST" action="{{ route('attendance.clock_out') }}" class="attendance-form">
				@csrf
				<button type="submit" class="attendance-button">
					退勤
				</button>
			</form>

			<form method="POST" action="{{ route('attendance.break_in') }}" class="attendance-form">
				@csrf
				<button type="submit" class="attendance-button attendance-button-secondary">
					休憩入
				</button>
			</form>

			@elseif ($status === \App\Models\Attendance::STATUS_BREAK)
			<form method="POST" action="{{ route('attendance.break_out') }}" class="attendance-form">
				@csrf
				<button type="submit" class="attendance-button attendance-button-secondary">
					休憩戻
				</button>
			</form>

			@elseif ($status === \App\Models\Attendance::STATUS_FINISHED)
			{{-- ここはボタンなし。下の「お疲れ様でした。」だけ表示 --}}
			@endif

		</div>

		@if ($status === \App\Models\Attendance::STATUS_FINISHED)
		<p class="attendance-message">お疲れ様でした。</p>
		@endif

	</section>
</div>
@endsection
