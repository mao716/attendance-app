@extends('layouts.admin')

@section('title', '修正申請 詳細')

@section('content')
<h1>修正申請 詳細</h1>

@if ($errors->has('request'))
<p style="color:red">{{ $errors->first('request') }}</p>
@endif

<p>氏名：{{ $stampCorrectionRequest->attendance->user->name }}</p>
<p>勤務日：{{ $stampCorrectionRequest->attendance->work_date->format('Y-m-d') }}</p>

<h2>修正後</h2>
<p>出勤：{{ optional($stampCorrectionRequest->after_clock_in_at)->format('H:i') }}</p>
<p>退勤：{{ optional($stampCorrectionRequest->after_clock_out_at)->format('H:i') }}</p>
<p>休憩合計：{{ $stampCorrectionRequest->after_break_minutes }} 分</p>

<h3>休憩明細</h3>
<ul>
	@foreach ($stampCorrectionRequest->correctionBreaks as $b)
	<li>{{ $b->break_order }}：
		{{ $b->break_start_at->format('H:i') }}〜{{ $b->break_end_at->format('H:i') }}
	</li>
	@endforeach
</ul>

<form method="post" action="{{ route('admin.stamp_correction_request.approve', $stampCorrectionRequest) }}">
	@csrf
	<button type="submit">承認する</button>
</form>
@endsection
