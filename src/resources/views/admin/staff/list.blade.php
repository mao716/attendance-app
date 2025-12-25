@extends('layouts.admin')

@section('title', 'スタッフ一覧')

@push('css')
<link rel="stylesheet" href="{{ asset('css/list-page.css') }}">
@endpush

@section('content')
<div class="list-page">
	<div class="list-page-container">
		<h1 class="page-title">スタッフ一覧</h1>

		<div class="table-wrap">
			<table class="table">
				<thead>
					<tr>
						<th>名前</th>
						<th>メールアドレス</th>
						<th>月次勤怠</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($users as $user)
					<tr>
						<td>{{ $user->name }}</td>
						<td>{{ $user->email }}</td>
						<td class="col-detail">
							<a href="{{ route('admin.attendance.staff', $user->id) }}"
								class="table-detail-link">
								詳細
							</a>
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

	</div>
</div>
@endsection
