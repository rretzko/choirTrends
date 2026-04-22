<?php

declare(strict_types=1);

namespace App\Livewire\Founder;

use App\Enums\QuickTipStatus;
use App\Models\QuickTip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

class QuickTipForm extends Component
{
    public ?QuickTip $quickTip = null;

    public int $sortOrder = 1;

    public string $header = '';

    public string $introduction = '';

    public string $tip = '';

    public string $footer = '';

    public string $callToAction = '';

    public string $status = 'Draft';

    public function mount(?QuickTip $quickTip = null): void
    {
        abort_unless(auth()->user()?->isFounder(), Response::HTTP_FORBIDDEN);

        if ($quickTip?->exists) {
            $this->quickTip = $quickTip;
            $this->sortOrder = $quickTip->sort_order;
            $this->header = $quickTip->header;
            $this->introduction = $quickTip->introduction ?? '';
            $this->tip = $quickTip->tip;
            $this->footer = $quickTip->footer ?? '';
            $this->callToAction = $quickTip->call_to_action ?? '';
            $this->status = $quickTip->status->value;
        } else {
            $this->sortOrder = (QuickTip::max('sort_order') ?? 0) + 1;
            $this->footer = config('quicktip.default_footer');
            $this->callToAction = config('quicktip.default_call_to_action');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'sortOrder' => ['required', 'integer', 'min:1'],
            'header' => ['required', 'string', 'max:255'],
            'introduction' => ['nullable', 'string'],
            'tip' => ['required', 'string'],
            'footer' => ['nullable', 'string'],
            'callToAction' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:Draft,Pending,Sent'],
        ]);

        $data = [
            'sort_order' => $validated['sortOrder'],
            'header' => $validated['header'],
            'introduction' => $validated['introduction'] ?: null,
            'tip' => $validated['tip'],
            'footer' => $validated['footer'] ?: null,
            'call_to_action' => $validated['callToAction'] ?: null,
            'status' => QuickTipStatus::from($validated['status']),
        ];

        if ($this->quickTip) {
            $this->quickTip->update($data);
            session()->flash('success', 'Quick tip updated successfully!');
        } else {
            QuickTip::create($data);
            session()->flash('success', 'Quick tip created successfully!');
        }

        $this->redirectRoute('founder.quickTips');
    }

    public function render(): View
    {
        $title = $this->quickTip ? __('Edit Quick Tip') : __('Add Quick Tip');

        /** @var string|null $lastEmailedAt */
        $lastEmailedAt = DB::table('quick_tip_user')->max('emailed_at');
        $reference = $lastEmailedAt ? Carbon::parse($lastEmailedAt) : Carbon::now();
        $nextMailingDate = $reference->copy()->addDays((int) config('quicktip.days_between_tips'));

        return view('livewire.founder.quick-tip-form', [
            'nextMailingDate' => $nextMailingDate,
        ])->layout('components.layouts.app', ['title' => $title]);
    }
}
