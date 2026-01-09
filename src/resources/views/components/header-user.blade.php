<header class="site-header">
	<div class="site-header-inner">
		<div class="site-header-left">
			<a href="{{ route('attendance.index') }}" class="site-logo">
				<img src="{{ asset('images/header-logo.png') }}" alt="ロゴ">
			</a>
		</div>

		<nav class="site-nav">
			<ul class="site-nav-list">

				<li class="site-nav-item">
					<a href="{{ route('attendance.index') }}" class="site-nav-link">
						勤怠
					</a>
				</li>

				<li class="site-nav-item">
					<a href="{{ route('attendance.list') }}" class="site-nav-link">
						勤怠一覧
					</a>
				</li>

				<li class="site-nav-item">
					<a href="{{ route('stamp_correction_request.list') }}" class="site-nav-link">
						申請
					</a>
				</li>

				<li class="site-nav-item">
					<form method="POST"
						action="{{ route('logout') }}"
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
