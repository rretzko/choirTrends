<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictAssistantToDigitalPrograms
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isAssistant() && ! str_starts_with((string) $request->route()?->getName(), 'digital-programs.')) {
            return redirect()->route('digital-programs.index');
        }

        return $next($request);
    }
}
