@extends('layouts.user')

@section('title', '勤怠一覧')

@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endpush

@section('content')
<div class="attendance-list-page">
	<h1 class="page-title">勤怠一覧</h1>

	{{-- 月ナビ --}}
	<div class="attendance-list-month-nav">
		<a href="{{ route('attendance.list', ['month' => $prevMonthParam]) }}" class=" month-nav-button is-prev">
			<img src="{{ asset('images/icon_arrow_left.svg') }}" alt="" class="month-nav-arrow-icon">
			<span class="month-nav-label">前月</span>
		</a>

		<div class="month-nav-current">
			<img src="{{ asset('images/icon_calendar.svg') }}"
				alt=""
				class="month-nav-icon">
			<span class="month-nav-text">{{ $targetMonth->format('Y/m') }}</span>
		</div>

		<a href="{{ route('attendance.list', ['month' => $nextMonthParam]) }}" class=" month-nav-button is-next">
			<span class="month-nav-label">翌月</span>
			<img src="{{ asset('images/icon_arrow_right.svg') }}" alt="" class="month-nav-arrow-icon">
		</a>
	</div>

	@php
	// 分 → "H:MM" のフォーマット（null の場合は空文字）
	$formatMinutes = function ($minutes) {
	if (is_null($minutes)) {
	return '';
	}
	$hours = intdiv($minutes, 60);
	$mins = $minutes % 60;
	return sprintf('%d:%02d', $hours, $mins);
	};
	@endphp

	<div class="table-wrap">
		<table class="table">
			<thead>
				<tr>
					<th>日付</th>
					<th>出勤</th>
					<th>退勤</th>
					<th>休憩</th>
					<th>合計</th>
					<th>詳細</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($rows as $row)
				<tr>
					<td class="col-date">{{ $row['date_label'] }}</td>
					<td class="col-time">{{ $row['clock_in'] }}</td>
					<td class="col-time">{{ $row['clock_out'] }}</td>
					<td class="col-time">{{ $formatMinutes($row['break_minutes']) }}</td>
					<td class="col-time">{{ $formatMinutes($row['work_minutes']) }}</td>
					<td class="col-detail">
						@if (!empty($row['attendance_id']))
						<a href="{{ route('attendance.detail', ['attendance' => $row['attendance_id']]) }}" class="table-detail-link">
							詳細
						</a>
						@endif
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
@endsection
