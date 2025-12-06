@extends('layouts.user')

@section('title', '勤怠詳細')

@push('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endpush

@section('content')
<div class="attendance-detail-page">
	<div class="attendance-detail">
		<h1 class="page-title">勤怠詳細</h1>

		<div class="attendance-detail-card">
			@if ($isEditable)
			{{-- PG06 でルート実装予定なので action は一旦 # にしておく --}}
			<form action="#" method="post" class="attendance-detail-form">
				@csrf
				@endif

				{{-- 名前 --}}
				<div class="attendance-detail-row">
					<div class="attendance-detail-label">名前</div>
					<div class="attendance-detail-value">
						{{ $user->name }}
					</div>
				</div>

				{{-- 日付 --}}
				<div class="attendance-detail-row">
					<div class="attendance-detail-label">日付</div>
					<div class="attendance-detail-value attendance-detail-date">
						<span class="attendance-detail-date-year">
							{{ $attendance->work_date->format('Y年') }}
						</span>
						<span class="attendance-detail-date-md">
							{{ $attendance->work_date->format('n月j日') }}
						</span>
					</div>
				</div>

				{{-- 出勤・退勤 --}}
				<div class="attendance-detail-row">
					<div class="attendance-detail-label">出勤・退勤</div>
					<div class="attendance-detail-value attendance-detail-time-range">
						@if ($isEditable)
						<input
							type="time"
							name="clock_in_at"
							class="attendance-detail-input-time"
							value="{{ old('clock_in_at', $clockInTime) }}">
						<span class="attendance-detail-tilde">〜</span>
						<input
							type="time"
							name="clock_out_at"
							class="attendance-detail-input-time"
							value="{{ old('clock_out_at', $clockOutTime) }}">
						@else
						<span class="attendance-detail-time">{{ $clockInTime }}</span>
						<span class="attendance-detail-tilde">〜</span>
						<span class="attendance-detail-time">{{ $clockOutTime }}</span>
						@endif
					</div>
				</div>

				{{-- 休憩・休憩2 --}}
				@foreach ($breakRows as $index => $row)
				<div class="attendance-detail-row">
					<div class="attendance-detail-label">
						{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
					</div>
					<div class="attendance-detail-value attendance-detail-time-range">
						@if ($isEditable)
						<input
							type="time"
							name="breaks[{{ $index }}][start]"
							class="attendance-detail-input-time"
							value="{{ old("breaks.$index.start", $row['start']) }}">
						<span class="attendance-detail-tilde">〜</span>
						<input
							type="time"
							name="breaks[{{ $index }}][end]"
							class="attendance-detail-input-time"
							value="{{ old("breaks.$index.end", $row['end']) }}">
						@else
						<span class="attendance-detail-time">{{ $row['start'] }}</span>
						<span class="attendance-detail-tilde">〜</span>
						<span class="attendance-detail-time">{{ $row['end'] }}</span>
						@endif
					</div>
				</div>
				@endforeach

				{{-- 備考 --}}
				<div class="attendance-detail-row attendance-detail-row-note">
					<div class="attendance-detail-label">備考</div>
					<div class="attendance-detail-value">
						@if ($isEditable)
						<textarea
							name="reason"
							class="attendance-detail-textarea">{{ old('reason', $note) }}</textarea>
						@else
						<p class="attendance-detail-note-text">
							{{ $note }}
						</p>
						@endif
					</div>
				</div>

				{{-- フッター（ボタン / メッセージ） --}}
				<div class="attendance-detail-footer">
					@if ($requestStatus === 'pending')
					<p class="attendance-detail-message is-pending">
						※承認待ちのため修正はできません。
					</p>
					@elseif ($requestStatus === 'approved')
					<p class="attendance-detail-status-badge">
						承認済み
					</p>
					@else
					<button type="submit" class="attendance-detail-button">
						修正
					</button>
					@endif
				</div>

				@if ($isEditable)
			</form>
			@endif
		</div>
	</div>
</div>
@endsection
