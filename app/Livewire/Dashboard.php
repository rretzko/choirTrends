<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\ChecksProgramCompliance;
use App\Models\Artist;
use App\Models\DigitalProgram;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    use ChecksProgramCompliance;

    public int $artistsCount = 0;

    public int $digitalProgramsCount = 0;

    public int $ensemblesCount = 0;

    public int $programsCount = 0;

    public int $schoolsCount = 0;

    public int $songTitlesCount = 0;

    public int $usersCount = 0;

    public bool $catalogEnabled = false;

    public string $catalogUrl = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->catalogEnabled = $user->isCatalogEnabled();
        $this->catalogUrl = $user->catalog_token
            ? route('catalog.show', $user->catalog_token)
            : '';

        $this->artistsCount = Artist::query()->count();
        $this->ensemblesCount = Ensemble::query()->count();
        $this->programsCount = Program::query()->count();
        $this->schoolsCount = School::query()->count();
        $this->songTitlesCount = SongTitle::query()->count();
        $this->digitalProgramsCount = DigitalProgram::query()->count();
        $this->usersCount = User::query()->whereNotNull('email_verified_at')->whereNull('parent_user_id')->count();
    }

    public function toggleCatalog(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isCatalogEnabled()) {
            $user->disableCatalog();
            $this->catalogEnabled = false;
        } else {
            $user->enableCatalog();
            $this->catalogEnabled = true;
            $this->catalogUrl = route('catalog.show', $user->catalog_token);
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
