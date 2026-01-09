@extends('layouts.admin')

@section('title', '勤怠詳細')

@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endpush

@section('content')

@php
$attendance = $stampCorrectionRequest->attendance;
@endphp

<div class="attendance-detail-page">
	<div class="attendance-detail">
		<h1 class="page-title">勤怠詳細</h1>

		@if ($errors->has('request'))
		<p class="attendance-detail-message is-error">{{ $errors->first('request') }}</p>
		@endif

		<div class="attendance-detail-card">

			<div class="attendance-detail-row">
				<div class="cell cell-label">名前</div>
				<div class="cell cell-main1 name-value">
					{{ $stampCorrectionRequest->attendance?->user?->name ?? '-' }}
				</div>
				<div class="cell cell-main2"></div>
				<div class="cell cell-main3"></div>
			</div>

			<div class="attendance-detail-row attendance-detail-row-date">
				<div class="cell cell-label">日付</div>

				<div class="cell cell-main1">
					<span class="attendance-detail-date-year">
						{{ $attendance->work_date->format('Y年') }}
					</span>
				</div>

				<div class="cell cell-main2">
					<span class="attendance-detail-date-md">
						{{ $attendance->work_date->format('n月j日') }}
					</span>
				</div>

				<div class="cell cell-main3"></div>
			</div>

			<div class="attendance-detail-row">
				<div class="cell cell-label">出勤・退勤</div>

				<div class="cell cell-main1">
					<div class="time-block attendance-detail-time">
						{{ optional($stampCorrectionRequest->after_clock_in_at)->format('H:i') }}
					</div>
				</div>

				<div class="cell cell-main2">
					<span class="attendance-detail-tilde">〜</span>
				</div>

				<div class="cell cell-main3">
					<div class="time-block attendance-detail-time">
						{{ optional($stampCorrectionRequest->after_clock_out_at)->format('H:i') }}
					</div>
				</div>
			</div>

			@foreach ($breakRows as $index => $breakRow)
			<div class="attendance-detail-row">
				<div class="cell cell-label">
					{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
				</div>
				<div class="cell cell-main1">
					<span class="attendance-detail-time">
						{{ $breakRow['start'] ?? '-' }}
					</span>
				</div>
				<div class="cell cell-main2">
					<span class="attendance-detail-tilde">〜</span>
				</div>
				<div class="cell cell-main3">
					<span class="attendance-detail-time">
						{{ $breakRow['end'] ?? '-' }}
					</span>
				</div>
			</div>
			@endforeach

			<div class="attendance-detail-row">
				<div class="cell cell-label">備考</div>
				<div class="cell cell-full">
					<p class="attendance-detail-note-text">
						{{ $stampCorrectionRequest->reason ?? '-' }}
					</p>
				</div>
			</div>

		</div>

		<div class="attendance-detail-footer">
			@if ($stampCorrectionRequest->isPending())
			<form method="post" action="{{ route('admin.stamp_correction_request.approve', $stampCorrectionRequest) }}">
				@csrf
				<button type="submit" class="attendance-detail-button">承認する</button>
			</form>
			@else
			<span class="attendance-detail-status-badge">承認済み</span>
			@endif
		</div>

	</div>
</div>
@endsection
