<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(
            function (
                User $user,
                string $token
            ): string {
                $frontendUrl = rtrim(
                    (string) config('app.frontend_url'),
                    '/'
                );

                return $frontendUrl
                    .'/reset-password'
                    .'?token='.urlencode($token)
                    .'&email='.urlencode($user->email);
            }
        );

        VerifyEmail::createUrlUsing(
            function (User $user): string {
                $backendUrl = URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes(60),
                    [
                        'id' => $user->getKey(),
                        'hash' => sha1(
                            $user->getEmailForVerification()
                        ),
                    ]
                );

                return $backendUrl;
            }
        );

        Gate::before(
            function (
                User $user,
                string $ability
            ): ?bool {
                if ($user->hasRole('admin')) {
                    return true;
                }

                if ($user->hasPermission($ability)) {
                    return true;
                }

                return null;
            }
        );

        Gate::define(
            'view-admin',
            function (User $user): bool {
                return $user->hasAnyRole([
                    'moderator',
                ]);
            }
        );

        Gate::define(
            'review-reports',
            function (User $user): bool {
                return $user->hasPermission(
                    'report.review'
                );
            }
        );

        Gate::define(
            'suspend-users',
            function (User $user): bool {
                return $user->hasPermission(
                    'user.suspend'
                );
            }
        );
    }
}
