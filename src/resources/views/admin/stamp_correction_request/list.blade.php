@extends('layouts.admin')

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
$list = $isPendingTab ? $pendingRequests : $approvedRequests;
@endphp

<div class="request-list-page">
	<div class="request-list">
		<h1 class="page-title">申請一覧</h1>

		@if (session('status'))
		<p>{{ session('status') }}</p>
		@endif

		<div class="request-tabs">
			<a href="{{ route('admin.stamp_correction_request.index', ['tab' => 'pending']) }}"
				class="request-tab{{ $isPendingTab ? ' is-active' : '' }}">
				承認待ち
			</a>
			<a href="{{ route('admin.stamp_correction_request.index', ['tab' => 'approved']) }}"
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
						<th class="table-col-date">申請理由</th>
						<th class="table-col-date">申請日時</th>
						<th class="table-col-detail">詳細</th>
					</tr>
				</thead>
				<tbody>
					@forelse ($list as $requestRow)
					<tr>
						<td>{{ $requestRow->status_label }}</td>
						<td>{{ $requestRow->attendance?->user?->name ?? '-' }}</td>
						<td class="table-col-date">{{ optional($requestRow->attendance?->work_date)->format('Y/m/d') ?? '-' }}</td>
						<td>{{ \Illuminate\Support\Str::limit($requestRow->reason, 13) }}</td>
						<td class="table-col-date">{{ $requestRow->created_at->format('Y/m/d') }}</td>
						<td class="table-col-detail">
							<a class="table-detail-link" href="{{ route('admin.stamp_correction_request.show', $requestRow) }}">
								詳細
							</a>
						</td>
					</tr>
					@empty
					<tr>
						<td colspan="5">
							{{ $isPendingTab ? '承認待ちの申請はありません' : '承認済みの申請はありません' }}
						</td>
					</tr>
					@endforelse
				</tbody>
			</table>
		</div>

	</div>
</div>
@endsection
