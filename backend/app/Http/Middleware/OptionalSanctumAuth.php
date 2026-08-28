<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Guard;
use Symfony\Component\HttpFoundation\Response;

class OptionalSanctumAuth
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $guard = app(Guard::class);

        $user = $guard($request);

        if ($user) {
            $request->setUserResolver(
                fn () => $user
            );
        }

        return $next($request);
    }
}
