<?php

declare(strict_types=1);

namespace App\Livewire\Artists;

use App\Models\Artist;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $filter = 'all';

    public function render(): View
    {
        $artists = Artist::query()->orderBy('artist_name')->get();

        return view('livewire.artists.index', [
            'artists' => $artists,
        ])->layout('components.layouts.app', ['title' => __('Artists')]);
    }
}
