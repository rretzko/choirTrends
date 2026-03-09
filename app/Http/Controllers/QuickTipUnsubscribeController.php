<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuickTipUnsubscribeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $user = User::findOrFail($request->query('user'));
        $user->update(['quick_tip_emails_enabled' => false]);

        return redirect()->route('login')->with('status', 'You have been unsubscribed from Quick Tips emails.');
    }
}
