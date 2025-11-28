<header class="site-header">
	<div class="site-header-inner">
		<div class="site-header-left">
			<a href="{{ url('/') }}" class="site-logo">AttendanceApp</a>
		</div>
		<nav class="site-nav">
			<ul class="site-nav-list">
				{{-- 一般ユーザー用 --}}
				<li class="site-nav-item">
					<a href="{{ route('login') }}" class="site-nav-link">ログイン</a>
				</li>
				<li class="site-nav-item">
					<a href="{{ route('register') }}" class="site-nav-link">会員登録</a>
				</li>
				{{-- 管理者ログインへの導線（必要なら） --}}
				<li class="site-nav-item">
					<a href="{{ url('/admin/login') }}" class="site-nav-link">管理者ログイン</a>
				</li>
			</ul>
		</nav>
	</div>
</header>
