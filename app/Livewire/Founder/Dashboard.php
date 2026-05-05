<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class Dashboard extends Component
{
    public bool $showProgramModal = false;

    #[Computed]
    public function programDistribution(): Collection
    {
        $ranges = [
            ['label' => '0', 'min' => 0, 'max' => 0],
            ['label' => '1–5', 'min' => 1, 'max' => 5],
            ['label' => '6–15', 'min' => 6, 'max' => 15],
            ['label' => '16–30', 'min' => 16, 'max' => 30],
            ['label' => '31–50', 'min' => 31, 'max' => 50],
            ['label' => '51+', 'min' => 51, 'max' => null],
        ];

        $users = User::query()
            ->withCount('programs')
            ->get();

        return collect($ranges)->map(function (array $range) use ($users) {
            $count = $users->filter(function (User $user) use ($range) {
                if ($range['max'] === null) {
                    return $user->programs_count >= $range['min'];
                }

                return $user->programs_count >= $range['min'] && $user->programs_count <= $range['max'];
            })->count();

            return (object) ['label' => $range['label'], 'count' => $count];
        });
    }

    #[Computed]
    public function userProgramDetails(): Collection
    {
        return User::query()
            ->withCount('programs')
            ->withMax('programs', 'updated_at')
            ->addSelect([
                'last_login_at' => UserLogin::query()
                    ->selectRaw('max(created_at)')
                    ->whereColumn('user_logins.user_id', 'users.id'),
            ])
            ->orderByDesc('programs_count')
            ->orderByDesc('programs_max_updated_at')
            ->get();
    }

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

        $byReferralSource = User::query()
            ->selectRaw('referral_source, count(*) as total')
            ->groupBy('referral_source')
            ->orderByDesc('total')
            ->get();

        $byReferralDetail = User::query()
            ->selectRaw('referral_detail, count(*) as total')
            ->groupBy('referral_detail')
            ->orderByDesc('total')
            ->get();

        return view('livewire.founder.dashboard', [
            'totalLogins' => $totalLogins,
            'uniqueUsers' => $uniqueUsers,
            'recentLogins' => $recentLogins,
            'byOs' => $byOs,
            'byBrowser' => $byBrowser,
            'byDevice' => $byDevice,
            'byReferralSource' => $byReferralSource,
            'byReferralDetail' => $byReferralDetail,
        ])->layout('components.layouts.app', ['title' => __('Founder Dashboard')]);
    }
}
