@extends('layouts.admin')

@section('title', '修正申請一覧')

@section('content')
<h1>修正申請一覧</h1>

@if (session('status'))
<p>{{ session('status') }}</p>
@endif

{{-- タブ --}}
<div style="margin-bottom: 16px;">
	<button type="button" onclick="showTab('pending')">承認待ち</button>
	<button type="button" onclick="showTab('approved')">承認済み</button>
</div>

{{-- 承認待ち --}}
<div id="tab-pending">
	<h2>承認待ち</h2>

	<table>
		<thead>
			<tr>
				<th>申請日</th>
				<th>氏名</th>
				<th>勤務日</th>
				<th>詳細</th>
			</tr>
		</thead>
		<tbody>
			@forelse ($pendingRequests as $r)
			<tr>
				<td>{{ $r->created_at->format('Y-m-d H:i') }}</td>
				<td>{{ $r->attendance->user->name }}</td>
				<td>{{ $r->attendance->work_date->format('Y-m-d') }}</td>
				<td>
					<a href="{{ route('admin.stamp_correction_request.show', $r) }}">詳細</a>
				</td>
			</tr>
			@empty
			<tr>
				<td colspan="4">承認待ちの申請はありません</td>
			</tr>
			@endforelse
		</tbody>
	</table>
</div>

{{-- 承認済み --}}
<div id="tab-approved" style="display:none;">
	<h2>承認済み</h2>

	<table>
		<thead>
			<tr>
				<th>申請日</th>
				<th>氏名</th>
				<th>勤務日</th>
				<th>詳細</th>
			</tr>
		</thead>
		<tbody>
			@forelse ($approvedRequests as $r)
			<tr>
				<td>{{ $r->created_at->format('Y-m-d H:i') }}</td>
				<td>{{ $r->attendance->user->name }}</td>
				<td>{{ $r->attendance->work_date->format('Y-m-d') }}</td>
				<td>
					<a href="{{ route('admin.stamp_correction_request.show', $r) }}">詳細</a>
				</td>
			</tr>
			@empty
			<tr>
				<td colspan="4">承認済みの申請はありません</td>
			</tr>
			@endforelse
		</tbody>
	</table>
</div>

<script>
	function showTab(type) {
		document.getElementById('tab-pending').style.display =
			type === 'pending' ? 'block' : 'none';

		document.getElementById('tab-approved').style.display =
			type === 'approved' ? 'block' : 'none';
	}
</script>
@endsection
