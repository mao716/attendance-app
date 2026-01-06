@extends('layouts.admin')

@section('title', '勤怠詳細')

@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endpush

@section('content')

@php
$canEdit = $isEditable && $requestStatus !== 'pending';
@endphp

<div class="attendance-detail-page">
	<div class="attendance-detail">
		<h1 class="page-title">勤怠詳細</h1>

		@if (session('success'))
		<div class="flash-message is-success">
			{{ session('success') }}
		</div>
		@endif

		@if (session('error'))
		<div class="flash-message is-error">
			{{ session('error') }}
		</div>
		@endif

		<div class="attendance-detail-card">

			<form
				action="{{ route('admin.attendance.update', ['attendance' => $attendance->id]) }}"
				method="post"
				id="attendance-detail-form"
				class="attendance-detail-form">
				@csrf
				@method('PUT')

				{{-- 名前 --}}
				<div class="attendance-detail-row">
					<div class="cell cell-label">名前</div>
					<div class="cell cell-main1 name-value">{{ $user->name }}</div>
					<div class="cell cell-main2"></div>
					<div class="cell cell-main3"></div>
				</div>

				{{-- 日付 --}}
				<div class="attendance-detail-row attendance-detail-row-date">
					<div class="cell cell-label">日付</div>

					<div class="cell cell-main1">
						<span class="attendance-detail-date-year">{{ $attendance->work_date->format('Y年') }}</span>
					</div>

					<div class="cell cell-main2">
						<span class="attendance-detail-date-md">{{ $attendance->work_date->format('n月j日') }}</span>
					</div>

					<div class="cell cell-main3"></div>
				</div>

				{{-- 出勤・退勤 --}}
				<div class="attendance-detail-row {{ ($errors->has('clock_in_at') || $errors->has('clock_out_at')) ? 'has-error' : '' }}">
					<div class="cell cell-label">出勤・退勤</div>

					<div class="cell cell-main1 time-block">
						@if ($canEdit)
						<div class="field-stack">
							<input
								type="time"
								name="clock_in_at"
								class="attendance-detail-input-time"
								value="{{ old('clock_in_at', $clockInTime) }}"
								@disabled(!$canEdit)>
						</div>
						@else
						<span class="attendance-detail-time">{{ $clockInTime ?? '--:--' }}</span>
						@endif
					</div>

					<div class="cell cell-main2">
						<span class="attendance-detail-tilde">〜</span>
					</div>

					<div class="cell cell-main3 time-block">
						@if ($canEdit)
						<div class="field-stack">
							<input
								type="time"
								name="clock_out_at"
								class="attendance-detail-input-time"
								value="{{ old('clock_out_at', $clockOutTime) }}"
								@disabled(!$canEdit)>
						</div>
						@else
						<span class="attendance-detail-time">{{ $clockOutTime ?? '--:--' }}</span>
						@endif
					</div>
				</div>

				@if ($errors->has('clock_in_at') || $errors->has('clock_out_at'))
				<div class="attendance-detail-row attendance-detail-row-error">
					<div class="cell cell-label"></div>
					<div class="cell cell-error-full">
						<p class="field-error-inline">
							{{ $errors->first('clock_in_at') ?? $errors->first('clock_out_at') }}
						</p>
					</div>
				</div>
				@endif

				{{-- 休憩 --}}
				@foreach ($breakRows as $index => $row)
				@php
				$hasBreakError = $errors->has("breaks.$index.start") || $errors->has("breaks.$index.end");
				@endphp

				<div class="attendance-detail-row {{ $hasBreakError ? 'has-error' : '' }}">
					<div class="cell cell-label">
						{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
					</div>

					<div class="cell cell-main1 time-block">
						@if ($canEdit)
						<input
							type="time"
							name="breaks[{{ $index }}][start]"
							class="attendance-detail-input-time"
							value="{{ old("breaks.$index.start", $row['start']) }}"
							@disabled(!$canEdit)>
						@else
						<span class="attendance-detail-time">{{ $row['start'] ?? '--:--' }}</span>
						@endif
					</div>

					<div class="cell cell-main2">
						<span class="attendance-detail-tilde">〜</span>
					</div>

					<div class="cell cell-main3 time-block">
						@if ($canEdit)
						<input
							type="time"
							name="breaks[{{ $index }}][end]"
							class="attendance-detail-input-time"
							value="{{ old("breaks.$index.end", $row['end']) }}"
							@disabled(!$canEdit)>
						@else
						<span class="attendance-detail-time">{{ $row['end'] ?? '--:--' }}</span>
						@endif
					</div>
				</div>

				@if ($hasBreakError)
				<div class="attendance-detail-row attendance-detail-row-error">
					<div class="cell cell-label"></div>
					<div class="cell cell-error-full">
						<p class="field-error-inline">
							{{ $errors->first("breaks.$index.end") ?: $errors->first("breaks.$index.start") }}
						</p>
					</div>
				</div>
				@endif
				@endforeach

				{{-- 備考 --}}
				<div class="attendance-detail-row {{ $errors->has('reason') ? 'has-error' : '' }}">
					<div class="cell cell-label">備考</div>
					<div class="cell cell-full">
						@if ($canEdit)
						<textarea
							name="reason"
							class="attendance-detail-textarea"
							@disabled(!$canEdit)>{{ old('reason', $noteForForm) }}</textarea>
						@else
						<p class="attendance-detail-note-text">{{ $noteForDisplay }}</p>
						@endif
					</div>
				</div>

				@if ($errors->has('reason'))
				<div class="attendance-detail-row attendance-detail-row-error">
					<div class="cell cell-label"></div>
					<div class="cell cell-error-full">
						<p class="field-error-inline">{{ $errors->first('reason') }}</p>
					</div>
				</div>
				@endif

			</form>

		</div>

		{{-- カードの外のフッター --}}
		<div class="attendance-detail-footer">
			@if ($requestStatus === 'pending')
			<p class="attendance-detail-message is-pending">※承認待ちのため修正はできません。</p>
			@elseif ($requestStatus === 'approved')
			<p class="attendance-detail-status-badge">承認済み</p>
			@else
			@if ($canEdit)
			<button
				type="submit"
				form="attendance-detail-form"
				class="attendance-detail-button">修正</button>
			@endif
			@endif
		</div>

	</div>
</div>
@endsection
