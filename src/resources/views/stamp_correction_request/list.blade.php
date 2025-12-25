@extends('layouts.user')

@section('title', '申請一覧')

@push('css')
<link rel="stylesheet" href="{{ asset('css/stamp-correction-request.css') }}">
@endpush

@section('content')
@php
$tab = request('tab', 'pending');
if (!in_array($tab, ['pending', 'approved'], true)) {
$tab = 'pending';
}

$isPendingTab = $tab === 'pending';
@endphp
<div class="list-page">
	<div class="list-page-container">
		<h1 class="page-title">申請一覧</h1>

		{{-- タブナビゲーション --}}
		<div class="request-tabs">
			<a
				href="{{ route('stamp_correction_request.user_index', ['tab' => 'pending']) }}"
				class="request-tab{{ $isPendingTab ? ' is-active' : '' }}">
				承認待ち
			</a>
			<a
				href="{{ route('stamp_correction_request.user_index', ['tab' => 'approved']) }}"
				class="request-tab{{ !$isPendingTab ? ' is-active' : '' }}">
				承認済み
			</a>
		</div>

		<div class="table-wrap">
			<table class="table">
				<thead>
					<tr>
						<th>状態</th>
						<th>名前</th>
						<th class="table-col-date">対象日時</th>
						<th>申請理由</th>
						<th class="table-col-date">申請日時</th>
						<th class="table-col-detail">詳細</th>
					</tr>
				</thead>
				<tbody>
					@php
					/** @var \Illuminate\Support\Collection $list */
					$list = $isPendingTab ? $pendingRequests : $approvedRequests;
					@endphp

					@forelse ($list as $requestRow)
					<tr>
						{{-- 状態（モデルのアクセサ） --}}
						<td>{{ $requestRow->status_label }}</td>

						{{-- 名前 --}}
						<td>{{ optional($requestRow->user)->name }}</td>

						{{-- 対象日時：勤怠の勤務日を表示（なければハイフン） --}}
						<td class="table-col-date">
							@if ($requestRow->attendance && $requestRow->attendance->work_date)
							{{ \Illuminate\Support\Carbon::parse($requestRow->attendance->work_date)->format('Y/m/d') }}
							@else
							-
							@endif
						</td>

						{{-- 申請理由（長すぎる場合は少しだけ切る） --}}
						<td>
							{{ \Illuminate\Support\Str::limit($requestRow->reason, 13) }}
						</td>

						{{-- 申請日時：作成日時 --}}
						<td class="table-col-date">
							{{ $requestRow->created_at->format('Y/m/d') }}
						</td>

						{{-- 詳細：勤怠詳細画面（PG05）へ --}}
						<td class="table-col-detail">
							@if ($requestRow->attendance)
							<a
								href="{{ route('attendance.detail', [
									'attendance'   => $requestRow->attendance->id,
									'from_request' => 1,
									'request_id'   => $requestRow->id,
									]) }}"
								class="table-detail-link">
								詳細
							</a>

							@else
							-
							@endif
						</td>
					</tr>
					@empty
					<tr>
						<td colspan="6">
							@if ($isPendingTab)
							承認待ちの修正申請はありません。
							@else
							承認済みの修正申請はありません。
							@endif
						</td>
					</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>
</div>
@endsection
