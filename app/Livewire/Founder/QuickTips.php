<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Mail\QuickTipEmail;
use App\Models\QuickTip;
use Flux;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class QuickTips extends Component
{
    public ?int $viewingTipId = null;

    public ?int $deletingTipId = null;

    public function sendTestEmail(int $tipId): void
    {
        $tip = QuickTip::findOrFail($tipId);

        /** @var \App\Models\User $founder */
        $founder = auth()->user();

        Mail::to($founder)->send(new QuickTipEmail($tip, $founder));

        session()->flash('success', 'Test email sent to '.$founder->email);
    }

    public function openViewModal(int $tipId): void
    {
        $this->viewingTipId = $tipId;

        Flux::modal('quick-tip-view')->show();
    }

    public function confirmDelete(int $tipId): void
    {
        $this->deletingTipId = $tipId;

        Flux::modal('quick-tip-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingTipId) {
            QuickTip::findOrFail($this->deletingTipId)->delete();
        }

        Flux::modal('quick-tip-delete')->close();
        $this->deletingTipId = null;
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        $tips = QuickTip::query()->orderBy('sort_order')->get();

        $viewingTip = $this->viewingTipId ? QuickTip::find($this->viewingTipId) : null;

        return view('livewire.founder.quick-tips', [
            'tips' => $tips,
            'viewingTip' => $viewingTip,
        ])->layout('components.layouts.app', ['title' => __('Quick Tips')]);
    }
}
