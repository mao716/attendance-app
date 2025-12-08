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
			<form action="#" method="post" id="attendance-detail-form" class="attendance-detail-form">
				@csrf
				@endif

				{{-- 名前 --}}
				<div class="attendance-detail-row">
					<div class="cell cell-label">名前</div>
					<div class="cell cell-main1 name-value">
						{{ $user->name }}
					</div>
					<div class="cell cell-main2"></div>
					<div class="cell cell-main3"></div>
				</div>

				{{-- 日付 --}}
				<div class="attendance-detail-row attendance-detail-row-date">
					<div class="cell cell-label">日付</div>
					<div class="cell cell-main1">{{ $attendance->work_date->format('Y年') }}</div>
					<div class="cell cell-main2"></div>
					<div class="cell cell-main3">{{ $attendance->work_date->format('n月j日') }}</div>
				</div>


				{{-- 出勤・退勤 --}}
				<div class="attendance-detail-row">
					<div class="cell cell-label">出勤・退勤</div>

					<div class="cell cell-main1 time-block">
						@if ($isEditable)
						<input type="time" name="clock_in_at"
							class="attendance-detail-input-time"
							value="{{ old('clock_in_at', $clockInTime) }}">
						@else
						<span class="attendance-detail-time">{{ $clockInTime }}</span>
						@endif
					</div>

					<div class="cell cell-main2">〜</div>

					<div class="cell cell-main3 time-block">
						@if ($isEditable)
						<input type="time" name="clock_out_at"
							class="attendance-detail-input-time"
							value="{{ old('clock_out_at', $clockOutTime) }}">
						@else
						<span class="attendance-detail-time">{{ $clockOutTime }}</span>
						@endif
					</div>
				</div>

				{{-- 休憩・休憩2 --}}
				@foreach ($breakRows as $index => $row)
				<div class="attendance-detail-row">
					<div class="cell cell-label">
						{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
					</div>

					{{-- 左：休憩開始 --}}
					@php
					// 元データから表示用の値を作る（--:-- は空にする）
					$rawStart = $row['start'] ?? '';
					if ($rawStart === '--:--') {
					$rawStart = '';
					}
					$startValue = old("breaks.$index.start", $rawStart);

					$rawEnd = $row['end'] ?? '';
					if ($rawEnd === '--:--') {
					$rawEnd = '';
					}
					$endValue = old("breaks.$index.end", $rawEnd);
					@endphp

					<div class="cell cell-main1 time-block">
						@if ($isEditable)
						<input
							type="time"
							name="breaks[{{ $index }}][start]"
							class="attendance-detail-input-time"
							value="{{ $startValue }}">
						@else
						@if ($rawStart !== '')
						<span class="attendance-detail-time">{{ $rawStart }}</span>
						@endif
						@endif
					</div>

					{{-- 中央：〜 は常に表示 --}}
					<div class="cell cell-main2">
						<span class="attendance-detail-tilde">〜</span>
					</div>

					{{-- 右：休憩終了 --}}
					<div class="cell cell-main3 time-block">
						@if ($isEditable)
						<input
							type="time"
							name="breaks[{{ $index }}][end]"
							class="attendance-detail-input-time"
							value="{{ $endValue }}">
						@else
						@if ($rawEnd !== '')
						<span class="attendance-detail-time">{{ $rawEnd }}</span>
						@endif
						@endif
					</div>
				</div>
				@endforeach

				{{-- 備考 --}}
				<div class="attendance-detail-row">
					<div class="cell cell-label">備考</div>
					<div class="cell cell-full">
						@if ($isEditable)
						<div class="attendance-detail-note-wrap">
							<textarea
								name="reason"
								class="attendance-detail-textarea">{{ old('reason', $note) }}</textarea>
						</div>
						@else
						<p class="attendance-detail-note-text">
							{{ $note }}
						</p>
						@endif
					</div>
				</div>

				@if ($isEditable)
			</form>
			@endif
		</div> {{-- /.attendance-detail-card --}}

		{{-- カードの外に出したフッター --}}
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
			@if ($isEditable)
			{{-- form の外にあるけど、PG06 で使うとき用に form 属性だけ付けておく --}}
			<button type="submit" form="attendance-detail-form" class="attendance-detail-button">
				修正
			</button>
			@endif
			@endif
		</div>
	</div>
</div>
@endsection
