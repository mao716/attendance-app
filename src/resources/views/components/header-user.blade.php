<header class="site-header">
	<div class="site-header-inner">
		<div class="site-header-left">
			<a href="{{ url('/attendance') }}" class="site-logo">
				<img src="{{ asset('images/header-logo.png') }}" alt="ロゴ">
			</a>
		</div>

		<nav class="site-nav">
			<ul class="site-nav-list">
				{{-- 勤怠（打刻） --}}
				<li class="site-nav-item">
					<a href="{{ url('/attendance') }}" class="site-nav-link">勤怠</a>
				</li>

				{{-- 勤怠一覧 --}}
				<li class="site-nav-item">
					<a href="{{ url('/attendance/list') }}" class="site-nav-link">勤怠一覧</a>
				</li>

				{{-- 申請一覧（修正申請） --}}
				<li class="site-nav-item">
					<a href="{{ url('/stamp_correction_request/list') }}" class="site-nav-link">申請</a>
				</li>

				{{-- ログアウト（POST） --}}
				<li class="site-nav-item">
					<form method="POST" action="{{ route('logout') }}" class="site-nav-logout-form">
						@csrf
						<button type="submit" class="site-nav-link site-nav-link--logout">
							ログアウト
						</button>
					</form>
				</li>
			</ul>
		</nav>
	</div>
</header>
