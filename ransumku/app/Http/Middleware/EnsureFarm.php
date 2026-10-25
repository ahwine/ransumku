<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Pastikan user sudah punya data kandang; jika belum, arahkan ke onboarding. */
class EnsureFarm
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->farm) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
