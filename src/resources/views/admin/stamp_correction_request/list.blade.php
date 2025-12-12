@extends('layouts.admin')

@section('title', '修正申請（承認待ち）')

@section('content')
<h1>修正申請（承認待ち）</h1>

@if (session('status'))
<p>{{ session('status') }}</p>
@endif

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
		@foreach ($requests as $r)
		<tr>
			<td>{{ $r->created_at->format('Y-m-d H:i') }}</td>
			<td>{{ $r->attendance->user->name }}</td>
			<td>{{ $r->attendance->work_date->format('Y-m-d') }}</td>
			<td>
				<a href="{{ route('admin.stamp_correction_request.show', $r) }}">詳細</a>
			</td>
		</tr>
		@endforeach
	</tbody>
</table>
@endsection
