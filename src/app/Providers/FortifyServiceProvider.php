<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse;

class FortifyServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		//
	}

	public function boot(): void
	{
		Fortify::createUsersUsing(CreateNewUser::class);
		Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
		Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
		Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
		Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

		RateLimiter::for('login', function (Request $request) {
			$throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

			return Limit::perMinute(5)->by($throttleKey);
		});

		RateLimiter::for('two-factor', function (Request $request) {
			return Limit::perMinute(5)->by($request->session()->get('login.id'));
		});

		Fortify::loginView(function () {
			return view('auth.login');
		});

		Fortify::registerView(function () {
			return view('auth.register');
		});

		Fortify::verifyEmailView(function () {
			return view('auth.verify-email');
		});

		Fortify::authenticateUsing(function (Request $request) {
			$user = User::where('email', $request->email)->first();

			if (! $user || ! Hash::check($request->password, $user->password)) {
				throw ValidationException::withMessages([
					'email' => ['ログイン情報が登録されていません'],
				]);
			}

			if ($request->input('login_type') === 'admin' && $user->role !== User::ROLE_ADMIN) {
				throw ValidationException::withMessages([
					'email' => ['ログイン情報が登録されていません'],
				]);
			}

			return $user;
		});

		$this->app->singleton(LoginResponse::class, function () {
			return new class implements LoginResponse {
				public function toResponse($request)
				{
					$user = auth()->user();

					if ($user && (int) $user->role === \App\Models\User::ROLE_ADMIN) {
						return redirect('/admin/attendance/list');
					}

					return redirect('/attendance');
				}
			};
		});
	}
}
