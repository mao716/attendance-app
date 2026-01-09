@extends('layouts.admin')

@section('title', 'スタッフ別勤怠一覧')

@push('css')
<link rel="stylesheet" href="{{ asset('css/list-page.css') }}">
@endpush

@section('content')
<div class="list-page">
	<div class="list-page-container">
		<h1 class="page-title">{{ $user->name }}さんの勤怠</h1>

		<div class="attendance-list-month-nav">
			<a href="{{ route('admin.attendance.staff', [
				'id' => $user->id,
				'month' => $prevMonthParam,
				]) }}" class="month-nav-button is-prev">
				<img src="{{ asset('images/icon_arrow_left.svg') }}" alt="" class="month-nav-arrow-icon">
				<span class="month-nav-label">前月</span>
			</a>

			<div class="month-nav-current">
				<img src="{{ asset('images/icon_calendar.svg') }}"
					alt=""
					class="month-nav-icon">
				<span class="month-nav-text">{{ $targetMonth->format('Y/m') }}</span>
			</div>

			<a href="{{ route('admin.attendance.staff', [
				'id' => $user->id,
				'month' => $nextMonthParam,
				]) }}" class="month-nav-button is-next">
				<span class="month-nav-label">翌月</span>
				<img src="{{ asset('images/icon_arrow_right.svg') }}" alt="" class="month-nav-arrow-icon">
			</a>
		</div>

		@php
		// 分 → "H:MM"（null の場合は空文字）
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
						<td class="table-col-date">{{ $row['date_label'] }}</td>
						<td class="table-col-time">{{ $row['clock_in'] }}</td>
						<td class="table-col-time">{{ $row['clock_out'] }}</td>
						<td class="table-col-time">{{ $formatMinutes($row['break_minutes']) }}</td>
						<td class="table-col-time">{{ $formatMinutes($row['work_minutes']) }}</td>
						<td class="table-col-detail">
							@if (!empty($row['attendance_id']))
							<a class="table-detail-link"
								href="{{ route('admin.attendance.detail', $row['attendance_id']) }}">
								詳細
							</a>
							@endif
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

		<div class="list-footer">
			<a href="{{ route('admin.attendance.staff.csv', [
					'id' => $user->id,
					'month' => $targetMonth->format('Y-m'),
					]) }}"
				class="list-button">
				CSV出力
			</a>
		</div>
	</div>
</div>
@endsection
