<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StopImpersonationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $founderId = $request->session()->get('impersonating_from');

        if (! $founderId) {
            abort(403);
        }

        $founder = User::findOrFail($founderId);

        if (! $founder->isFounder()) {
            $request->session()->forget('impersonating_from');
            abort(403);
        }

        $request->session()->forget('impersonating_from');

        Auth::login($founder);

        return redirect()->route('dashboard');
    }
}
