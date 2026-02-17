<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\UserLogin;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Dashboard extends Component
{
    public function render(): View
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $totalLogins = UserLogin::query()->count();
        $uniqueUsers = UserLogin::query()->distinct('user_id')->count('user_id');

        $recentLogins = UserLogin::query()
            ->with('user')
            ->latest('created_at')
            ->limit(50)
            ->get();

        $byOs = UserLogin::query()
            ->selectRaw('os, count(*) as total')
            ->groupBy('os')
            ->orderByDesc('total')
            ->get();

        $byBrowser = UserLogin::query()
            ->selectRaw('browser, count(*) as total')
            ->groupBy('browser')
            ->orderByDesc('total')
            ->get();

        $byDevice = UserLogin::query()
            ->selectRaw('device, count(*) as total')
            ->groupBy('device')
            ->orderByDesc('total')
            ->get();

        return view('livewire.founder.dashboard', [
            'totalLogins' => $totalLogins,
            'uniqueUsers' => $uniqueUsers,
            'recentLogins' => $recentLogins,
            'byOs' => $byOs,
            'byBrowser' => $byBrowser,
            'byDevice' => $byDevice,
        ])->layout('components.layouts.app', ['title' => __('Founder Dashboard')]);
    }
}
