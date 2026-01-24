<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public int $artistsCount = 0;

    public int $ensemblesCount = 0;

    public int $programsCount = 0;

    public int $schoolsCount = 0;

    public int $songTitlesCount = 0;

    public int $usersCount = 0;

    public function mount(): void
    {
        $this->artistsCount = Artist::query()->count();
        $this->ensemblesCount = Ensemble::query()->count();
        $this->programsCount = Program::query()->count();
        $this->schoolsCount = School::query()->count();
        $this->songTitlesCount = SongTitle::query()->count();
        $this->usersCount = User::query()->count();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.dashboard');
    }
}
