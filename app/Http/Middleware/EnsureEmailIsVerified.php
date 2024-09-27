<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next): Response
    // {
    //     if (! $request->user() ||
    //         ($request->user() instanceof MustVerifyEmail &&
    //         ! $request->user()->hasVerifiedEmail())) {
    //         return response()->json(['message' => 'Your email address is not verified.'], 409);
    //     }

    //     return $next($request);
    // }
    

public function handle($request, Closure $next, ...$guards)
{
    if (in_array('api', $guards)) {
        EnsureFrontendRequestsAreStateful::class;
    }

    return $next($request);
}

}
