<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsFounderOrImpersonating
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->isFounder() && ! $request->session()->has('impersonating_from')) {
            abort(403);
        }

        return $next($request);
    }
}
