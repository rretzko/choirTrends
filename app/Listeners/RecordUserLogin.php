<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\UserLogin;
use Illuminate\Auth\Events\Login;
use Jenssegers\Agent\Agent;

class RecordUserLogin
{
    public function handle(Login $event): void
    {
        $agent = new Agent;

        $counter = UserLogin::query()
            ->where('user_id', $event->user->getAuthIdentifier())
            ->max('counter');

        UserLogin::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'ip_address' => request()->ip(),
            'os' => $agent->platform() ?: null,
            'browser' => $agent->browser() ?: null,
            'device' => $agent->isMobile() ? 'mobile' : 'desktop',
            'viewport' => request()->input('viewport'),
            'counter' => ($counter ?? 0) + 1,
            'created_at' => now(),
        ]);
    }
}
