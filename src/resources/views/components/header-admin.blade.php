<header class="site-header site-header-admin">
	<div class="site-header-inner">
		<div class="site-header-left">
			<a href="{{ url('/admin/attendance/list') }}" class="site-logo">AttendanceApp 管理</a>
		</div>

		<nav class="site-nav">
			<ul class="site-nav-list">
				<li class="site-nav-item">
					<a href="{{ url('/admin/attendance/list') }}" class="site-nav-link">日次勤怠</a>
				</li>
				<li class="site-nav-item">
					<a href="{{ url('/admin/staff/list') }}" class="site-nav-link">スタッフ一覧</a>
				</li>
				<li class="site-nav-item">
					<a href="{{ url('/stamp_correction_request/list') }}" class="site-nav-link">修正申請</a>
				</li>
			</ul>
		</nav>

		<div class="site-header-right">
			<span class="site-user-name">
				管理者：{{ Auth::user()->name ?? 'ADMIN' }}
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
