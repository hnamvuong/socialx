<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
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
    }
}
