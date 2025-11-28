<header class="site-header">
	<div class="site-header-inner">
		<div class="site-header-left">
			<a href="{{ url('/attendance') }}" class="site-logo">AttendanceApp</a>
		</div>

		<nav class="site-nav">
			<ul class="site-nav-list">
				<li class="site-nav-item">
					<a href="{{ url('/attendance') }}" class="site-nav-link">打刻</a>
				</li>
				<li class="site-nav-item">
					<a href="{{ url('/attendance/list') }}" class="site-nav-link">勤怠一覧</a>
				</li>
				<li class="site-nav-item">
					<a href="{{ url('/stamp_correction_request/list') }}" class="site-nav-link">申請一覧</a>
				</li>
			</ul>
		</nav>

		<div class="site-header-right">
			<span class="site-user-name">
				{{ Auth::user()->name ?? 'USER' }}
			</span>
			<form method="POST" action="{{ route('logout') }}">
				@csrf
				<button type="submit" class="site-logout-button">
					ログアウト
				</button>
			</form>
		</div>
	</div>
</header>
