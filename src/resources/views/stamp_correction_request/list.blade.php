@extends('layouts.user')

@section('title', '申請一覧')

@push('css')
<link rel="stylesheet" href="{{ asset('css/stamp-correction-list.css') }}">
@endpush

@section('content')
<div class="stamp-list-page">
	<div class="stamp-list">
		<h1 class="page-title">申請一覧</h1>

		{{-- タブ風ヘッダー（見た目だけでもOK） --}}
		<div class="stamp-list-tabs">
			<span class="tab-item is-active">承認待ち</span>
			<span class="tab-item">承認済み</span>
		</div>

		{{-- 承認待ち一覧 --}}
		<div class="stamp-list-section">
			<table class="stamp-list-table">
				<thead>
					<tr>
						<th>状態</th>
						<th>名前</th>
						<th>対象日</th>
						<th>申請理由</th>
						<th>申請日時</th>
						<th>詳細</th>
					</tr>
				</thead>
				<tbody>
					@forelse ($pendingRequests as $request)
					<tr>
						<td>承認待ち</td>
						<td>{{ $request->user->name }}</td>
						<td>
							{{-- attendances.work_date を日付で表示 --}}
							{{ optional($request->attendance->work_date)->format('Y/m/d') }}
						</td>
						<td>{{ $request->reason }}</td>
						<td>{{ $request->created_at->format('Y/m/d') }}</td>
						<td>
							{{-- 勤怠詳細（閲覧専用）へ --}}
							<a
								href="{{ route('attendance.detail', ['attendance' => $request->attendance_id]) }}"
								class="stamp-list-link">
								詳細
							</a>
						</td>
					</tr>
					@empty
					<tr>
						<td colspan="6" class="stamp-list-empty">
							承認待ちの申請はありません。
						</td>
					</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		{{-- 承認済み一覧 --}}
		<div class="stamp-list-section">
			<table class="stamp-list-table">
				<thead>
					<tr>
						<th>状態</th>
						<th>名前</th>
						<th>対象日</th>
						<th>申請理由</th>
						<th>申請日時</th>
						<th>詳細</th>
					</tr>
				</thead>
				<tbody>
					@forelse ($approvedRequests as $request)
					<tr>
						<td>承認済み</td>
						<td>{{ $request->user->name }}</td>
						<td>
							{{ optional($request->attendance->work_date)->format('Y/m/d') }}
						</td>
						<td>{{ $request->reason }}</td>
						<td>{{ $request->created_at->format('Y/m/d') }}</td>
						<td>
							<a
								href="{{ route('attendance.detail', ['attendance' => $request->attendance_id]) }}"
								class="stamp-list-link">
								詳細
							</a>
						</td>
					</tr>
					@empty
					<tr>
						<td colspan="6" class="stamp-list-empty">
							承認済みの申請はありません。
						</td>
					</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>
</div>
@endsection
