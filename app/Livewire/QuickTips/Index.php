<?php

declare(strict_types=1);

namespace App\Livewire\QuickTips;

use App\Enums\QuickTipStatus;
use App\Models\QuickTip;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public ?int $openTipId = null;

    public function toggleTip(int $tipId): void
    {
        $this->openTipId = $this->openTipId === $tipId ? null : $tipId;
    }

    public function viewNextTip(): void
    {
        $user = auth()->user();

        $viewedTipIds = $user->quickTips()->pluck('quick_tips.id');

        $nextTip = QuickTip::query()
            ->where('status', QuickTipStatus::Sent)
            ->whereNotIn('id', $viewedTipIds)
            ->orderBy('sort_order')
            ->first();

        if ($nextTip) {
            $user->quickTips()->attach($nextTip->id, [
                'viewed_at' => now(),
            ]);

            $this->openTipId = $nextTip->id;
        }
    }

    public function render(): View
    {
        $user = auth()->user();

        $userTipIds = $user->quickTips()->pluck('quick_tips.id');

        $tips = QuickTip::query()
            ->where('status', QuickTipStatus::Sent)
            ->whereIn('id', $userTipIds)
            ->orderBy('sort_order')
            ->get();

        $hasNextTip = QuickTip::query()
            ->where('status', QuickTipStatus::Sent)
            ->whereNotIn('id', $userTipIds)
            ->exists();

        return view('livewire.quick-tips.index', [
            'tips' => $tips,
            'hasNextTip' => $hasNextTip,
        ])->layout('components.layouts.app', ['title' => __('Quick Tips')]);
    }
}
