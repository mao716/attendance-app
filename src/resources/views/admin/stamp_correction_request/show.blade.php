@extends('layouts.admin')

@section('title', '勤務詳細')

@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endpush

@section('content')
<div class="attendance-detail-page">
	<div class="attendance-detail">
		<h1 class="page-title">勤務詳細</h1>

		{{-- 上部エラー --}}
		@if ($errors->has('request'))
		<p class="attendance-detail-message is-error">{{ $errors->first('request') }}</p>
		@endif

		<div class="attendance-detail-card">
			{{-- 名前 --}}
			<div class="attendance-detail-row">
				<div class="cell cell-label">名前</div>
				<div class="cell cell-main1 cell-full name-value">
					{{ $stampCorrectionRequest->attendance?->user?->name ?? '-' }}
				</div>
				<div class="cell cell-main2"></div>
				<div class="cell cell-main3"></div>
			</div>

			{{-- 勤務日 --}}
			<div class="attendance-detail-row attendance-detail-row-date">
				<div class="cell cell-label">日付</div>
				<div class="cell cell-main1">
					{{ optional($stampCorrectionRequest->attendance?->work_date)->format('Y-m-d') ?? '-' }}
				</div>
				<div class="cell cell-main2"></div>
				<div class="cell cell-main3"></div>
			</div>

			{{-- 出勤〜退勤 --}}
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

			{{-- 休憩合計 --}}
			<div class="attendance-detail-row">
				<div class="cell cell-label">休憩合計</div>
				<div class="cell cell-main1 cell-full">
					{{ $stampCorrectionRequest->after_break_minutes }} 分
				</div>
				<div class="cell cell-main2"></div>
				<div class="cell cell-main3"></div>
			</div>

			{{-- 休憩明細（必要ならここも行で増やす） --}}
			{{-- とりあえずulのままでもOK。揃えたくなったらこの部分も row 形式にする --}}
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
