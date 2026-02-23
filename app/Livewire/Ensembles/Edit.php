<?php

declare(strict_types=1);

namespace App\Livewire\Ensembles;

use App\Enums\EnsembleType;
use App\Models\Ensemble;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Edit extends Component
{
    public Ensemble $ensemble;

    public string $ensembleName = '';

    public string $schoolName = '';

    public string $type = '';

    public bool $aCappella = false;

    public bool $editable = true;

    public function mount(Ensemble $ensemble): void
    {
        $ensemble->load('school.users');

        $isOwner = $ensemble->school?->users->contains('id', Auth::id()) ?? false;
        abort_unless($isOwner, 403);

        $this->ensemble = $ensemble;
        $this->ensembleName = $ensemble->ensemble_name;
        $this->schoolName = $ensemble->school->school_name ?? '';
        $this->type = $ensemble->type->value;
        $this->aCappella = $ensemble->a_cappella;

        $this->editable = ! DB::table('program_song_title')
            ->join('programs', 'programs.id', '=', 'program_song_title.program_id')
            ->where('program_song_title.ensemble_id', $ensemble->id)
            ->where('programs.user_id', '!=', Auth::id())
            ->exists();
    }

    public function remove(): void
    {
        abort_unless($this->editable, 403);

        $this->ensemble->delete();

        session()->flash('success', 'Ensemble removed successfully!');
        $this->redirectRoute('ensembles.index');
    }

    public function save(): void
    {
        $this->validate([
            'ensembleName' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', array_column(EnsembleType::cases(), 'value'))],
            'aCappella' => ['boolean'],
        ]);

        $data = [
            'type' => $this->type,
            'a_cappella' => $this->aCappella,
        ];

        if ($this->editable) {
            $data['ensemble_name'] = $this->ensembleName;
        }

        $this->ensemble->update($data);

        session()->flash('success', 'Ensemble updated successfully!');
        $this->redirectRoute('ensembles.index');
    }

    public function render(): View
    {
        return view('livewire.ensembles.edit')
            ->layout('components.layouts.app', ['title' => __('Edit Ensemble')]);
    }
}
