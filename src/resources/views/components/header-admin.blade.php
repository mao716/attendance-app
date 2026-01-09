<header class="site-header site-header-admin">
	<div class="site-header-inner">
		<div class="site-header-left">
			<a href="{{ url('/admin/attendance/list') }}" class="site-logo">
				<img src="{{ asset('images/header-logo.png') }}" alt="ロゴ">
			</a>
		</div>

		<nav class="site-nav">
			<ul class="site-nav-list">

				<li class="site-nav-item">
					<a href="{{ route('admin.attendance.list') }}" class="site-nav-link">
						勤怠一覧
					</a>
				</li>

				<li class="site-nav-item">
					<a href="{{ route('admin.staff.list') }}" class="site-nav-link">
						スタッフ一覧
					</a>
				</li>

				<li class="site-nav-item">
					<a href="{{ route('stamp_correction_request.list') }}" class="site-nav-link">
						申請一覧
					</a>
				</li>

				<li class="site-nav-item">
					<form method="POST"
						action="{{ route('admin.logout') }}"
						class="site-nav-logout-form">
						@csrf
						<button type="submit"
							class="site-nav-link site-nav-link--logout">
							ログアウト
						</button>
					</form>
				</li>
			</ul>
		</nav>
	</div>
</header>
