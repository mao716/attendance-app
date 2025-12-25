@extends('layouts.admin')

@section('title', '勤怠一覧（管理者）')

@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endpush

@section('content')
<div class="list-page">
	<div class="list-page-container">
		<h1 class="page-title">{{ $targetDateLabel }}の勤怠</h1>

		{{-- 日ナビ（見た目はユーザー月ナビと同じCSSを流用） --}}
		<div class="attendance-list-month-nav">
			<a href="{{ route('admin.attendance.list', ['date' => $targetDate->copy()->subDay()->toDateString()]) }}"
				class="month-nav-button is-prev">
				<img src="{{ asset('images/icon_arrow_left.svg') }}" alt="" class="month-nav-arrow-icon">
				<span class="month-nav-label">前日</span>
			</a>

			<div class="month-nav-current">
				<img src="{{ asset('images/icon_calendar.svg') }}" alt="" class="month-nav-icon">
				<span class="month-nav-text">{{ $targetDate->format('Y/m/d') }}</span>
			</div>

			<a href="{{ route('admin.attendance.list', ['date' => $targetDate->copy()->addDay()->toDateString()]) }}"
				class="month-nav-button is-next">
				<span class="month-nav-label">翌日</span>
				<img src="{{ asset('images/icon_arrow_right.svg') }}" alt="" class="month-nav-arrow-icon">
			</a>
		</div>

		@php
		$formatMinutes = function ($minutes) {
		if (is_null($minutes)) {
		return '';
		}
		$hours = intdiv($minutes, 60);
		$mins = $minutes % 60;
		return sprintf('%d:%02d', $hours, $mins);
		};

		$formatTime = function ($time) {
		if (empty($time)) {
		return '';
		}
		return \Carbon\Carbon::parse($time)->format('H:i');
		};
		@endphp

		<div class="table-wrap">
			<table class="table">
				<thead>
					<tr>
						<th>名前</th>
						<th>出勤</th>
						<th>退勤</th>
						<th>休憩</th>
						<th>合計</th>
						<th>詳細</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($rows as $row)
					@php
					$user = $row['user'];
					$attendance = $row['attendance'];
					@endphp
					<tr>
						<td class="table-col-date">{{ $user->name }}</td>
						<td class="table-col-time">{{ $formatTime($attendance?->clock_in_at) }}</td>
						<td class="table-col-time">{{ $formatTime($attendance?->clock_out_at) }}</td>
						<td class="table-col-time">{{ $formatMinutes($attendance?->total_break_minutes) }}</td>
						<td class="table-col-time">{{ $formatMinutes($attendance?->working_minutes) }}</td>
						<td class="table-col-detail">
							@if ($attendance)
							<a href="{{ route('admin.attendance.detail', $attendance->id) }}" class="table-detail-link">詳細</a>
							@endif
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

	</div>
</div>
@endsection
